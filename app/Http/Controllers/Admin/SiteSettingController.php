<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    public function edit()
    {
        $this->ensureDefaults();

        return view('portal.admin.cms.settings', [
            'settings' => SiteSetting::allSettings(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['nullable', 'string', 'max:160'],
            'institutional_text' => ['nullable', 'string', 'max:900'],
            'ecosystem_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'cnpj' => ['nullable', 'string', 'max:24'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:180'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,ico', 'max:1024'],
        ]);

        foreach ($this->textFields() as $field => $meta) {
            SiteSetting::setValue($field, $data[$field] ?? null, $meta['type'], $meta['group']);
        }

        if ($request->hasFile('logo')) {
            SiteSetting::setValue('logo_path', $request->file('logo')->store('site', 'public'), 'image', 'identity');
        }

        if ($request->hasFile('favicon')) {
            SiteSetting::setValue('favicon_path', $request->file('favicon')->store('site', 'public'), 'image', 'identity');
        }

        return redirect()->route('admin.cms.settings.edit')->with('status', 'Configuracoes do portal atualizadas com sucesso.');
    }

    private function ensureDefaults(): void
    {
        foreach ($this->defaults() as $key => $setting) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $setting['value'], 'type' => $setting['type'], 'group' => $setting['group']]
            );
        }
    }

    private function textFields(): array
    {
        return collect($this->defaults())
            ->except(['logo_path', 'favicon_path'])
            ->map(fn ($setting) => ['type' => $setting['type'], 'group' => $setting['group']])
            ->all();
    }

    private function defaults(): array
    {
        return [
            'site_name' => ['value' => 'Portal Associacao Tech PG', 'type' => 'text', 'group' => 'identity'],
            'site_tagline' => ['value' => 'Portal Associativo', 'type' => 'text', 'group' => 'identity'],
            'institutional_text' => ['value' => 'Uma base institucional para conectar talentos, empresas, eventos e iniciativas de tecnologia em Ponta Grossa.', 'type' => 'textarea', 'group' => 'identity'],
            'ecosystem_years' => ['value' => '10', 'type' => 'number', 'group' => 'identity'],
            'email' => ['value' => 'contato@techpg.org.br', 'type' => 'email', 'group' => 'contact'],
            'phone' => ['value' => '(42) 99999-2026', 'type' => 'text', 'group' => 'contact'],
            'whatsapp' => ['value' => '(42) 99999-2026', 'type' => 'text', 'group' => 'contact'],
            'cnpj' => ['value' => '', 'type' => 'text', 'group' => 'contact'],
            'city' => ['value' => 'Ponta Grossa, PR', 'type' => 'text', 'group' => 'contact'],
            'address' => ['value' => '', 'type' => 'text', 'group' => 'contact'],
            'linkedin_url' => ['value' => 'https://www.linkedin.com', 'type' => 'url', 'group' => 'social'],
            'instagram_url' => ['value' => 'https://www.instagram.com', 'type' => 'url', 'group' => 'social'],
            'whatsapp_url' => ['value' => 'https://wa.me/5542999992026', 'type' => 'url', 'group' => 'social'],
            'logo_path' => ['value' => '', 'type' => 'image', 'group' => 'identity'],
            'favicon_path' => ['value' => '', 'type' => 'image', 'group' => 'identity'],
        ];
    }
}
