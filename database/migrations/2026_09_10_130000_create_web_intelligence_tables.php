<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('web_analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('occurred_at')->index();
            $table->string('session_hash', 64)->nullable()->index();
            $table->string('ip_hash', 64)->index();
            $table->string('ip_masked', 64)->nullable();
            $table->string('method', 10);
            $table->string('path', 500)->index();
            $table->string('route_name', 180)->nullable();
            $table->unsignedSmallInteger('status_code')->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->string('source', 40)->default('direct')->index();
            $table->string('device', 30)->nullable()->index();
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('visitor_class', 30)->default('human')->index();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('country', 100)->nullable();
            $table->string('region', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 180)->nullable();
            $table->timestamps();
            $table->index(['occurred_at', 'visitor_class']);
            $table->index(['occurred_at', 'status_code']);
        });

        Schema::create('security_events', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('occurred_at')->index();
            $table->string('ip_hash', 64)->index();
            $table->string('ip_masked', 64)->nullable();
            $table->string('event_type', 80)->index();
            $table->string('path', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('action', 30)->default('observed')->index();
            $table->string('risk', 20)->default('low')->index();
            $table->json('evidence')->nullable();
            $table->timestamp('false_positive_at')->nullable();
            $table->foreignId('false_positive_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['occurred_at', 'risk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('web_analytics_events');
    }
};
