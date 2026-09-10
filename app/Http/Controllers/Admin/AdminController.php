<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\ContactInquiry;
use App\Models\Media;
use App\Models\Page;
use App\Models\Section;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Cliente;
use App\Models\ClientOrder;
use App\Models\ClientInvoice;
use App\Models\ClientPaymentReport;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterCampaign;
use App\Services\ProductRelationService;
use App\Services\ProductTaxonomyService;
use App\Support\SafeSvg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __construct(
        private readonly ProductRelationService $relations,
        private readonly ProductTaxonomyService $taxonomy,
    ) {}

    public function index(): Response
    {
        $pages = Page::with(['sections.items.media', 'sections.media'])->orderBy('id')->get();
        $catalog = $pages->firstWhere('slug', 'productos')?->sections
            ->firstWhere('type', 'products')?->items ?? collect();
        $productSection = $pages->firstWhere('slug', 'productos')?->sections->firstWhere('type', 'products');

        if ($productSection) {
            $sectionSettings = $productSection->settings ?? [];
            $sectionSettings['families'] = $this->taxonomy->families($catalog, $sectionSettings['families'] ?? []);
            $productSection->setAttribute('settings', $sectionSettings);
        }

        $catalog->each(function (ContentItem $product) use ($catalog): void {
            $settings = $product->settings ?? [];
            $manual = array_key_exists('related_manual', $settings)
                ? (bool) $settings['related_manual']
                : ! empty($settings['related_ids']);

            if (! $manual) {
                $settings['related_ids'] = $this->relations->suggest($product, $catalog)->pluck('id')->all();
            }

            $settings['related_source'] = $manual ? 'manual' : 'automatic';
            $product->setAttribute('settings', $settings);
        });

        return Inertia::render('Admin/Cms', [
            'pages' => $pages,
            'sectionTypes' => $this->types(),
            'recommendations' => $this->recommendations(),
            'siteSettings' => SiteSetting::query()->get()->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->value]),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'is_admin', 'created_at']),
            'contactInquiries' => ContactInquiry::query()->latest()->paginate(50),
            'newsletter' => [
                'subscribers' => NewsletterSubscriber::query()->latest('subscribed_at')->get(),
                'campaigns' => NewsletterCampaign::query()->latest()->limit(30)->get(),
            ],
            'commerce' => [
                'clients' => Cliente::query()->withCount('orders')->latest()->limit(100)->get(),
                'orders' => ClientOrder::query()->with(['cliente', 'items'])->latest()->limit(30)->get(),
                'invoices' => ClientInvoice::query()->with('order.cliente')->latest()->limit(50)->get(),
                'payments' => ClientPaymentReport::query()->with('cliente')->latest()->limit(100)->get(),
                'totals' => ['clients'=>Cliente::count(),'orders'=>ClientOrder::count(),'invoices'=>ClientInvoice::count()],
                'products' => $catalog->map(fn (ContentItem $product) => ['id'=>$product->id,'name'=>$product->title,'sku'=>data_get($product->settings,'code'),'stock'=>(int) data_get($product->settings,'stock',0),'price'=>(float) data_get($product->settings,'price',0)]),
            ],
        ]);
    }

    public function savePage(Request $request, Page $page): RedirectResponse
    {
        $page->update($request->validate([
            'name' => ['required', 'max:120'],
            'slug' => ['required', 'max:120', Rule::unique('pages')->ignore($page)],
            'seo_title' => ['nullable', 'max:160'],
            'seo_description' => ['nullable', 'max:320'],
            'seo_keywords' => ['nullable', 'max:500'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:500'],
            'og_title' => ['nullable', 'max:160'],
            'og_description' => ['nullable', 'max:320'],
            'seo_image' => ['nullable', 'max:500'],
            'noindex' => ['boolean'],
            'is_published' => ['boolean'],
            'show_on_home' => ['boolean'],
        ]));

        return back()->with('success', 'Ajustes de la página guardados.');
    }

    public function uploadSeoImage(Request $request, Page $page): RedirectResponse
    {
        $file = $request->validate(['image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120']])['image'];
        if ($page->seo_image && str_starts_with($page->seo_image, 'storage/seo/')) Storage::disk('public')->delete(substr($page->seo_image, 8));
        $page->update(['seo_image' => 'storage/'.$file->store('seo', 'public')]);
        return back()->with('success', 'Imagen SEO actualizada.');
    }

    public function createSection(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate(['type' => ['required', Rule::in(array_keys($this->types()))], 'title' => ['nullable', 'max:180']]);
        $data['sort_order'] = ($page->sections()->max('sort_order') ?? -1) + 1;
        $page->sections()->create($data);
        return back()->with('success', 'Sección creada.');
    }

    public function saveSection(Request $request, Section $section): RedirectResponse
    {
        $section->update($request->validate([
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'eyebrow' => ['nullable', 'max:100'],
            'title' => ['nullable', 'max:180'],
            'body' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'sort_order' => ['integer', 'min:0'],
            'is_visible' => ['boolean'],
        ]));
        return back()->with('success', 'Cambios guardados correctamente.');
    }

    public function deleteSection(Section $section): RedirectResponse
    {
        $section->delete();
        return back()->with('success', 'Sección eliminada.');
    }

    public function createItem(Request $request, Section $section): RedirectResponse
    {
        $data = $request->validate(['title' => ['nullable', 'max:180']]);
        $data['sort_order'] = ($section->items()->max('sort_order') ?? -1) + 1;
        $section->items()->create($data);
        return back()->with('success', 'Elemento agregado.');
    }

    public function saveItem(Request $request, ContentItem $item): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'max:180'],
            'subtitle' => ['nullable', 'max:180'],
            'body' => ['nullable', 'string'],
            'label' => ['nullable', 'max:100'],
            'url' => ['nullable', 'max:500', 'not_regex:/^\s*(javascript|vbscript|data|file)\s*:/i'],
            'settings' => ['nullable', 'array'],
            'sort_order' => ['integer', 'min:0'],
            'is_visible' => ['boolean'],
        ]);
        if ($item->section?->type === 'products') {
            $settings = $data['settings'] ?? $item->settings ?? [];
            $settings['slug'] = $settings['slug'] ?? str($data['title'] ?: 'producto-'.$item->id)->ascii()->slug()->toString();
            $data['settings'] = $settings;
            $data['url'] = '/productos/'.$settings['slug'];
        }
        if ($item->section?->type === 'news') {
            $settings = $data['settings'] ?? $item->settings ?? [];
            $settings['slug'] = $settings['slug'] ?? Str::slug($data['title'] ?: 'novedad').'-'.$item->id;
            $data['settings'] = $settings;
            $data['url'] = '/novedades/'.$settings['slug'];
        }
        $item->update($data);
        return back()->with('success', 'Elemento actualizado.');
    }

    public function deleteItem(ContentItem $item): RedirectResponse
    {
        $item->delete();
        return back()->with('success', 'Elemento eliminado.');
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'owner_type' => ['required', Rule::in(['section', 'item'])],
            'owner_id' => ['required', 'integer'],
            'kind' => ['required', Rule::in(['image', 'video', 'youtube'])],
            'file' => ['required_unless:kind,youtube', 'file', 'max:102400'],
            'url' => ['required_if:kind,youtube', 'nullable', 'url', 'max:500'],
            'alt' => ['nullable', 'max:180'],
            'caption' => ['nullable', 'max:500'],
        ]);

        if ($request->hasFile('file')) {
            $allowed = $data['kind'] === 'image'
                ? ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']
                : ['video/mp4', 'video/webm'];
            abort_unless(in_array($request->file('file')->getMimeType(), $allowed, true), 422, 'Formato de archivo no permitido.');
            if (SafeSvg::isSvg($request->file('file')) && ! SafeSvg::isSafe($request->file('file'))) {
                throw ValidationException::withMessages(['file' => 'El SVG contiene código activo (scripts o eventos). Exportalo nuevamente como SVG simple o usá PNG/WebP.']);
            }
        }

        $owner = $data['owner_type'] === 'section' ? Section::findOrFail($data['owner_id']) : ContentItem::findOrFail($data['owner_id']);
        if ($owner instanceof ContentItem && $owner->section?->type === 'products' && $owner->media()->count() >= 3) {
            throw ValidationException::withMessages(['file' => 'Este producto ya tiene sus 3 medios. Eliminá uno antes de cargar otro.']);
        }
        if ($owner instanceof Section && $owner->type === 'catalog_page' && $owner->media()->count() >= 2) {
            throw ValidationException::withMessages(['file' => 'El catálogo ya tiene la portada y el logo. Eliminá uno antes de reemplazarlo.']);
        }
        $media = new Media([
            'kind' => $data['kind'],
            'url' => $data['url'] ?? null,
            'alt' => $data['alt'] ?? null,
            'caption' => $data['caption'] ?? null,
            'sort_order' => $owner->media()->count(),
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('cms/'.date('Y/m'), 'public');
            $media->path = 'storage/'.$path;
            $media->mime_type = $file->getMimeType();
            if (str_starts_with($media->mime_type, 'image/')) {
                $size = @getimagesize($file->getRealPath());
                $media->width = $size[0] ?? null;
                $media->height = $size[1] ?? null;
            }
        }

        if (($owner instanceof ContentItem && $owner->section?->type === 'hero') || ($owner instanceof Section && in_array($owner->type, ['about_page', 'quality_page'], true))) {
            $owner->media()->get()->each(function (Media $existing): void {
                if ($existing->path && str_starts_with($existing->path, 'storage/')) {
                    Storage::disk('public')->delete(substr($existing->path, 8));
                }
                $existing->delete();
            });
        }

        $owner->media()->save($media);
        return back()->with('success', 'Medio agregado.');
    }

    public function uploadDocument(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'owner_type' => ['required', Rule::in(['section', 'item'])],
            'owner_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        $owner = $data['owner_type'] === 'section'
            ? Section::findOrFail($data['owner_id'])
            : ContentItem::findOrFail($data['owner_id']);
        abort_unless(in_array(($owner instanceof Section ? $owner->type : $owner->section?->type), ['catalog_page', 'quality_page'], true), 422);

        $settings = $owner->settings ?? [];
        if (! empty($settings['document_path']) && str_starts_with($settings['document_path'], 'storage/catalog/')) {
            Storage::disk('public')->delete(substr($settings['document_path'], 8));
        }

        $file = $request->file('file');
        $path = $file->store(($owner instanceof Section && $owner->type === 'quality_page' ? 'quality' : 'catalog').'/'.date('Y/m'), 'public');
        $settings['document_path'] = 'storage/'.$path;
        $settings['document_name'] = $file->getClientOriginalName();
        $settings['document_size'] = $file->getSize();
        $settings['document_pages'] = $this->countPdfPages($file->getRealPath());
        unset($settings['pages'], $settings['display_size']);
        $owner->update(['settings' => $settings]);

        return back()->with('success', 'Documento del catálogo actualizado.');
    }

    private function countPdfPages(string $path): ?int
    {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        preg_match_all('/\/Type\s*\/Page\b/', $contents, $matches);
        return count($matches[0]) ?: null;
    }

    public function deleteMedia(Media $media): RedirectResponse
    {
        if ($media->path && str_starts_with($media->path, 'storage/')) {
            Storage::disk('public')->delete(substr($media->path, 8));
        }
        $media->delete();
        return back()->with('success', 'Medio eliminado.');
    }

    public function saveSetting(Request $request, string $key): RedirectResponse
    {
        $allowed = ['quality', 'stores', 'contact', 'newsletter', 'social'];
        abort_unless(in_array($key, $allowed, true), 404);
        $value = $request->validate(['value' => ['required', 'array']])['value'];
        if ($key === 'contact') {
            $value = validator($value, [
                'intro' => ['required', 'string', 'max:500'],
                'address' => ['required', 'string', 'max:240'],
                'city' => ['required', 'string', 'max:120'],
                'phone' => ['required', 'string', 'max:80'],
                'email' => ['required', 'email:rfc', 'max:254'],
            ])->validate();
            $stored = SiteSetting::where('key', 'contact')->value('value') ?? [];
            $value = array_merge($stored, $value, ['maps_url' => 'https://maps.app.goo.gl/gVUD5k7wC3zZhwbX']);
        }
        if ($key === 'stores') {
            validator($value, [
                'country' => ['required', 'string', 'max:80'],
                'load_more_label' => ['required', 'string', 'max:80'],
                'locations' => ['required', 'array', 'max:100'],
                'locations.*.name' => ['required', 'string', 'max:180'],
                'locations.*.address' => ['required', 'string', 'max:300'],
                'locations.*.phone' => ['nullable', 'string', 'max:100'],
                'locations.*.email' => ['nullable', 'email:rfc', 'max:254'],
                'locations.*.latitude' => ['required', 'numeric', 'between:-90,90'],
                'locations.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            ])->validate();
        }
        SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        return back()->with('success', 'Módulo actualizado.');
    }

    public function geocodeStore(Request $request): JsonResponse
    {
        $address = $request->validate(['address' => ['required', 'string', 'max:300']])['address'];
        $response = Http::timeout(8)->acceptJson()->withHeaders([
            'User-Agent' => 'Moldpack CMS/1.0 (ventas@moldpack.com.ar)',
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'jsonv2',
            'limit' => 1,
            'countrycodes' => 'ar',
        ]);

        $result = $response->successful() ? $response->json('0') : null;
        if (! $result || ! isset($result['lat'], $result['lon'])) {
            return response()->json(['message' => 'No encontramos esa dirección. Revisala o cargá las coordenadas manualmente.'], 422);
        }

        return response()->json([
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'display_name' => $result['display_name'] ?? $address,
        ]);
    }

    public function markInquiryRead(ContactInquiry $inquiry): RedirectResponse
    {
        $inquiry->update(['read_at' => $inquiry->read_at ?: now()]);
        return back()->with('success', 'Consulta marcada como leída.');
    }

    private function normalizeMapEmbedUrl(string $value): string
    {
        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $value, $match)) {
            $value = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
        }

        $value = trim($value);
        $parts = parse_url($value);
        if (($parts['scheme'] ?? null) !== 'https' || ! in_array($parts['host'] ?? '', ['www.google.com', 'google.com', 'maps.google.com'], true) || ! str_starts_with($parts['path'] ?? '', '/maps/embed')) {
            throw ValidationException::withMessages(['value.map_embed_url' => 'Pegá un iframe o enlace de Google Maps Embed válido.']);
        }

        return $value;
    }

    public function createUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'is_admin' => ['boolean'],
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return back()->with('success', 'Usuario creado.');
    }

    public function saveUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:254', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'is_admin' => ['boolean'],
        ]);
        if (blank($data['password'] ?? null)) unset($data['password']);
        else {
            $data['password'] = Hash::make($data['password']);
            // Invalidate "remember me" cookies issued with the old password.
            $user->setRememberToken(Str::random(60));
        }
        $user->update($data);
        return back()->with('success', 'Usuario actualizado.');
    }

    public function deleteUser(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'No podés eliminar tu propia cuenta.');
        $user->delete();
        return back()->with('success', 'Usuario eliminado.');
    }

    private function types(): array
    {
        return ['hero' => 'Sliders', 'categories' => 'Categorías', 'products' => 'Productos destacados', 'about' => 'Nosotros', 'about_page' => 'Página Nosotros', 'quality_page' => 'Página Calidad', 'catalog_page' => 'Página Catálogo', 'news' => 'Novedades', 'rich_text' => 'Texto enriquecido', 'media' => 'Galería o video', 'cta' => 'Banner de catálogo'];
    }

    private function recommendations(): array
    {
        return [
            'hero' => 'Imagen o video horizontal 1367×768. MP4/WebM hasta 50 MB.',
            'categories' => 'Imagen cuadrada 900×900 px, JPG o WebP.',
            'products' => 'Imagen cuadrada de 1200×1200 px en WebP o PNG, máximo 2 MB. Producto completo, centrado, con 8–10% de aire alrededor y fondo blanco o gris muy claro. Evitá textos, marcos y recortes.',
            'about' => 'Fotografía horizontal o cuadrada de al menos 1200 px.',
            'about_page' => 'Fotografía horizontal de al menos 1200×840 px. Se muestra recortada en un marco cuadrado de 600×600 px.',
            'quality_page' => 'Imagen horizontal 1366×430 px. Se recorta en el marco de 601×600 px exactamente como en Figma.',
            'news' => 'Imagen de novedad 784×700 px, JPG o WebP. Evitá texto pequeño en la imagen: la tarjeta recorta a 392×350 px, oscurece en hover y muestra un + al centro.',
            'cta' => 'Banner horizontal de al menos 1600×500 px.',
            'catalog_page' => 'Portada vertical 936×1332 px (proporción 234×333), JPG o WebP. Sin textos agregados fuera del diseño.',
            'media' => 'YouTube, imagen JPG/PNG/WebP/SVG o video MP4/WebM.',
        ];
    }
}
