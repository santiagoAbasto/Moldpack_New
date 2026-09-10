<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClientInvoice;
use App\Models\ClientOrder;
use App\Models\ClientPaymentReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class ClientCommerceController extends Controller
{
    public function data(Request $request, string $type): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $perPage = min(100, max(10, (int) $request->query('per_page', 50)));
        if ($type === 'clientes') {
            $query = Cliente::query()->withCount('orders')->latest();
            if ($search !== '') $query->where(fn ($q) => $q->where('name','like',"%{$search}%")->orWhere('business_name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")->orWhere('tax_id','like',"%{$search}%"));
        } elseif ($type === 'pedidos') {
            $query = ClientOrder::query()->with(['cliente','items'])->latest();
            if ($search !== '') $query->where(fn ($q) => $q->where('number','like',"%{$search}%")->orWhereHas('cliente',fn($c)=>$c->where('name','like',"%{$search}%")->orWhere('business_name','like',"%{$search}%")));
            if ($request->filled('status')) $query->where('status', $request->query('status'));
            if ($request->filled('billing_status')) $query->where('billing_status', $request->query('billing_status'));
        } elseif ($type === 'facturas') {
            $query = ClientInvoice::query()->with('order.cliente')->latest();
            if ($search !== '') $query->where(fn ($q) => $q->where('number','like',"%{$search}%")->orWhere('external_reference','like',"%{$search}%"));
        } else abort(404);
        return response()->json($query->paginate($perPage));
    }
    public function export(string $type)
    {
        abort_unless(in_array($type, ['clientes','pedidos','stock'], true), 404);
        return response()->streamDownload(function () use ($type): void {
            $out=fopen('php://output','wb'); fwrite($out, "\xEF\xBB\xBF");
            $text = fn ($value) => $this->csvText($value);
            if($type==='clientes'){fputcsv($out,['Nombre','Empresa','CUIT','Email','Estado']);Cliente::query()->orderBy('id')->chunk(500,fn($rows)=>$rows->each(fn($c)=>fputcsv($out,[$text($c->name),$text($c->business_name),$text($c->tax_id),$text($c->email),$c->is_active?'Activo':'Pendiente'])));}
            elseif($type==='pedidos'){fputcsv($out,['Pedido','Fecha','Cliente','Estado','Facturación','Total']);ClientOrder::query()->with('cliente')->orderBy('id')->chunk(500,fn($rows)=>$rows->each(fn($o)=>fputcsv($out,[$text($o->number),$o->created_at,$text($o->cliente?->business_name?:$o->cliente?->name),$o->status,$o->billing_status,$o->total])));}
            else {fputcsv($out,['Producto','SKU','Precio','Stock']);\App\Models\ContentItem::query()->whereHas('section',fn($q)=>$q->where('type','products'))->orderBy('id')->chunk(500,fn($rows)=>$rows->each(fn($p)=>fputcsv($out,[$text($p->title),$text(data_get($p->settings,'code')),data_get($p->settings,'price'),data_get($p->settings,'stock')])));}
            fclose($out);
        }, $type.'-moldpack-'.now()->format('Ymd').'.csv', ['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    /** Spreadsheet apps execute cells starting with = + - @ as formulas; names come from public registration. */
    private function csvText(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    public function updateClient(Request $request, Cliente $cliente): RedirectResponse
    {
        $data = $request->validate([
            'username'=>['required','max:80',Rule::unique('clientes')->ignore($cliente)], 'name'=>['required','max:120'],
            'first_name'=>['nullable','max:100'], 'last_name'=>['nullable','max:120'], 'business_name'=>['nullable','max:160'],
            'email'=>['required','email',Rule::unique('clientes')->ignore($cliente)], 'alternate_email'=>['nullable','email','max:180'],
            'phone'=>['nullable','max:60'], 'tax_id'=>['nullable','max:32'], 'document_id'=>['nullable','max:32'],
            'billing_address'=>['nullable','max:500'], 'delivery_address'=>['nullable','max:500'], 'started_on'=>['nullable','max:40'],
            'discount_percent'=>['required','numeric','between:0,100'], 'show_prices'=>['required','boolean'], 'is_active'=>['required','boolean'],
        ]);
        if ($data['is_active'] && ! $cliente->approved_at) $data['approved_at'] = now();
        $cliente->update($data);
        return back()->with('success', 'Cliente actualizado.');
    }

    public function updateClientPassword(Request $request, Cliente $cliente): RedirectResponse
    {
        $data = $request->validate([
            'admin_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ], ['admin_password.current_password' => 'La contraseña del administrador no es correcta.']);

        $cliente->forceFill([
            'password' => $data['password'],
            'password_encrypted' => Crypt::encryptString($data['password']),
            'remember_token' => \Illuminate\Support\Str::random(60),
        ])->save();

        return back()->with('success', 'Nueva contraseña del cliente guardada de forma segura.');
    }

    public function viewClientPassword(Request $request, Cliente $cliente): JsonResponse
    {
        $request->validate(['admin_password' => ['required', 'current_password']]);
        if (! $cliente->password_encrypted) {
            return response()->json(['message' => 'La clave histórica está hasheada y no puede recuperarse. Creá una nueva desde este editor.'], 404);
        }
        try {
            return response()->json(['password' => Crypt::decryptString($cliente->password_encrypted)]);
        } catch (\Throwable) {
            return response()->json(['message' => 'La clave histórica fue cifrada con otra llave. Creá una nueva para este cliente.'], 422);
        }
    }

    public function updateOrder(Request $request, ClientOrder $order): RedirectResponse
    {
        $data = $request->validate(['status'=>['required',Rule::in(['pending','approved','preparing','ready','dispatched','delivered','cancelled'])],'notes'=>['nullable','max:1000'],'prepared'=>['nullable','array'],'prepared.*'=>['integer','min:0']]);
        DB::transaction(function () use ($order, $data, $request): void {
            $old = $order->status;
            $extra = [];
            if ($data['status']==='approved') $extra['approved_at'] ??= now();
            if ($data['status']==='dispatched') $extra['dispatched_at'] ??= now();
            if ($data['status']==='delivered') $extra['delivered_at'] ??= now();
            $order->update(['status'=>$data['status'],'notes'=>$data['notes'] ?? $order->notes] + $extra);
            foreach (($data['prepared'] ?? []) as $id=>$quantity) $order->items()->whereKey($id)->update(['prepared_quantity'=>$quantity]);
            if ($old !== $data['status']) $order->events()->create(['user_id'=>$request->user()->id,'type'=>'status','label'=>'Estado: '.$data['status'],'notes'=>$data['notes'] ?? null]);
        });
        return back()->with('success', 'Pedido y preparación actualizados.');
    }

    public function createInvoice(Request $request, ClientOrder $order): RedirectResponse
    {
        $data = $request->validate(['type'=>['required',Rule::in(['A','B','C','NC'])],'number'=>['required','max:60','unique:client_invoices'],'issued_at'=>['required','date'],'due_at'=>['nullable','date','after_or_equal:issued_at'],'external_reference'=>['nullable','max:120']]);
        DB::transaction(function () use ($order, $data, $request): void {
            $order->invoices()->create($data + ['status'=>'issued','subtotal'=>$order->subtotal,'tax_total'=>$order->tax_total,'total'=>$order->total]);
            $order->update(['billing_status'=>$data['type']==='NC' ? 'credited' : 'invoiced']);
            $order->events()->create(['user_id'=>$request->user()->id,'type'=>'invoice','label'=>"Comprobante {$data['type']} {$data['number']}"]);
        });
        return back()->with('success', 'Comprobante registrado.');
    }

    public function cancelInvoice(ClientInvoice $invoice): RedirectResponse
    {
        $invoice->update(['status'=>'cancelled']);
        return back()->with('success', 'Comprobante anulado.');
    }

    public function updatePayment(Request $request, ClientPaymentReport $payment): RedirectResponse
    {
        $payment->update($request->validate(['status' => ['required', Rule::in(['pending', 'verified', 'rejected'])]]));
        return back()->with('success', 'Estado del pago actualizado.');
    }
}
