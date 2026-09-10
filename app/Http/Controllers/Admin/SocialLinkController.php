<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\SafeSvg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SocialLinkController extends Controller
{
    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:80'],
            'url' => ['required', 'url:http,https', 'max:500'],
            'icon' => ['nullable', 'file', 'mimes:svg,png,webp', 'max:2048'],
        ]);
        if ($request->hasFile('icon') && SafeSvg::isSvg($request->file('icon')) && ! SafeSvg::isSafe($request->file('icon'))) {
            throw ValidationException::withMessages(['icon' => 'El SVG contiene código activo (scripts o eventos). Usá un SVG simple, PNG o WebP.']);
        }
        $setting = SiteSetting::firstOrCreate(['key' => 'social'], ['value' => ['links' => []]]);
        $value = $setting->value ?: ['links' => []];
        $id = $data['id'] ?: Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $existing = collect($value['links'] ?? [])->firstWhere('id', $id);
        $icon = $existing['icon'] ?? null;
        if ($request->hasFile('icon')) {
            if ($icon && str_starts_with($icon, 'storage/social/')) Storage::disk('public')->delete(substr($icon, 8));
            $icon = 'storage/'.$request->file('icon')->store('social', 'public');
        }
        if (! $icon) return back()->with('error', 'Cargá un icono para esta red social.');
        $link = ['id' => $id, 'name' => $data['name'], 'url' => $data['url'], 'icon' => $icon];
        $links = collect($value['links'] ?? [])->reject(fn ($item) => ($item['id'] ?? null) === $id)->push($link)->values()->all();
        $setting->update(['value' => ['links' => $links]]);
        return back()->with('success', 'Red social actualizada en el footer.');
    }

    public function delete(string $id): RedirectResponse
    {
        $setting = SiteSetting::where('key', 'social')->firstOrFail();
        $value = $setting->value ?: ['links' => []];
        $removed = collect($value['links'] ?? [])->firstWhere('id', $id);
        if (($removed['icon'] ?? null) && str_starts_with($removed['icon'], 'storage/social/')) Storage::disk('public')->delete(substr($removed['icon'], 8));
        $setting->update(['value' => ['links' => collect($value['links'] ?? [])->reject(fn ($item) => ($item['id'] ?? null) === $id)->values()->all()]]);
        return back()->with('success', 'Red social eliminada del footer.');
    }
}
