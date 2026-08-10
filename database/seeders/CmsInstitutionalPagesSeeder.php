<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsInstitutionalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Um ponto de encontro para tecnologia em Ponta Grossa',
                'slug' => 'sobre',
                'excerpt' => 'O portal organiza informações profissionais, da visibilidade a empresas locais e fácilita novas conexões no ecossistema regional.',
                'body' => "A Associação Tech PG nasce como uma camada de organização para o ecossistema de tecnologia de Ponta Grossa.\n\nO objetivo é reunir empresas, profissionais, eventos, projetos e comunicados em um ambiente único, com curadoria administrativa e informações sempre atualizáveis pelo CMS.",
                'position' => 10,
            ],
            [
                'title' => 'Benefícios claros para empresas, profissionais e comunidade',
                'slug' => 'beneficios',
                'excerpt' => 'Uma proposta de valor baseada em visibilidade, networking, capacitação, representatividade e oportunidades para associados.',
                'body' => "O portal transforma a associação em uma vitrine ativa: empresas ganham página pública, membros ganham perfil profissional e o ecossistema ganha uma base organizada de contatos, especialidades e iniciativas.\n\nA evolução natural inclui planos de associado, banco de oportunidades, trilhas de capacitação e indicadores setoriais.",
                'position' => 20,
            ],
            [
                'title' => 'Governança simples, transparente e participativa',
                'slug' => 'governanca',
                'excerpt' => 'Diretoria, conselhos, comitês e ritos de acompanhamento ajudam a transformar a rede em uma instituição forte.',
                'body' => "A governança proposta combina diretoria executiva, conselho consultivo e comitês temáticos para eventos, educação, inovação e dados.\n\nA transparência deve ser sustentada por pautas, atas resumidas, critérios de aprovação, indicadores do portal e prestação de contas.",
                'position' => 30,
            ],
            [
                'title' => 'Entre para uma rede que fortalece tecnologia e inovação em Ponta Grossa',
                'slug' => 'associe-se',
                'excerpt' => 'Associe sua empresa para ganhar visibilidade, participar da agenda do ecossistema e contribuir com uma pauta coletiva para o setor.',
                'body' => "O cadastro público é o primeiro passo para a entrada da empresa no portal. A solicitação passa por análise administrativa antes da publicação.\n\nDepois de aprovada, a empresa pode aparecer no diretório público e ser vinculada a membros, especialidades e projetos.",
                'position' => 40,
            ],
            [
                'title' => 'Projetos coletivos para transformar relacionamento em impacto regional',
                'slug' => 'projetos',
                'excerpt' => 'Programas, grupos de trabalho, parcerias e iniciativas de inovação aberta mostram a associação em movimento.',
                'body' => "A página de projetos apresenta iniciativas cadastradas no CMS, como programas de capacitação, agenda de encontros, grupos de trabalho, parcerias institucionais e projetos de inovação aberta.\n\nEssas frentes ajudam a associação a sair do cadastro e gerar entregas percebidas pelos membros.",
                'position' => 50,
            ],
        ];

        foreach ($pages as $page) {
            CmsPage::query()->updateOrCreate(
                ['slug' => $page['slug']],
                $page + [
                    'is_published' => true,
                    'show_in_menu' => false,
                ]
            );
        }
    }
}
