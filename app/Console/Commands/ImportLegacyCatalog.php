<?php

namespace App\Console\Commands;

use App\Models\ContentItem;
use App\Models\Media;
use App\Models\Page;
use App\Models\Section;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportLegacyCatalog extends Command
{
    protected $signature = 'catalog:import-legacy {dump : Ruta absoluta al archivo SQL} {--dry-run : Solo analizar, sin modificar datos}';

    protected $description = 'Reemplaza el catálogo CMS con productos, familias y presentaciones de la base histórica';

    /** @var array<int, string> */
    protected array $tables = [
        'categorias',
        'familia_productos',
        'presentacion_relacions',
        'productos',
        'producto_relacions',
    ];

    public function handle(): int
    {
        $path = (string) $this->argument('dump');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("No se puede leer el dump: {$path}");

            return self::FAILURE;
        }

        $this->info('Analizando catálogo histórico…');
        $data = $this->readDump($path);

        $this->table(
            ['Tabla', 'Registros'],
            collect($this->tables)->map(fn (string $table) => [$table, count($data[$table])])->all()
        );

        if ($data['productos'] === []) {
            $this->error('El dump no contiene productos. No se modificó la base actual.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Validación completada. No se modificaron datos.');

            return self::SUCCESS;
        }

        $section = Page::query()
            ->where('slug', 'productos')
            ->firstOrFail()
            ->sections()
            ->where('type', 'products')
            ->firstOrFail();

        $categories = collect($data['categorias'])->keyBy(fn (array $row) => (string) $row['id']);
        $families = collect($data['familia_productos'])->keyBy(fn (array $row) => (string) $row['id']);
        $presentations = collect($data['presentacion_relacions'])->groupBy(fn (array $row) => (string) $row['producto_id']);
        $legacyRelations = collect($data['producto_relacions'])->groupBy(fn (array $row) => (string) $row['producto_id']);
        $createdByLegacyId = [];

        DB::transaction(function () use (
            $section,
            $data,
            $categories,
            $families,
            $presentations,
            $legacyRelations,
            &$createdByLegacyId
        ): void {
            $section->items()->each(function (ContentItem $item): void {
                $item->media()->delete();
                $item->delete();
            });

            foreach ($data['productos'] as $index => $product) {
                $legacyId = (string) $product['id'];
                $family = $families->get((string) ($product['subcategorias_id'] ?? ''));
                $category = $categories->get((string) ($product['categorias_id'] ?? ''))
                    ?? $categories->get((string) ($family['categorias_id'] ?? ''));
                $productPresentations = $presentations->get($legacyId, collect());
                $presentationNames = $productPresentations
                    ->pluck('presentacion')
                    ->filter()
                    ->map(fn ($value) => trim((string) $value))
                    ->unique()
                    ->values();
                $codes = $productPresentations
                    ->pluck('codigo')
                    ->prepend($product['codigo'] ?? null)
                    ->filter()
                    ->map(fn ($value) => trim((string) $value))
                    ->unique()
                    ->values();
                $categoryName = trim((string) ($category['nombre'] ?? ''));
                $familyName = trim((string) ($family['nombre'] ?? ''));
                $subtitle = collect([$categoryName, $familyName])->filter()->unique()->implode(' / ');
                $name = trim((string) ($product['nombre'] ?? 'Producto')) ?: 'Producto';
                $slug = Str::slug($name).'-'.$legacyId;
                $legacyCopy = $this->extractLegacyCopy($product['descripcion'] ?? null);

                $item = $section->items()->create([
                    'title' => $name,
                    'subtitle' => $subtitle,
                    'body' => $product['descripcion'] ?: null,
                    'label' => $presentationNames->implode(' | '),
                    'url' => '/productos/'.$slug,
                    'settings' => [
                        'legacy_id' => (int) $legacyId,
                        'slug' => $slug,
                        'code' => $codes->first(),
                        'codes' => $codes->all(),
                        'brand' => 'MoldPack',
                        'category' => $categoryName,
                        'family' => $familyName,
                        'subcategory' => $familyName,
                        'design' => $legacyCopy['design'],
                        'food_safe' => $legacyCopy['food_safe'],
                        'quality' => $legacyCopy['quality'],
                        'featured_home' => (int) ($product['destacado'] ?? 0) === 1,
                        'legacy_image' => $product['imagen'] ?: null,
                        'legacy_gallery' => $product['galeria'] ?: null,
                        'presentations' => $productPresentations->map(fn (array $row) => [
                            'name' => $row['presentacion'] ?: null,
                            'code' => $row['codigo'] ?: null,
                            'price' => $row['precio'] !== null ? (float) $row['precio'] : null,
                            'stock' => $row['stock'] ?? null,
                            'active' => $row['activa'] !== null ? (bool) $row['activa'] : null,
                        ])->values()->all(),
                        'related_ids' => [],
                        'related_manual' => false,
                    ],
                    'sort_order' => is_numeric($product['orden'] ?? null) ? (int) $product['orden'] : $index,
                    'is_visible' => (int) ($product['activa'] ?? 0) === 1,
                ]);

                $createdByLegacyId[$legacyId] = $item->id;

                foreach ($this->extractImagePaths($product) as $mediaIndex => $mediaPath) {
                    $item->media()->create([
                        'kind' => 'image',
                        'disk' => 'public',
                        'path' => $mediaPath,
                        'alt' => $name,
                        'sort_order' => $mediaIndex,
                    ]);
                }
            }

            foreach ($createdByLegacyId as $legacyId => $itemId) {
                $relatedIds = $legacyRelations->get($legacyId, collect())
                    ->pluck('relacion_id')
                    ->map(fn ($relatedLegacyId) => $createdByLegacyId[(string) $relatedLegacyId] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                if ($relatedIds !== []) {
                    $item = ContentItem::query()->findOrFail($itemId);
                    $settings = $item->settings ?? [];
                    $settings['related_ids'] = $relatedIds;
                    $settings['related_manual'] = true;
                    $item->update(['settings' => $settings]);
                }
            }
        });

        $active = ContentItem::query()->where('section_id', $section->id)->where('is_visible', true)->count();
        $media = Media::query()->where('mediable_type', ContentItem::class)
            ->whereIn('mediable_id', array_values($createdByLegacyId))->count();

        $this->newLine();
        $this->info(count($createdByLegacyId)." productos importados ({$active} publicados, {$media} referencias de imagen). ");

        return self::SUCCESS;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    protected function readDump(string $path): array
    {
        $result = array_fill_keys($this->tables, []);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("No se pudo abrir {$path}");
        }

        $statement = '';
        $capturing = false;

        while (($line = fgets($handle)) !== false) {
            if (! $capturing) {
                foreach ($this->tables as $table) {
                    if (str_starts_with($line, "INSERT INTO `{$table}`")) {
                        $capturing = true;
                        $statement = $line;
                        break;
                    }
                }
            } else {
                $statement .= $line;
            }

            if ($capturing && str_ends_with(rtrim($line), ';')) {
                [$table, $rows] = $this->parseInsert($statement);
                array_push($result[$table], ...$rows);
                $statement = '';
                $capturing = false;
            }
        }

        fclose($handle);

        return $result;
    }

    /** @return array{0: string, 1: array<int, array<string, mixed>>} */
    protected function parseInsert(string $statement): array
    {
        if (! preg_match('/^INSERT INTO `([^`]+)` \(([^)]+)\) VALUES\s*/s', $statement, $match)) {
            throw new RuntimeException('No se pudo interpretar una sentencia INSERT del catálogo.');
        }

        $table = $match[1];
        $columns = array_map(fn (string $column) => trim($column, " `\t\n\r"), explode(',', $match[2]));
        $values = substr($statement, strlen($match[0]));
        $values = preg_replace('/;\s*$/', '', $values) ?? $values;
        $tuples = $this->splitSql($values, true);
        $rows = [];

        foreach ($tuples as $tuple) {
            $fields = $this->splitSql(substr($tuple, 1, -1), false);

            if (count($fields) !== count($columns)) {
                throw new RuntimeException("Cantidad de columnas inválida en {$table}: ".count($fields).' de '.count($columns));
            }

            $rows[] = array_combine($columns, array_map([$this, 'decodeSqlValue'], $fields));
        }

        return [$table, $rows];
    }

    /** @return array<int, string> */
    protected function splitSql(string $input, bool $tuples): array
    {
        $parts = [];
        $buffer = '';
        $quoted = false;
        $escaped = false;
        $depth = 0;
        $length = strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];
            $buffer .= $char;

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($quoted && $char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === "'") {
                $quoted = ! $quoted;
                continue;
            }

            if ($quoted) {
                continue;
            }

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            $separator = $char === ',' && ($tuples ? $depth === 0 : true);

            if ($separator) {
                $parts[] = trim(substr($buffer, 0, -1));
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $parts[] = trim($buffer);
        }

        return $parts;
    }

    protected function decodeSqlValue(string $value): mixed
    {
        $value = trim($value);

        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            $value = substr($value, 1, -1);

            return strtr($value, [
                '\\0' => "\0",
                '\\n' => "\n",
                '\\r' => "\r",
                '\\Z' => "\x1a",
                "\\'" => "'",
                '\\"' => '"',
                '\\\\' => '\\',
            ]);
        }

        return is_numeric($value) ? $value + 0 : $value;
    }

    /** @return array<int, string> */
    private function extractImagePaths(array $product): array
    {
        $paths = [];

        foreach ([$product['imagen'] ?? null, $product['galeria'] ?? null] as $rawPath) {
            if (! is_string($rawPath) || trim($rawPath) === '') {
                continue;
            }

            $decoded = json_decode($rawPath, true);
            $candidates = is_array($decoded) ? $decoded : [$rawPath];

            foreach ($candidates as $candidate) {
                if (! is_string($candidate) || trim($candidate) === '') {
                    continue;
                }

                $normalized = ltrim(preg_replace('#^public/#', '', trim($candidate)) ?? trim($candidate), '/');
                $paths[] = str_starts_with($normalized, 'storage/') ? $normalized : 'storage/'.$normalized;
            }
        }

        return array_values(array_unique($paths));
    }

    /** @return array{design: string, food_safe: string, quality: string} */
    private function extractLegacyCopy(?string $html): array
    {
        $text = html_entity_decode(strip_tags((string) $html, '<br>'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<br\s*\/?\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/[\t ]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{2,}/', "\n", $text) ?? $text;
        $text = trim($text);

        $extract = static function (string $source, string $start, ?string $end = null): string {
            $startPosition = mb_stripos($source, $start);

            if ($startPosition === false) {
                return '';
            }

            $value = mb_substr($source, $startPosition + mb_strlen($start));

            if ($end !== null && ($endPosition = mb_stripos($value, $end)) !== false) {
                $value = mb_substr($value, 0, $endPosition);
            }

            return trim($value, " :\n\r\t");
        };

        $design = $extract($text, 'Diseño', 'Envase apto para alimentos');
        $foodSafe = $extract($text, 'Envase apto para alimentos', 'Calidad');
        $quality = $extract($text, 'Calidad');

        return [
            'design' => $design !== '' ? $design : $text,
            'food_safe' => $foodSafe,
            'quality' => $quality,
        ];
    }
}
