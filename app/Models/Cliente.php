<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Cliente extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['username', 'name', 'first_name', 'last_name', 'business_name', 'tax_id', 'document_id', 'email', 'alternate_email', 'phone', 'billing_address', 'delivery_address', 'discount_percent', 'show_prices', 'is_active', 'password', 'password_encrypted', 'approved_at', 'started_on'];
    protected $hidden = ['password', 'password_encrypted', 'remember_token'];
    protected function casts(): array { return ['password' => 'hashed', 'is_active' => 'boolean', 'show_prices' => 'boolean', 'approved_at' => 'datetime', 'discount_percent' => 'decimal:2']; }
    public function orders(): HasMany { return $this->hasMany(ClientOrder::class); }
    public function paymentReports(): HasMany { return $this->hasMany(ClientPaymentReport::class); }
}
