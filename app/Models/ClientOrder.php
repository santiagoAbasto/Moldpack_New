<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ClientOrder extends Model {
    protected $guarded = [];
    protected function casts(): array { return ['subtotal'=>'decimal:2','discount_percent'=>'decimal:2','discount_total'=>'decimal:2','tax_percent'=>'decimal:2','tax_total'=>'decimal:2','total'=>'decimal:2','approved_at'=>'datetime','dispatched_at'=>'datetime','delivered_at'=>'datetime']; }
    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class); }
    public function items(): HasMany { return $this->hasMany(ClientOrderItem::class); }
    public function events(): HasMany { return $this->hasMany(ClientOrderEvent::class)->latest(); }
    public function invoices(): HasMany { return $this->hasMany(ClientInvoice::class); }
}
