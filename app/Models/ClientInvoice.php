<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClientInvoice extends Model { protected $guarded=[]; protected function casts(): array { return ['subtotal'=>'decimal:2','tax_total'=>'decimal:2','total'=>'decimal:2','issued_at'=>'date','due_at'=>'date']; } public function order(): BelongsTo { return $this->belongsTo(ClientOrder::class, 'client_order_id'); } }
