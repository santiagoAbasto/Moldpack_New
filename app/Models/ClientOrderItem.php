<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClientOrderItem extends Model { protected $guarded=[]; protected function casts(): array { return ['unit_price'=>'decimal:2','line_total'=>'decimal:2']; } public function order(): BelongsTo { return $this->belongsTo(ClientOrder::class, 'client_order_id'); } }
