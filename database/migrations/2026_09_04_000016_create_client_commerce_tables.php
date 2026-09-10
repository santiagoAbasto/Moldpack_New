<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('tax_id', 32)->nullable()->index();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(false)->index();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('client_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->string('number')->unique();
            $table->enum('status', ['pending', 'approved', 'preparing', 'ready', 'dispatched', 'delivered', 'cancelled'])->default('pending')->index();
            $table->enum('billing_status', ['pending', 'ready', 'invoiced', 'credited', 'cancelled'])->default('pending')->index();
            $table->string('delivery_method')->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(21);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('client_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_item_id')->nullable()->constrained('content_items')->nullOnDelete();
            $table->string('sku')->nullable()->index();
            $table->string('name');
            $table->string('presentation')->nullable();
            $table->decimal('unit_price', 14, 2);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('prepared_quantity')->default(0);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
        });

        Schema::create('client_order_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('label');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('client_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_order_id')->constrained()->restrictOnDelete();
            $table->string('type', 8)->default('A');
            $table->string('number')->unique();
            $table->enum('status', ['draft', 'issued', 'cancelled'])->default('draft')->index();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('external_reference')->nullable()->index();
            $table->string('document_path')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_invoices');
        Schema::dropIfExists('client_order_events');
        Schema::dropIfExists('client_order_items');
        Schema::dropIfExists('client_orders');
        Schema::dropIfExists('clientes');
    }
};
