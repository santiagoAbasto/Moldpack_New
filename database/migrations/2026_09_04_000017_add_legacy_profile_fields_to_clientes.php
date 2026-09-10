<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('alternate_email')->nullable()->after('email');
            $table->string('document_id', 32)->nullable()->after('tax_id');
            $table->text('delivery_address')->nullable()->after('billing_address');
            $table->string('started_on')->nullable()->after('approved_at');
            $table->boolean('show_prices')->default(false)->after('discount_percent');
            $table->text('password_encrypted')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name', 'alternate_email', 'document_id', 'delivery_address', 'started_on', 'show_prices', 'password_encrypted']);
        });
    }
};
