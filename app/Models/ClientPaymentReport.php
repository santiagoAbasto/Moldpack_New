<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPaymentReport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['paid_at' => 'date', 'amount' => 'decimal:2', 'invoice_ids' => 'array'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
