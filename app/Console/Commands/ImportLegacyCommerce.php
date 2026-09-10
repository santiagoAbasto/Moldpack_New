<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\ClientOrder;
use App\Models\ContentItem;
use Illuminate\Support\Facades\DB;

class ImportLegacyCommerce extends ImportLegacyCatalog
{
    protected $signature = 'commerce:import-legacy {dump : Ruta absoluta al respaldo SQL} {--dry-run}';
    protected $description = 'Importa clientes, pedidos, ítems y comprobantes del sistema histórico';
    protected array $tables = ['clientes', 'pedidos', 'facturas_relacions'];

    public function handle(): int
    {
        $path = (string) $this->argument('dump');
        if (! is_readable($path)) { $this->error('No se puede leer el respaldo.'); return self::FAILURE; }
        $data = $this->readDump($path);
        $this->table(['Entidad','Registros'], [['Clientes',count($data['clientes'])],['Pedidos',count($data['pedidos'])],['Comprobantes',count($data['facturas_relacions'])]]);
        if ($this->option('dry-run')) return self::SUCCESS;

        $products = ContentItem::query()->get()->mapWithKeys(fn ($item) => [(string) data_get($item->settings, 'legacy_id') => $item]);
        DB::transaction(function () use ($data, $products): void {
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::table('client_order_events')->delete(); DB::table('client_invoices')->delete(); DB::table('client_order_items')->delete(); DB::table('client_orders')->delete(); DB::table('clientes')->delete();
            foreach ($data['clientes'] as $row) {
                $email = filter_var($row['email'] ?? null, FILTER_VALIDATE_EMAIL) ? $row['email'] : 'cliente'.$row['id'].'@legacy.moldpack.local';
                $username = $row['username'] ?: 'cliente'.$row['id'];
                if (DB::table('clientes')->where('username', $username)->exists()) $username .= '-'.$row['id'];
                if (DB::table('clientes')->where('email', $email)->exists()) $email = 'cliente'.$row['id'].'@legacy.moldpack.local';
                DB::table('clientes')->insert([
                    'id'=>$row['id'], 'username'=>$username,
                    'name'=>trim(($row['nombre']??'').' '.($row['apellido']??'')) ?: ($row['razonSocial'] ?: 'Cliente '.$row['id']),
                    'first_name'=>$row['nombre'] ?: null, 'last_name'=>$row['apellido'] ?: null,
                    'business_name'=>$row['razonSocial'] ?: null, 'tax_id'=>$row['cuit'] ?: null, 'document_id'=>$row['dni'] ?: null,
                    'email'=>$email, 'alternate_email'=>$row['emailAux'] ?: null, 'phone'=>$row['telefono'] ?: null,
                    'billing_address'=>$row['direccion'] ?: null, 'delivery_address'=>$row['direccionEntrega'] ?: null,
                    'discount_percent'=>(float)($row['descuento']??0), 'show_prices'=>(bool)($row['precios']??false),
                    'is_active'=>(bool)($row['estado']??$row['activo']??0),
                    'password'=>$row['password'] ?: password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
                    'password_encrypted'=>$row['password_encrypted'] ?: null, 'remember_token'=>$row['remember_token']??null,
                    'approved_at'=>(($row['estado']??0) ? ($row['updated_at'] ?: now()) : null), 'started_on'=>$row['fechaInicio'] ?: null,
                    'created_at'=>$row['created_at'], 'updated_at'=>$row['updated_at'],
                ]);
            }
            foreach ($data['pedidos'] as $row) {
                if (! Cliente::query()->whereKey($row['usuario_id'])->exists()) continue;
                $legacyStatus=(int)($row['estado']??0); $status=match(true){$legacyStatus<=0=>'pending',$legacyStatus===1=>'approved',$legacyStatus===2=>'preparing',$legacyStatus===3=>'ready',$legacyStatus===4=>'dispatched',default=>'delivered'};
                $items=json_decode($row['pedido']??'[]',true); if(!is_array($items))$items=[]; $total=(float)($row['total']??0);
                $order=ClientOrder::create(['id'=>$row['id'],'cliente_id'=>$row['usuario_id'],'number'=>'MP-LEG-'.str_pad((string)$row['id'],6,'0',STR_PAD_LEFT),'status'=>$status,'billing_status'=>(float)($row['facturaTotal']??0)>0?'invoiced':'pending','delivery_method'=>$row['locale']??null,'notes'=>$row['mensaje']??null,'subtotal'=>$total,'discount_percent'=>0,'discount_total'=>0,'tax_percent'=>21,'tax_total'=>0,'total'=>(float)($row['facturaTotal']?:$total),'created_at'=>$row['created_at']?:now(),'updated_at'=>$row['updated_at']?:now()]);
                foreach ($items as $item) { if(!is_array($item))continue; $legacyId=(string)($item['id']??$item['producto_id']??''); $product=$products->get($legacyId); $qty=(int)($item['cantidad']??$item['quantity']??1); $price=(float)($item['precio']??$item['price']??0); $order->items()->create(['content_item_id'=>$product?->id,'sku'=>$item['codigo']??data_get($product?->settings,'code'),'name'=>$item['nombre']??$product?->title??'Producto histórico','presentation'=>$item['presentacion']??null,'unit_price'=>$price,'quantity'=>max(1,$qty),'prepared_quantity'=>(int)($item['stock']??0),'line_total'=>(float)($item['subtotal']??($price*$qty))]); }
                $order->events()->create(['type'=>'legacy_import','label'=>'Importado del sistema anterior','metadata'=>['legacy_id'=>$row['id'],'bultos'=>$row['bultos']??null]]);
            }
            foreach ($data['facturas_relacions'] as $row) { $order=ClientOrder::query()->find($row['pedido_id']); if(!$order)continue; $type=match(strtoupper((string)($row['factura']??'A'))){'A'=>'A','B'=>'B','C'=>'C',default=>'NC'}; $number=$type.'-'.($row['numeroFactura']?:$row['id']); if(DB::table('client_invoices')->where('number',$number)->exists())$number.='-'.$row['id']; $order->invoices()->create(['type'=>$type,'number'=>$number,'status'=>(int)($row['estado']??1)===0?'cancelled':'issued','subtotal'=>(float)($row['subtotal']??0),'tax_total'=>max(0,(float)($row['total']??0)-(float)($row['subtotal']??0)),'total'=>(float)($row['total']??0),'external_reference'=>'legacy:'.$row['id'],'issued_at'=>substr((string)($row['created_at']??now()),0,10),'created_at'=>$row['created_at']?:now(),'updated_at'=>$row['updated_at']?:now()]); }
            DB::statement('PRAGMA foreign_keys=ON');
        });
        $this->info('Migración completada: '.Cliente::count().' clientes, '.ClientOrder::count().' pedidos.');
        return self::SUCCESS;
    }
}
