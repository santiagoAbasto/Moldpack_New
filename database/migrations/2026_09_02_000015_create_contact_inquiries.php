<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 254)->index();
            $table->string('phone', 60);
            $table->string('company', 160)->nullable();
            $table->text('message');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        $contact = DB::table('site_settings')->where('key', 'contact')->value('value');
        $value = $contact ? json_decode($contact, true) : [];
        $value['map_embed_url'] = $value['map_embed_url'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3967.7656349128115!2d-58.613389219219584!3d-34.48364573183678!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bca4cd16c9fcf7%3A0x46425f36c28d01be!2sMOLDPACK!5e0!3m2!1ses!2sbo!4v1788402231251!5m2!1ses!2sbo';
        DB::table('site_settings')->updateOrInsert(['key' => 'contact'], ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_inquiries');
    }
};
