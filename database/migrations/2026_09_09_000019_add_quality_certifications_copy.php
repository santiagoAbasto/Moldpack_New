<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('sections')->where('type', 'quality_page')->where('body', 'not like', '%R.N.E.%')->update([
            'body' => '<p>En Moldpack convertimos la pasión por la cocina en productos seguros, prácticos y de excelencia. Contamos con certificados R.N.E. y R.N.P.A.</p>',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('sections')->where('type', 'quality_page')->update([
            'body' => '<p>En Moldpack convertimos la pasión por la cocina en productos seguros, prácticos y de excelencia.</p>',
            'updated_at' => now(),
        ]);
    }
};
