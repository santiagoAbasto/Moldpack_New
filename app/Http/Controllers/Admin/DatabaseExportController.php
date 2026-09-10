<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MySqlDataExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseExportController extends Controller
{
    public function mysql(Request $request, MySqlDataExportService $exporter): StreamedResponse
    {
        $filename = 'moldpack-data-'.now()->format('Y-m-d-His').'.sql';

        return response()->streamDownload(
            fn () => $exporter->stream(fn (string $chunk) => print $chunk, $request->boolean('include_operational')),
            $filename,
            ['Content-Type' => 'application/sql; charset=utf-8', 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    /**
     * Exact local backup for safekeeping before a production migration.
     * It is intentionally available only while this installation uses SQLite.
     */
    public function sqlite(): BinaryFileResponse
    {
        abort_unless(config('database.default') === 'sqlite', 404);

        $database = config('database.connections.sqlite.database');
        abort_unless(is_string($database) && is_file($database), 404);

        return response()->download(
            $database,
            'moldpack-local-complete-'.now()->format('Y-m-d-His').'.sqlite',
            ['Content-Type' => 'application/vnd.sqlite3', 'X-Content-Type-Options' => 'nosniff'],
        );
    }
}
