<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CmsItem;
use App\Models\CmsPage;
use App\Support\Portal\PortalData;

class PageController extends Controller
{
    public function about(PortalData $portalData)
    {
        // Carregados so aqui, e nao no PortalData: sao usados nesta pagina, e
        // nao ha por que consultar o banco em toda pagina do portal.
        return view('portal.about', $this->withCmsPage($portalData, 'sobre') + [
            'purposeItems' => $this->moduleItems('missao-visao'),
            'valueItems' => $this->moduleItems('valores'),
        ]);
    }

    public function benefits(PortalData $portalData)
    {
        return view('portal.benefits', $this->withCmsPage($portalData, 'beneficios'));
    }

    public function governance(PortalData $portalData)
    {
        return view('portal.governance', $this->withCmsPage($portalData, 'governanca'));
    }

    public function join(PortalData $portalData)
    {
        return view('portal.join', $this->withCmsPage($portalData, 'associe-se'));
    }

    public function projects(PortalData $portalData)
    {
        return view('portal.projects', $this->withCmsPage($portalData, 'projetos'));
    }

    public function lgpd(PortalData $portalData)
    {
        return view('portal.legal-page', $portalData->merge([
            'title' => 'LGPD e Proteção de Dados',
            'eyebrow' => 'Conformidade',
            'excerpt' => 'Diretrizes iniciais sobre tratamento de dados pessoais no Portal Associação Tech PG.',
            'sections' => [
                [
                    'title' => 'Compromisso com a proteção de dados',
                    'body' => 'O portal trata dados pessoais com finalidade institucional, associativa e de comunicação, observando princípios da Lei Geral de Proteção de Dados Pessoais (Lei nº 13.709/2018), como finalidade, necessidade, transparência, segurança e prevenção.',
                ],
                [
                    'title' => 'Dados tratados',
                    'body' => 'Podemos tratar dados de contato, identificação profissional, informações empresariais, links públicos, imagens/logos enviadas em cadastros e dados técnicos de navegação necessários para funcionamento e segurança do portal.',
                ],
                [
                    'title' => 'Bases e finalidades',
                    'body' => 'Os dados são utilizados para análise de cadastros, publicação de empresas e perfis aprovados, comunicação institucional, organização de eventos, relacionamento com associados e melhoria da experiência do usuário.',
                ],
                [
                    'title' => 'Direitos do titular',
                    'body' => 'Titulares podem solicitar confirmação de tratamento, acesso, correção, atualização, anonimização, bloqueio, eliminação ou informações sobre compartilhamento de seus dados, conforme aplicável.',
                ],
                [
                    'title' => 'Canal de contato',
                    'body' => 'Solicitações relacionadas a privacidade e proteção de dados podem ser encaminhadas pelo contato institucional informado no rodapé do portal.',
                ],
            ],
        ]));
    }

    public function privacy(PortalData $portalData)
    {
        return view('portal.legal-page', $portalData->merge([
            'title' => 'Política de Privacidade',
            'eyebrow' => 'Privacidade',
            'excerpt' => 'Como o portal coleta, usa, armazena e protege informações dos visitantes, empresas e membros.',
            'sections' => [
                [
                    'title' => 'Coleta de informações',
                    'body' => 'Coletamos dados informados voluntariamente em formulários, como cadastro de empresa, contato institucional e futuras áreas de relacionamento. Também podemos registrar informações técnicas básicas de navegação para segurança, auditoria e melhoria do portal.',
                ],
                [
                    'title' => 'Uso das informações',
                    'body' => 'As informações são usadas para analisar solicitações, publicar dados aprovados no diretório, manter comunicação com interessados, organizar iniciativas da associação e melhorar serviços e conteúdos do portal.',
                ],
                [
                    'title' => 'Compartilhamento',
                    'body' => 'Dados públicos de empresas e perfis aprovados podem ser exibidos no portal. Dados administrativos não são vendidos e somente são compartilhados quando necessário para operação, cumprimento legal ou autorização do titular.',
                ],
                [
                    'title' => 'Segurança',
                    'body' => 'Adotamos medidas técnicas e administrativas razoáveis para proteger informações contra acessos não autorizados, perda, alteração ou divulgação indevida.',
                ],
                [
                    'title' => 'Atualizações',
                    'body' => 'Esta política poderá ser atualizada conforme a evolução do portal, novas funcionalidades, exigências legais ou práticas internas da associação.',
                ],
            ],
        ]));
    }

    public function cookies(PortalData $portalData)
    {
        return view('portal.legal-page', $portalData->merge([
            'title' => 'Política de Cookies',
            'eyebrow' => 'Cookies',
            'excerpt' => 'Informações sobre o uso de cookies e tecnologias semelhantes no portal.',
            'sections' => [
                [
                    'title' => 'O que são cookies',
                    'body' => 'Cookies são pequenos arquivos armazenados no navegador para permitir funcionamento do site, lembrar preferências e apoiar medições técnicas de uso.',
                ],
                [
                    'title' => 'Cookies essenciais',
                    'body' => 'Utilizamos cookies essenciais para manter segurança, sessão, preferências básicas e funcionamento correto de formulários e navegação.',
                ],
                [
                    'title' => 'Cookies de melhoria',
                    'body' => 'Em fases futuras, o portal poderá usar cookies analíticos para compreender páginas mais acessadas, fluxos de navegação e oportunidades de melhoria. Sempre que necessário, a configuração será atualizada.',
                ],
                [
                    'title' => 'Gerenciamento',
                    'body' => 'O usuário pode limpar ou bloquear cookies pelo próprio navegador. O bloqueio de cookies essenciais pode comprometer recursos como formulários, login administrativo e preferências de navegação.',
                ],
            ],
        ]));
    }

    public function show(CmsPage $page, PortalData $portalData)
    {
        abort_unless($page->is_published, 404);

        return view('portal.cms-page', $portalData->merge(['page' => $page]));
    }

    /**
     * Itens ativos de um modulo do CMS, na ordem definida no painel.
     */
    private function moduleItems(string $module)
    {
        return CmsItem::query()
            ->module($module)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    private function withCmsPage(PortalData $portalData, string $slug): array
    {
        return $portalData->merge([
            'cmsPage' => CmsPage::query()
                ->published()
                ->where('slug', $slug)
                ->first(),
        ]);
    }
}
