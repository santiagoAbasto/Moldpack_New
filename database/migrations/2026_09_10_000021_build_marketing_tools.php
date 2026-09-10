<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('seo_keywords', 500)->nullable()->after('seo_description');
            $table->string('canonical_url', 500)->nullable()->after('seo_keywords');
            $table->string('og_title', 160)->nullable()->after('canonical_url');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('seo_image', 500)->nullable()->after('og_description');
            $table->boolean('noindex')->default(false)->after('seo_image');
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 254)->unique();
            $table->string('name', 120)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->uuid('unsubscribe_token')->unique();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('subject', 180);
            $table->string('preheader', 240)->nullable();
            $table->longText('body');
            $table->string('image_path', 500)->nullable();
            $table->string('action_label', 80)->nullable();
            $table->string('action_url', 500)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('newsletter_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('newsletter_subscribers')->cascadeOnDelete();
            $table->string('status', 20)->default('queued')->index();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'subscriber_id']);
        });

        DB::table('pages')->updateOrInsert(
            ['slug' => 'donde-comprar'],
            ['name' => 'Dónde comprar', 'seo_title' => 'Dónde comprar Moldpack | Distribuidores en Argentina', 'seo_description' => 'Encontrá el punto de venta Moldpack más cercano, consultá direcciones y obtené indicaciones para llegar.', 'is_published' => true, 'show_on_home' => false, 'created_at' => now(), 'updated_at' => now()],
        );

        $contact = json_decode(DB::table('site_settings')->where('key', 'contact')->value('value') ?: '[]', true);
        $contact['intro'] = $contact['intro'] ?? 'Para mayor información, no dude en contactarse mediante el siguiente formulario, o a través de nuestras vías de comunicación.';
        $contact['city'] = $contact['city'] ?? 'Buenos Aires, Argentina.';
        $contact['maps_url'] = 'https://maps.app.goo.gl/gVUD5k7wC3zZhwbX';
        DB::table('site_settings')->updateOrInsert(['key' => 'contact'], ['value' => json_encode($contact, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);

        $social = json_decode(DB::table('site_settings')->where('key', 'social')->value('value') ?: '[]', true);
        $links = $social['links'] ?? [
            ['id' => 'facebook', 'name' => 'Facebook', 'url' => $social['facebook'] ?? '', 'icon' => 'assets/figma/exact/facebook.svg'],
            ['id' => 'instagram', 'name' => 'Instagram', 'url' => $social['instagram'] ?? '', 'icon' => 'assets/figma/exact/instagram.svg'],
        ];
        DB::table('site_settings')->updateOrInsert(['key' => 'social'], ['value' => json_encode(['links' => $links], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_deliveries');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn(['seo_keywords', 'canonical_url', 'og_title', 'og_description', 'seo_image', 'noindex']);
        });
    }
};
