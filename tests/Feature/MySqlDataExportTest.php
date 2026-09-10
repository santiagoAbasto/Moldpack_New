<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Services\MySqlDataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MySqlDataExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_only_admin_can_download_a_mysql_compatible_data_export(): void
    {
        $this->get('/admin/database/export/mysql')->assertRedirect('/admin/login');

        $admin = User::query()->create(['name' => 'Export Admin', 'email' => 'export@example.com', 'password' => bcrypt('Password-Segura-1'), 'is_admin' => true]);
        Page::query()->create(['name' => "Página de prueba", 'slug' => 'mysql-export-test', 'is_published' => true]);

        $response = $this->actingAs($admin)->get('/admin/database/export/mysql');

        $response->assertOk()->assertHeader('content-type', 'application/sql; charset=utf-8');

        $chunks = [];
        app(MySqlDataExportService::class)->stream(function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });
        $sql = implode('', $chunks);
        $this->assertStringContainsString('SET NAMES utf8mb4;', $sql);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0;', $sql);
        $this->assertStringContainsString('CREATE TABLE `pages`', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `users`', $sql);
        $this->assertStringContainsString('INSERT INTO `pages`', $sql);
        $this->assertStringContainsString('Página de prueba', $sql);
        $this->assertStringContainsString('INSERT INTO `migrations`', $sql);
    }

    public function test_only_admin_can_download_the_exact_local_sqlite_backup(): void
    {
        $this->get('/admin/database/export/sqlite')->assertRedirect('/admin/login');

        $admin = User::query()->create(['name' => 'Backup Admin', 'email' => 'backup@example.com', 'password' => bcrypt('Password-Segura-1'), 'is_admin' => true]);
        $backup = tempnam(sys_get_temp_dir(), 'moldpack-sqlite-');
        $this->assertNotFalse($backup);
        config()->set('database.connections.sqlite.database', $backup);

        try {
            $this->actingAs($admin)
                ->get('/admin/database/export/sqlite')
                ->assertOk()
                ->assertHeader('content-type', 'application/vnd.sqlite3')
                ->assertDownload();
        } finally {
            @unlink($backup);
        }
    }
}
