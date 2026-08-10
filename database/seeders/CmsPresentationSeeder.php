<?php

namespace Database\Seeders;

use App\Models\CmsItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CmsPresentationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['module' => 'banners', 'title' => 'Conectando empresas, profissionais e inovação regional', 'subtitle' => 'Home principal', 'description' => 'Uma plataforma institucional para dar visibilidade ao ecossistema tech, organizar associados, fortalecer a representação setorial e aproximar oportunidades.', 'position' => 1],
            ['module' => 'destaques', 'title' => 'Cadastro aberto para empresas de tecnologia', 'subtitle' => 'Associação', 'description' => 'Empresas podem solicitar participação pelo formulário público e aguardar aprovação administrativa antes da publicação no portal.', 'link_url' => '/atpg/associe-se', 'button_label' => 'Associar empresa', 'position' => 1],
            ['module' => 'destaques', 'title' => 'Agenda para aproximar o ecossistema', 'subtitle' => 'Eventos', 'description' => 'Workshops, encontros, reuniões temáticas e demo days ajudam a transformar cadastro em relacionamento real.', 'link_url' => '/atpg/eventos', 'button_label' => 'Ver agenda', 'position' => 2],
            ['module' => 'destaques', 'title' => 'Vitrine qualificada para associados', 'subtitle' => 'Visibilidade', 'description' => 'Perfis de empresas e membros mostram especialidades, contatos, projetos e áreas de atuação em Ponta Grossa.', 'link_url' => '/atpg/empresas', 'button_label' => 'Ver empresas', 'position' => 3],
            ['module' => 'projetos', 'title' => 'Mapa Tech Ponta Grossa', 'subtitle' => 'Inteligência setorial', 'description' => 'Levantamento contínuo de empresas, profissionais, especialidades e demandas para orientar a atuação da associação.', 'position' => 1],
            ['module' => 'projetos', 'title' => 'Agenda de Capacitação ATPG', 'subtitle' => 'Talentos', 'description' => 'Trilha de encontros, palestras e workshops para desenvolver competências técnicas e de gestão no ecossistema local.', 'position' => 2],
            ['module' => 'projetos', 'title' => 'Conecta Empresas e Universidades', 'subtitle' => 'Parcerias', 'description' => 'Programa para aproximar empresas, instituições de ensino, estudantes e pesquisadores em desafios reais de tecnologia.', 'position' => 3],
            ['module' => 'parceiros', 'title' => 'Universidades e Instituições de Ensino', 'subtitle' => 'Educação', 'description' => 'Conexões com formação, pesquisa e desenvolvimento de talentos.', 'position' => 1],
            ['module' => 'parceiros', 'title' => 'Entidades Empresariais', 'subtitle' => 'Representatividade', 'description' => 'Articulação com entidades locais para ampliar oportunidades de negócios.', 'position' => 2],
            ['module' => 'parceiros', 'title' => 'Poder Público e Inovação', 'subtitle' => 'Desenvolvimento', 'description' => 'Diálogo institucional para fortalecer políticas de tecnologia e inovação.', 'position' => 3],
            ['module' => 'depoimentos', 'title' => 'Diretoria ATPG', 'subtitle' => 'Governança', 'description' => 'O portal organiza a associação e cria uma vitrine profissional para mostrar a força da tecnologia em Ponta Grossa.', 'position' => 1],
            ['module' => 'depoimentos', 'title' => 'Empresa associada', 'subtitle' => 'Visibilidade', 'description' => 'Ter um perfil público ajuda clientes, parceiros e talentos a entenderem melhor nossa atuação no ecossistema.', 'position' => 2],
        ];

        foreach ($items as $item) {
            CmsItem::updateOrCreate(
                ['module' => $item['module'], 'slug' => Str::slug($item['title'])],
                array_merge([
                    'is_active' => true,
                    'published_at' => now(),
                ], $item, ['slug' => Str::slug($item['title'])])
            );
        }
    }
}
