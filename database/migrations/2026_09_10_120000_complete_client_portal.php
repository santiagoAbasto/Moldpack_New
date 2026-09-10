<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_orders', function (Blueprint $table): void {
            $table->string('payment_method')->nullable()->after('delivery_address');
            $table->string('attachment_path')->nullable()->after('notes');
        });

        Schema::create('client_payment_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->date('paid_at');
            $table->decimal('amount', 14, 2);
            $table->string('bank', 160);
            $table->string('branch', 160)->nullable();
            $table->json('invoice_ids')->nullable();
            $table->text('observations')->nullable();
            $table->string('receipt_path')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payment_reports');
        Schema::table('client_orders', fn (Blueprint $table) => $table->dropColumn(['payment_method', 'attachment_path']));
    }
};
