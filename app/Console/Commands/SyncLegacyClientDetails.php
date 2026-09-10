<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class SyncLegacyClientDetails extends ImportLegacyCatalog
{
    protected $signature = 'commerce:sync-client-details {dump : Ruta absoluta al respaldo SQL} {--dry-run}';
    protected $description = 'Completa perfiles y credenciales recuperables sin reemplazar pedidos ni cambios del CMS nuevo';
    protected array $tables = ['clientes'];

    public function handle(): int
    {
        $path = (string) $this->argument('dump');
        if (! is_readable($path)) { $this->error('No se puede leer el respaldo.'); return self::FAILURE; }
        $rows = $this->readDump($path)['clientes'] ?? [];
        if ($this->option('dry-run')) { $this->info(count($rows).' perfiles disponibles.'); return self::SUCCESS; }

        $updated = 0;
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::transaction(function () use ($chunk, &$updated): void {
                foreach ($chunk as $row) {
                    $updated += DB::table('clientes')->where('id', $row['id'])->update([
                        'first_name' => $row['nombre'] ?: null,
                        'last_name' => $row['apellido'] ?: null,
                        'alternate_email' => $row['emailAux'] ?: null,
                        'document_id' => $row['dni'] ?: null,
                        'delivery_address' => $row['direccionEntrega'] ?: null,
                        'started_on' => $row['fechaInicio'] ?: null,
                        'show_prices' => (bool) ($row['precios'] ?? false),
                        'password_encrypted' => $row['password_encrypted'] ?: null,
                    ]);
                }
            });
        }
        $this->info("{$updated} perfiles completados sin borrar datos actuales.");
        return self::SUCCESS;
    }
}
