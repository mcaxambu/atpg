<?php

namespace Database\Seeders;

use App\Enums\ModerationStatus;
use App\Models\CmsPage;
use App\Models\Company;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use App\Models\Specialty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PortalDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Este seeder cria empresas e membros ficticios. Rodar em producao
        // sujaria o diretorio publico com cadastros que nao existem.
        // Para limpar o que ja entrou: php artisan portal:limpar-demo
        if (app()->isProduction() && ! $this->command?->option('force')) {
            $this->command?->warn('PortalDemoSeeder ignorado: dados de demonstração não entram em produção.');

            return;
        }

        $specialtyNames = [
            'Desenvolvimento de Software',
            'Desenvolvimento Web',
            'Infraestrutura',
            'Hospedagem de Sites',
            'Streaming',
            'Segurança da Informação',
            'Inteligência Artificial',
            'Marketing Digital',
            'Design UX/UI',
            'Automação',
            'Suporte Técnico',
            'Redes',
            'Cloud Computing',
            'SEO',
            'Scrum',
            'Educação Tecnológica',
            'React',
            'Python',
            'Firewall',
            'Tráfego Pago',
            'Gestão de Projetos',
            'Produtos Digitais',
            'Manutenção',
            'Cursos',
            'Capacitação',
        ];

        $specialties = collect($specialtyNames)->mapWithKeys(fn (string $name) => [
            $name => Specialty::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]),
        ]);

        $companyRows = [
            ['name' => 'HostPG', 'segment' => 'Hospedagem e infraestrutura', 'city' => 'Ponta Grossa, PR', 'description' => 'Hospedagem gerenciada, streaming e infraestrutura cloud para empresas regionais.', 'initials' => 'HP'],
            ['name' => 'CodeLab PG', 'segment' => 'Desenvolvimento de sistemas', 'city' => 'Ponta Grossa, PR', 'description' => 'Software sob medida, plataformas web e times dedicados para produtos digitais.', 'initials' => 'CL'],
            ['name' => 'SecureNet', 'segment' => 'Consultoria em TI', 'city' => 'Ponta Grossa, PR', 'description' => 'Segurança da informação, redes, firewall e governança para ambientes corporativos.', 'initials' => 'SN'],
            ['name' => 'Agencia Pixel', 'segment' => 'Marketing e tecnologia', 'city' => 'Ponta Grossa, PR', 'description' => 'Estratégia digital, SEO, mídia paga e presença online para marcas locais.', 'initials' => 'PX'],
            ['name' => 'AI Solutions PG', 'segment' => 'Automação comercial', 'city' => 'Ponta Grossa, PR', 'description' => 'Automações, IA aplicada e integrações inteligentes para processos de negocio.', 'initials' => 'AI'],
            ['name' => 'TechFlow', 'segment' => 'Consultoria em TI', 'city' => 'Ponta Grossa, PR', 'description' => 'Gestão de produtos, processos digitais e squads para empresas em crescimento.', 'initials' => 'TF'],
            ['name' => 'DS Tecnologia', 'segment' => 'Suporte e infraestrutura', 'city' => 'Ponta Grossa, PR', 'description' => 'Suporte técnico, manutenção preventiva e infraestrutura para empresas locais.', 'initials' => 'DS'],
            ['name' => 'EducaTech PG', 'segment' => 'Educação tecnológica', 'city' => 'Ponta Grossa, PR', 'description' => 'Cursos, treinamentos e trilhas de capacitação para o ecossistema tech regional.', 'initials' => 'ET'],
        ];

        $companies = collect($companyRows)->mapWithKeys(fn (array $company) => [
            $company['name'] => Company::updateOrCreate(
                ['slug' => Str::slug($company['name'])],
                $company + [
                    'slug' => Str::slug($company['name']),
                    'status' => ModerationStatus::Approved,
                    'is_active' => true,
                    'reviewed_at' => now(),
                    'registration_source' => 'demo',
                ]
            ),
        ]);

        $members = [
            ['name' => 'Marcelo Caxambu', 'role' => 'Especialista em Infraestrutura e Streaming', 'company' => 'HostPG', 'experience_years' => 15, 'avatar_initials' => 'MC', 'avatar_color' => '#0f4c81', 'specialties' => ['Hospedagem de Sites', 'Streaming', 'Infraestrutura', 'Cloud Computing'], 'summary' => 'Profissional com atuação em hospedagem, transmissao ao vivo e ambientes de alta disponibilidade para empresas locais e eventos regionais.'],
            ['name' => 'Ana Ribeiro', 'role' => 'Desenvolvedora Front-end Senior', 'company' => 'CodeLab PG', 'experience_years' => 8, 'avatar_initials' => 'AR', 'avatar_color' => '#1d9bf0', 'specialties' => ['Desenvolvimento Web', 'Design UX/UI', 'React'], 'summary' => 'Cria interfaces web responsivas para produtos digitais, com foco em performance, acessibilidade e experiência de usuário.'],
            ['name' => 'Rafael Martins', 'role' => 'Consultor de Segurança da Informação', 'company' => 'SecureNet', 'experience_years' => 12, 'avatar_initials' => 'RM', 'avatar_color' => '#16324f', 'specialties' => ['Segurança da Informação', 'Redes', 'Firewall'], 'summary' => 'Atua em protecao de redes corporativas, políticas de segurança e resposta a incidentes para pequenas e médias empresas.'],
            ['name' => 'Camila Souza', 'role' => 'Estrategista de Marketing Digital', 'company' => 'Agencia Pixel', 'experience_years' => 7, 'avatar_initials' => 'CS', 'avatar_color' => '#0ea5a8', 'specialties' => ['Marketing Digital', 'SEO', 'Tráfego Pago'], 'summary' => 'Planeja campanhas digitais e estratégias de crescimento para empresas de tecnologia, educação e serviços profissionais.'],
            ['name' => 'Bruno Almeida', 'role' => 'Engenheiro de IA e Automação', 'company' => 'AI Solutions PG', 'experience_years' => 6, 'avatar_initials' => 'BA', 'avatar_color' => '#2563eb', 'specialties' => ['Inteligência Artificial', 'Python', 'Automação'], 'summary' => 'Desenvolve automações inteligentes, agentes internos e modelos aplicados a processos administrativos e operacionais.'],
            ['name' => 'Fernanda Lima', 'role' => 'Product Manager', 'company' => 'TechFlow', 'experience_years' => 10, 'avatar_initials' => 'FL', 'avatar_color' => '#334155', 'specialties' => ['Gestão de Projetos', 'Produtos Digitais', 'Scrum'], 'summary' => 'Conecta estratégia, produto e operação para transformar ideias em plataformas digitais sustentáveis.'],
            ['name' => 'Diego Santos', 'role' => 'Analista de Suporte e Infraestrutura', 'company' => 'DS Tecnologia', 'experience_years' => 14, 'avatar_initials' => 'DS', 'avatar_color' => '#0369a1', 'specialties' => ['Suporte Técnico', 'Manutenção', 'Infraestrutura'], 'summary' => 'Atende empresas locais com suporte, manutenção preventiva, redes internas e melhoria de ambientes de trabalho.'],
            ['name' => 'Juliana Rocha', 'role' => 'Coordenadora de Educação Tecnológica', 'company' => 'EducaTech PG', 'experience_years' => 9, 'avatar_initials' => 'JR', 'avatar_color' => '#0891b2', 'specialties' => ['Educação Tecnológica', 'Cursos', 'Capacitação'], 'summary' => 'Desenvolve trilhas de formação em tecnologia para jovens, profissionais em transicao de carreira e equipes corporativas.'],
        ];

        foreach ($members as $index => $row) {
            $member = Member::updateOrCreate(
                ['slug' => Str::slug($row['name'])],
                [
                    'company_id' => $companies[$row['company']]->id,
                    'name' => $row['name'],
                    'avatar_initials' => $row['avatar_initials'],
                    'avatar_color' => $row['avatar_color'],
                    'role' => $row['role'],
                    'city' => 'Ponta Grossa, PR',
                    'experience_years' => $row['experience_years'],
                    'summary' => $row['summary'],
                    'site_url' => 'https://'.Str::slug($row['company']).'.example.com',
                    'linkedin_url' => '#',
                    'instagram_url' => '#',
                    'whatsapp' => '(42) 99999-0000',
                    'email' => Str::slug($row['name'], '.').'@techpg.org.br',
                    'is_featured' => $index < 3,
                    'status' => ModerationStatus::Approved,
                    'is_active' => true,
                    'reviewed_at' => now(),
                    'registration_source' => 'demo',
                ]
            );

            $member->specialties()->sync(collect($row['specialties'])->map(fn (string $name) => $specialties[$name]->id));

            foreach (['Projetos para empresas locais e regionais', 'Atuação em equipes multidisciplinares', 'Participação no ecossistema de tecnologia de PG'] as $position => $title) {
                $member->experiences()->updateOrCreate(['position' => $position], ['title' => $title]);
            }

            foreach (['Portal institucional e presença digital', 'Automação de processos internos', 'Consultoria para melhoria operacional'] as $position => $title) {
                $member->projects()->updateOrCreate(['position' => $position], ['title' => $title]);
            }

            foreach (['Certificacao profissional relacionada a área', 'Formacao complementar em tecnologia'] as $position => $title) {
                $member->certifications()->updateOrCreate(['position' => $position], ['title' => $title]);
            }
        }

        $posts = [
            [
                'title' => 'Associação Tech PG inicia mapeamento do ecossistema local',
                'category' => 'Institucional',
                'excerpt' => 'Portal organiza empresas, profissionais e especialidades para dar mais visibilidade ao setor de tecnologia em Ponta Grossa.',
                'body' => "A Associação Tech PG iniciou uma nova etapa de organização do ecossistema local de tecnologia.\n\nO portal reúne empresas, profissionais, especialidades e canais de contato em uma base única, fácilitando conexões, parcerias, oportunidades e a representação institucional do setor.\n\nA primeira fase prioriza cadastro, aprovação e publicação de empresas associadas, além de perfis profissionais e indicadores iniciais.",
                'published_at' => now()->subDays(6),
            ],
            [
                'title' => 'Cadastro público de empresas já está aberto',
                'category' => 'Associados',
                'excerpt' => 'Empresas interessadas podem enviar seus dados para análise e futura publicação no diretório da associação.',
                'body' => "O cadastro público de empresas está aberto para participantes do ecossistema tech de Ponta Grossa.\n\nO envio não cria senha nem acesso administrativo. Os dados passam por triagem interna e, após aprovação, a empresa pode aparecer no portal público.\n\nA proposta é manter uma vitrine organizada e confiável, com informações de contato, segmento de atuação, descrição institucional e membros vinculados.",
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Agenda da associação deve priorizar encontros recorrentes',
                'category' => 'Eventos',
                'excerpt' => 'Encontros mensais, workshops e demo days ajudam a transformar cadastro em relacionamento real entre associados.',
                'body' => "Uma associação ganha força quando cria ritos de participação.\n\nA agenda proposta para o Portal Associação Tech PG combina encontros pequenos de networking, workshops de capacitação, demo days e fóruns temáticos sobre tecnologia, inovação, educação e transformação digital.\n\nEsses formatos ajudam empresas e profissionais a criarem vínculos, compartilharem conhecimento e apresentarem resultados para a comunidade.",
                'published_at' => now()->subDay(),
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(
                ['slug' => Str::slug($post['title'])],
                $post + [
                    'slug' => Str::slug($post['title']),
                    'is_published' => true,
                ]
            );
        }

        $events = [
            ['title' => 'Encontro Tech PG', 'type' => 'Networking', 'description' => 'Rodada mensal para conexão entre empresas, profissionais, universidades e poder público.', 'location' => 'Ponta Grossa, PR', 'event_date' => now()->addMonth()->toDateString(), 'starts_at' => '19:00', 'ends_at' => '21:00', 'is_featured' => true],
            ['title' => 'Workshop LGPD para empresas de tecnologia', 'type' => 'Capacitação', 'description' => 'Boas práticas de tratamento de dados, cadastro de associados e publicação de perfis.', 'location' => 'Online', 'event_date' => now()->addMonths(2)->toDateString(), 'starts_at' => '14:00', 'ends_at' => '17:00', 'is_featured' => false],
            ['title' => 'Demo Day Associação Tech PG', 'type' => 'Inovação', 'description' => 'Apresentacao de produtos, cases, startups e soluções criadas por associados.', 'location' => 'Ponta Grossa, PR', 'event_date' => now()->addMonths(3)->toDateString(), 'starts_at' => '18:30', 'ends_at' => '22:00', 'is_featured' => true],
        ];

        foreach ($events as $event) {
            Event::updateOrCreate(
                ['slug' => Str::slug($event['title'])],
                $event + ['slug' => Str::slug($event['title']), 'is_published' => true]
            );
        }

        $pages = [
            ['title' => 'Comitês', 'excerpt' => 'Conheça os grupos de trabalho da associação.', 'body' => "Os comitês organizam a participação dos associados em temas práticos.\n\nSugestões iniciais: Eventos e Comunidade, Educação e Talentos, Inovação e Negócios, Políticas Públicas e Dados.", 'position' => 10],
            ['title' => 'Projetos', 'excerpt' => 'Iniciativas coletivas em desenvolvimento pela associação.', 'body' => "Esta página pode concentrar projetos em andamento, resultados esperados, responsáveis e oportunidades de participação.\n\nExemplos: mapeamento do ecossistema, calendário de eventos, banco de oportunidades e relatório anual do setor.", 'position' => 20],
        ];

        foreach ($pages as $page) {
            CmsPage::updateOrCreate(
                ['slug' => Str::slug($page['title'])],
                $page + ['slug' => Str::slug($page['title']), 'is_published' => true, 'show_in_menu' => true]
            );
        }
    }
}
