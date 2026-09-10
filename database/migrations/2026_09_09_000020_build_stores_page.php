<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $current = DB::table('site_settings')->where('key', 'stores')->first();
        $value = $current ? json_decode($current->value, true) : null;

        if (! $current) {
            DB::table('site_settings')->insert([
                'key' => 'stores',
                'value' => json_encode($this->defaults(), JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif (! is_array($value) || ! isset($value['locations'])) {
            DB::table('site_settings')->where('key', 'stores')->update([
                'value' => json_encode($this->defaults(), JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void {}

    private function defaults(): array
    {
        return [
            'country' => 'Argentina',
            'load_more_label' => 'Cargar más resultados',
            'locations' => [
                ['name' => 'CEM PROVISIONES INDUSTRIALES de Claudio Macoratti', 'address' => 'Av. San Martín 3148, San Lorenzo, Santa Fe, Argentina', 'phone' => '+54 3476-426900', 'email' => 'cem@cemprovin.com.ar', 'latitude' => -32.7411, 'longitude' => -60.7322],
                ['name' => 'CLAUDIO COLEDANI', 'address' => 'Radio Belgrano 3062, Barrio Intersindical, Salta, Argentina', 'phone' => '0387-4243058 / 0387-155-196032', 'email' => 'claudiocoledani@yahoo.com.ar', 'latitude' => -24.7821, 'longitude' => -65.4232],
                ['name' => 'INDAVE PATAGONICO S.A.', 'address' => 'Río Negro 1218, Neuquén, Argentina', 'phone' => '+54 299 422-2780', 'email' => 'info@indavepatagonico.com', 'latitude' => -38.9587, 'longitude' => -68.0592],
                ['name' => 'INDAVE PATAGONICO S.A.', 'address' => 'Avellaneda 612, Bahía Blanca, Argentina', 'phone' => '+54 291 4533466', 'email' => 'rpuente@saippargentina.com.ar', 'latitude' => -38.7183, 'longitude' => -62.2663],
            ],
        ];
    }
};
