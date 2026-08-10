<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(PortalDemoSeeder::class);

        $email = env('ADMIN_EMAIL', 'admin@techpg.local');
        $existente = User::query()->where('email', $email)->first();

        if ($existente) {
            $this->command?->info("Administrador {$email} já existe; senha preservada.");

            return;
        }

        /*
         * Sem senha padrao no codigo.
         *
         * Antes havia um valor fixo aqui como fallback. Num repositorio
         * compartilhado, isso e a senha do administrador publicada em texto
         * puro — qualquer pessoa com acesso ao codigo entra no painel.
         *
         * Agora: usa ADMIN_PASSWORD do .env quando definida; senao gera uma
         * senha aleatoria e a imprime uma unica vez, no console.
         */
        $senha = env('ADMIN_PASSWORD') ?: Str::password(16);

        User::create([
            'name' => env('ADMIN_NAME', 'Administrador Tech PG'),
            'email' => $email,
            'password' => $senha,
            'role' => UserRole::Admin,
        ]);

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn('Administrador criado com senha gerada. Anote agora, ela não será mostrada de novo:');
            $this->command?->line("  e-mail: {$email}");
            $this->command?->line("  senha:  {$senha}");
        }
    }
}
