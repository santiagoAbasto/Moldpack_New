<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClientInvoice;
use App\Models\ClientOrder;
use App\Models\ClientPaymentReport;
use App\Models\ContentItem;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientAreaController extends Controller
{
    private const SECTIONS = ['productos', 'carrito', 'pedidos', 'pagos', 'cuenta', 'facturas'];

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['username' => ['required', 'string', 'max:120'], 'password' => ['required', 'string']]);
        $client = Cliente::query()->where('username', $data['username'])->orWhere('email', $data['username'])->first();
        $matches = false;
        if ($client) { try { $matches = Hash::check($data['password'], $client->password); } catch (\Throwable) { $matches = hash_equals((string) $client->password, (string) $data['password']); } }
        if (! $client || ! $client->is_active || ! $matches) throw ValidationException::withMessages(['username' => 'Usuario o contraseña incorrectos, o cuenta pendiente de aprobación.']);
        if (! str_starts_with((string) $client->password, '$2')) { $client->password = $data['password']; $client->save(); }
        Auth::guard('cliente')->login($client, $request->boolean('remember'));
        $request->session()->regenerate();
        return redirect()->intended(route('client.portal', 'productos'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'alpha_dash', 'max:80', 'unique:clientes'], 'name' => ['required', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:160'], 'tax_id' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:180', 'unique:clientes'], 'phone' => ['nullable', 'string', 'max:60'],
            'password' => ['required', 'confirmed', 'min:8', 'max:128'],
        ]);
        Cliente::create($data + ['is_active' => false]);
        return redirect('/')->with('success', 'Recibimos tu solicitud. Te avisaremos cuando la cuenta esté habilitada.');
    }

    public function dashboard(): RedirectResponse { return redirect()->route('client.portal', 'productos'); }

    public function portal(string $section = 'productos'): View
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);
        /** @var Cliente $client */
        $client = Auth::guard('cliente')->user();
        $orders = $client->orders()->with(['items', 'invoices', 'events'])->latest()->get();
        $products = ContentItem::query()->whereHas('section', fn ($query) => $query->where('type', 'products'))
            ->where('is_visible', true)->with('media')->orderBy('sort_order')->get()
            ->each(function (ContentItem $product): void {
                $settings = $product->settings ?? [];
                $presentation = collect($settings['presentations'] ?? [])->first(fn ($row) => (float) ($row['price'] ?? 0) > 0) ?? collect($settings['presentations'] ?? [])->first();
                if ($presentation) {
                    $settings['price'] ??= (float) ($presentation['price'] ?? 0);
                    $settings['code'] ??= $presentation['code'] ?? null;
                    $settings['presentation'] ??= $presentation['name'] ?? null;
                    $settings['stock'] ??= $presentation['stock'] ?? 0;
                    $product->setAttribute('settings', $settings);
                }
            })
            ->filter(fn ($product) => (float) data_get($product->settings, 'price', 0) > 0)->values();
        $cart = $this->cart();
        $productIndex = $products->keyBy('id');
        $cartLines = $cart->map(function (array $row, string $key) use ($productIndex) {
            $product = $productIndex->get($row['product_id']);
            if (! $product) return null;
            $presentations = $this->purchasablePresentations($product);
            $presentation = $presentations->get($row['presentation_index']) ?? $presentations->first() ?? [];
            return ['key' => $key, 'product' => $product, 'quantity' => $row['quantity'], 'presentation_index' => (int) $row['presentation_index'], 'presentation' => $presentation, 'price' => (float) ($presentation['price'] ?? data_get($product->settings, 'price', 0))];
        })->filter()->values();
        $invoices = $orders->flatMap->invoices->where('status', '!=', 'cancelled')->sortByDesc('issued_at')->values();
        $paymentReports = $client->paymentReports()->latest('paid_at')->get();
        $invoiceOutstanding = $this->invoiceOutstandingBalances($invoices, $paymentReports);
        $pendingInvoices = $invoices->filter(fn (ClientInvoice $invoice) => (float) $invoiceOutstanding->get($invoice->id, 0) > 0.005)->values();
        $balance = (float) $invoiceOutstanding->sum();
        $overdueBalance = (float) $invoices->filter(fn (ClientInvoice $invoice) => $invoice->due_at?->isPast())->sum(fn (ClientInvoice $invoice) => $invoiceOutstanding->get($invoice->id, 0));
        $settings = SiteSetting::query()->where('key', 'client_portal')->value('value') ?? [];
        $contact = SiteSetting::query()->where('key', 'contact')->value('value') ?? [];
        $socialLinks = SiteSetting::query()->where('key', 'social')->value('value')['links'] ?? [];
        $newsletterSettings = SiteSetting::query()->where('key', 'newsletter')->value('value') ?? [];
        $categories = $products->map(fn ($product) => data_get($product->settings, 'category') ?: explode('/', (string) $product->subtitle)[0])->map(fn ($v) => trim((string) $v))->filter()->unique()->values();
        return view('client.portal', compact('section', 'client', 'orders', 'products', 'cart', 'cartLines', 'invoices', 'pendingInvoices', 'invoiceOutstanding', 'paymentReports', 'balance', 'overdueBalance', 'settings', 'categories', 'contact', 'socialLinks', 'newsletterSettings'));
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'exists:content_items,id'], 'presentation_index' => ['nullable', 'integer', 'min:0'], 'quantity' => ['required', 'integer', 'min:1', 'max:9999']]);
        $index = (int) ($data['presentation_index'] ?? 0);
        $product = ContentItem::findOrFail($data['product_id']);
        $presentations = $this->purchasablePresentations($product);
        if ($presentations->isNotEmpty() && ! $presentations->has($index)) throw ValidationException::withMessages(['presentation_index' => 'La presentación seleccionada no está disponible.']);
        $key = $data['product_id'].':'.$index;
        $cart = $this->cart()->all();
        $cart[$key] = ['product_id' => (int) $data['product_id'], 'presentation_index' => $index, 'quantity' => min(9999, ($cart[$key]['quantity'] ?? 0) + $data['quantity'])];
        session(['client_cart' => $cart]);
        return redirect()->route('client.portal', $request->input('stay', 'carrito'))->with('success', 'Producto agregado al carrito.');
    }

    public function updateCart(Request $request, string $product): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:9999'],
            'presentation_index' => ['nullable', 'integer', 'min:0'],
        ]);
        $cart = $this->cart()->all();
        if (! isset($cart[$product])) return back();
        $row = $cart[$product];
        $index = (int) ($data['presentation_index'] ?? $row['presentation_index']);
        $catalogProduct = ContentItem::find($row['product_id']);
        $presentations = $this->purchasablePresentations($catalogProduct);
        if ($presentations->isNotEmpty() && ! $presentations->has($index)) throw ValidationException::withMessages(['presentation_index' => 'La presentación seleccionada no está disponible.']);
        unset($cart[$product]);
        if ((int) $data['quantity'] > 0) {
            $newKey = $row['product_id'].':'.$index;
            $cart[$newKey] = ['product_id' => $row['product_id'], 'presentation_index' => $index, 'quantity' => min(9999, (int) $data['quantity'] + ($cart[$newKey]['quantity'] ?? 0))];
        }
        session(['client_cart' => $cart]);
        return back()->with('success', 'Carrito actualizado.');
    }

    public function removeFromCart(string $product): RedirectResponse
    {
        $cart = $this->cart()->all(); unset($cart[$product]); session(['client_cart' => $cart]);
        return back()->with('success', 'Producto quitado del carrito.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'delivery_method' => ['required', Rule::in(['pickup', 'moldpack_delivery', 'freight'])], 'delivery_address' => ['nullable', 'max:500'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'check'])], 'notes' => ['nullable', 'max:1500'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,xls,xlsx,doc,docx', 'max:10240'],
        ]);
        $cart = $this->cart();
        if ($cart->isEmpty()) return back()->withErrors(['cart' => 'El carrito está vacío.']);
        $products = ContentItem::query()->whereIn('id', $cart->pluck('product_id'))->get()->keyBy('id');
        $attachment = $request->file('attachment')?->store('client-orders', 'public');
        DB::transaction(function () use ($request, $data, $cart, $products, $attachment): void {
            $subtotal = $cart->sum(function (array $row) use ($products) { $product = $products->get($row['product_id']); $presentation = $this->purchasablePresentations($product)->get($row['presentation_index']); return (float) ($presentation['price'] ?? data_get($product?->settings, 'price', 0)) * $row['quantity']; });
            $discountPercent = (float) $request->user('cliente')->discount_percent;
            $discount = round($subtotal * $discountPercent / 100, 2); $net = $subtotal - $discount; $tax = round($net * .21, 2);
            $order = ClientOrder::create([
                'cliente_id' => $request->user('cliente')->id, 'number' => 'MP-'.now()->format('ymd').'-'.str_pad((string) ((ClientOrder::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT),
                'status' => 'pending', 'billing_status' => 'pending', 'delivery_method' => $data['delivery_method'], 'delivery_address' => $data['delivery_address'] ?? null,
                'payment_method' => $data['payment_method'], 'notes' => $data['notes'] ?? null, 'attachment_path' => $attachment,
                'subtotal' => $subtotal, 'discount_percent' => $discountPercent, 'discount_total' => $discount, 'tax_percent' => 21, 'tax_total' => $tax, 'total' => $net + $tax,
            ]);
            foreach ($cart as $row) { $product = $products->get($row['product_id']); if (! $product) continue; $presentation = $this->purchasablePresentations($product)->get($row['presentation_index']) ?? []; $price = (float) ($presentation['price'] ?? data_get($product->settings, 'price', 0)); $quantity = $row['quantity']; $order->items()->create(['content_item_id' => $product->id, 'sku' => $presentation['code'] ?? data_get($product->settings, 'code'), 'name' => $product->title, 'presentation' => $presentation['name'] ?? data_get($product->settings, 'presentation', $product->label), 'unit_price' => $price, 'quantity' => $quantity, 'line_total' => $price * $quantity]); }
            $order->events()->create(['type' => 'created', 'label' => 'Pedido recibido']);
        });
        session()->forget('client_cart');
        return redirect()->route('client.portal', 'pedidos')->with('success', 'Pedido enviado correctamente.');
    }

    public function reorder(Request $request, ClientOrder $order): RedirectResponse
    {
        abort_unless($order->cliente_id === $request->user('cliente')->id, 403);

        $cart = $this->cart()->all();
        $added = 0;
        $order->loadMissing('items');
        $products = ContentItem::query()
            ->whereIn('id', $order->items->pluck('content_item_id')->filter())
            ->where('is_visible', true)
            ->get()
            ->keyBy('id');

        foreach ($order->items as $item) {
            $product = $products->get($item->content_item_id);
            if (! $product) continue;

            $presentations = $this->purchasablePresentations($product);
            if ($presentations->isEmpty() || $presentations->every(fn ($presentation) => (float) ($presentation['price'] ?? 0) <= 0)) continue;
            $presentationIndex = $presentations->search(fn ($presentation) =>
                ($item->sku && ($presentation['code'] ?? null) === $item->sku)
                || ($item->presentation && ($presentation['name'] ?? null) === $item->presentation)
            );
            $presentationIndex = $presentationIndex === false ? 0 : (int) $presentationIndex;
            if ((float) ($presentations->get($presentationIndex)['price'] ?? 0) <= 0) continue;
            $key = $product->id.':'.$presentationIndex;
            $quantity = max(1, (int) $item->quantity);
            $cart[$key] = [
                'product_id' => $product->id,
                'presentation_index' => $presentationIndex,
                'quantity' => min(9999, ($cart[$key]['quantity'] ?? 0) + $quantity),
            ];
            $added += $quantity;
        }

        if ($added === 0) {
            return back()->withErrors(['reorder' => 'Los productos de este pedido ya no están disponibles.']);
        }

        session(['client_cart' => $cart]);

        return redirect()->route('client.portal', 'carrito')->with('success', 'Agregamos nuevamente los productos disponibles a tu carrito.');
    }

    public function reportPayment(Request $request): RedirectResponse
    {
        /** @var Cliente $client */ $client = $request->user('cliente');
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', (string) $request->input('paid_at'), $date)) {
            $request->merge(['paid_at' => $date[3].'-'.$date[2].'-'.$date[1]]);
        }
        $data = $request->validate([
            'paid_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'bank' => ['required', 'string', 'max:160'], 'branch' => ['required', 'string', 'max:160'], 'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => [Rule::exists('client_invoices', 'id')->where(fn ($query) => $query->whereIn('client_order_id', $client->orders()->select('id')))],
            'observations' => ['nullable', 'string', 'max:1500'], 'receipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $clientInvoices = $client->orders()->with('invoices')->get()->flatMap->invoices->where('status', '!=', 'cancelled')->values();
        $outstanding = $this->invoiceOutstandingBalances($clientInvoices, $client->paymentReports()->get());
        $selectedBalance = collect($data['invoice_ids'])->unique()->sum(fn ($invoiceId) => (float) $outstanding->get((int) $invoiceId, 0));
        if ($selectedBalance <= 0.005) {
            throw ValidationException::withMessages(['invoice_ids' => 'Las facturas seleccionadas ya no tienen saldo pendiente.']);
        }
        if ((float) $data['amount'] > $selectedBalance + 0.005) {
            throw ValidationException::withMessages(['amount' => 'El importe no puede superar el saldo pendiente seleccionado de $'.number_format($selectedBalance, 2, ',', '.')]);
        }
        $data['cliente_id'] = $client->id; $data['receipt_path'] = $request->file('receipt')->store('payment-receipts', 'public'); unset($data['receipt']);
        ClientPaymentReport::create($data);
        return back()->with('success', 'Comprobante informado. Lo validaremos a la brevedad.');
    }

    public function downloadInvoice(Request $request, ClientInvoice $invoice)
    {
        abort_unless($invoice->order()->where('cliente_id', $request->user('cliente')->id)->exists(), 403);
        if ($invoice->document_path) {
            $path = preg_replace('#^storage/#', '', $invoice->document_path);
            if (Storage::disk('public')->exists($path)) return Storage::disk('public')->download($path);
            if (is_file(public_path($invoice->document_path))) return response()->download(public_path($invoice->document_path));
        }
        $invoice->load('order.cliente', 'order.items');
        $filename = 'factura-'.preg_replace('/[^A-Za-z0-9-]/', '-', $invoice->number).'.pdf';

        return Pdf::loadView('client.invoice-download', compact('invoice'))
            ->setPaper('a4')
            ->download($filename);
    }

    public function downloadPaymentReceipt(Request $request, ClientPaymentReport $payment)
    {
        abort_unless($payment->cliente_id === $request->user('cliente')->id, 403);
        abort_unless($payment->receipt_path && Storage::disk('public')->exists($payment->receipt_path), 404);

        return Storage::disk('public')->download($payment->receipt_path);
    }

    public function logout(Request $request): RedirectResponse
    {
        $redirectTo = (string) $request->input('redirect_to', '/');
        $isSafePublicPath = str_starts_with($redirectTo, '/')
            && ! str_starts_with($redirectTo, '//')
            && ! str_starts_with($redirectTo, '/area-clientes')
            && ! str_starts_with($redirectTo, '/admin');
        Auth::guard('cliente')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($isSafePublicPath ? $redirectTo : '/');
    }

    private function cart(): \Illuminate\Support\Collection
    {
        return collect(session('client_cart', []))->mapWithKeys(function ($value, $key) {
            if (is_array($value)) {
                $quantity = max(0, (int) ($value['quantity'] ?? 0));
                return $quantity ? [(string) $key => ['product_id' => (int) ($value['product_id'] ?? explode(':', (string) $key)[0]), 'presentation_index' => (int) ($value['presentation_index'] ?? 0), 'quantity' => $quantity]] : [];
            }
            $quantity = max(0, (int) $value);
            return $quantity ? [((int) $key).':0' => ['product_id' => (int) $key, 'presentation_index' => 0, 'quantity' => $quantity]] : [];
        });
    }

    private function invoiceOutstandingBalances(Collection $invoices, Collection $paymentReports): Collection
    {
        $balances = $invoices->mapWithKeys(fn (ClientInvoice $invoice) => [$invoice->id => max(0, (float) $invoice->total)]);
        $fallbackOrder = $invoices->sortBy(fn (ClientInvoice $invoice) => ($invoice->due_at?->timestamp ?? PHP_INT_MAX).'-'.str_pad((string) $invoice->id, 12, '0', STR_PAD_LEFT))->pluck('id');

        foreach ($paymentReports->where('status', 'verified')->sortBy(fn (ClientPaymentReport $payment) => ($payment->paid_at?->timestamp ?? 0).'-'.str_pad((string) $payment->id, 12, '0', STR_PAD_LEFT)) as $payment) {
            $remaining = max(0, (float) $payment->amount);
            $invoiceIds = collect($payment->invoice_ids)->map(fn ($id) => (int) $id)->filter(fn ($id) => $balances->has($id))->unique()->values();
            if ($invoiceIds->isEmpty()) $invoiceIds = $fallbackOrder;

            foreach ($invoiceIds as $invoiceId) {
                if ($remaining <= 0.005) break;
                $current = (float) $balances->get($invoiceId, 0);
                $applied = min($current, $remaining);
                $balances->put($invoiceId, round($current - $applied, 2));
                $remaining = round($remaining - $applied, 2);
            }
        }

        return $balances;
    }

    private function purchasablePresentations(?ContentItem $product): Collection
    {
        if (! $product) return collect();

        $presentations = collect(data_get($product->settings, 'presentations', []))
            ->filter(fn ($presentation) => (float) ($presentation['price'] ?? 0) > 0)
            ->values();

        if ($presentations->isNotEmpty()) return $presentations;

        return collect([[
            'code' => data_get($product->settings, 'code'),
            'name' => data_get($product->settings, 'presentation', $product->label),
            'price' => (float) data_get($product->settings, 'price', 0),
            'stock' => data_get($product->settings, 'stock', 0),
        ]]);
    }
}
