<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'site_name' => ['Portal Associação Tech PG', 'text', 'identity'],
            'site_tagline' => ['Portal Associativo', 'text', 'identity'],
            'institutional_text' => ['Uma base institucional para conectar talentos, empresas, eventos e iniciativas de tecnologia em Ponta Grossa.', 'textarea', 'identity'],
            'ecosystem_years' => ['10', 'number', 'identity'],
            'email' => ['contato@techpg.org.br', 'email', 'contact'],
            'phone' => ['(42) 99999-2026', 'text', 'contact'],
            'whatsapp' => ['(42) 99999-2026', 'text', 'contact'],
            'cnpj' => ['', 'text', 'contact'],
            'city' => ['Ponta Grossa, PR', 'text', 'contact'],
            'address' => ['', 'text', 'contact'],
            'linkedin_url' => ['https://www.linkedin.com', 'url', 'social'],
            'instagram_url' => ['https://www.instagram.com', 'url', 'social'],
            'whatsapp_url' => ['https://wa.me/5542999992026', 'url', 'social'],
        ];

        foreach ($settings as $key => [$value, $type, $group]) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => $type, 'group' => $group]
            );
        }
    }
}
