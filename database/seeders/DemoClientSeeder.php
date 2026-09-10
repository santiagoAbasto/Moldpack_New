<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\ClientOrder;
use App\Models\ContentItem;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class DemoClientSeeder extends Seeder
{
    public function run(): void
    {
        $client = Cliente::updateOrCreate(['username' => 'demo'], [
            'name' => 'Juan Carlos Demo', 'first_name' => 'Juan Carlos', 'last_name' => 'Demo', 'business_name' => 'Moldpack Demo',
            'tax_id' => '30-00000000-0', 'email' => 'demo@moldpack.com.ar', 'phone' => '11 4727-2836',
            'billing_address' => 'Dante Alighieri 1377, Don Torcuato', 'delivery_address' => 'Dante Alighieri 1377, Don Torcuato',
            'discount_percent' => 10, 'show_prices' => true, 'is_active' => true, 'password' => 'demo1234',
            'password_encrypted' => encrypt('demo1234'), 'approved_at' => now(),
        ]);

        SiteSetting::updateOrCreate(['key' => 'client_portal'], ['value' => [
            'important_title' => 'Información importante',
            'important_body' => 'Los pedidos están sujetos a confirmación de stock. Los precios se expresan sin IVA y se actualizan al confirmar la operación.',
            'pickup_address' => 'Dante Alighieri 1377, Don Torcuato, Buenos Aires', 'bank_holder' => 'Moldpack',
            'bank_name' => 'Banco Nación', 'bank_cbu' => '0000383745825737629562',
            'bank_alias' => 'MOLDPACK.PACKAGING.CUENTA', 'bank_tax_id' => '27-8477203-8',
        ]]);

        $products = ContentItem::query()->whereHas('section', fn ($query) => $query->where('type', 'products'))->take(3)->get();
        foreach ([['MP-DEMO-1001', 'delivered', 48625.50], ['MP-DEMO-1002', 'preparing', 22748.00]] as $index => [$number, $status, $total]) {
            $order = ClientOrder::updateOrCreate(['number' => $number], [
                'cliente_id' => $client->id, 'status' => $status, 'billing_status' => $index === 0 ? 'invoiced' : 'pending',
                'delivery_method' => 'moldpack_delivery', 'delivery_address' => $client->delivery_address, 'payment_method' => 'transfer',
                'subtotal' => round($total / 1.21, 2), 'discount_percent' => 0, 'discount_total' => 0, 'tax_percent' => 21,
                'tax_total' => round($total - ($total / 1.21), 2), 'total' => $total,
                'delivered_at' => $status === 'delivered' ? now()->subDays(8) : null,
            ]);
            if ($order->items()->doesntExist()) foreach ($products as $product) {
                $presentation = collect(data_get($product->settings, 'presentations', []))->first();
                $price = (float) ($presentation['price'] ?? data_get($product->settings, 'price', 1000));
                $order->items()->create(['content_item_id' => $product->id, 'sku' => $presentation['code'] ?? data_get($product->settings, 'code'), 'name' => $product->title, 'presentation' => $presentation['name'] ?? $product->label, 'unit_price' => $price, 'quantity' => 2, 'prepared_quantity' => $status === 'delivered' ? 2 : 1, 'line_total' => $price * 2]);
            }
            $order->events()->firstOrCreate(['type' => 'created'], ['label' => 'Pedido recibido']);
            if ($index === 0) $order->invoices()->updateOrCreate(['number' => '0001-00001234'], ['type' => 'A', 'status' => 'issued', 'subtotal' => $order->subtotal, 'tax_total' => $order->tax_total, 'total' => $order->total, 'issued_at' => now()->subDays(10), 'due_at' => now()->addDays(10)]);
        }
    }
}
