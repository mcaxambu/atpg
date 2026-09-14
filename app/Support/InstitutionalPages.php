<?php

namespace App\Support;

/**
 * Paginas do CMS que alimentam rotas fixas do portal.
 *
 * Estas paginas sao encontradas pelo SLUG, nao pelo id. Se o slug muda, a rota
 * deixa de achar a pagina e o portal cai no texto embutido no Blade — sem erro,
 * sem aviso. Foi o que aconteceu com a "Sobre", que ficou meses desligada
 * porque o slug era gerado a partir do titulo.
 *
 * Concentrado aqui para que o painel consiga avisar o editor de qual endereco
 * cada pagina alimenta.
 */
class InstitutionalPages
{
    /**
     * slug => [rotulo exibido no painel, nome da rota]
     *
     * @var array<string, array{label: string, route: string}>
     */
    private const PAGES = [
        'sobre' => ['label' => 'Sobre', 'route' => 'about'],
        'beneficios' => ['label' => 'Benefícios', 'route' => 'benefits'],
        'governanca' => ['label' => 'Governança', 'route' => 'governance'],
        'associe-se' => ['label' => 'Associe-se', 'route' => 'join'],
        'projetos' => ['label' => 'Projetos', 'route' => 'projects'],
    ];

    /**
     * @return array<string, array{label: string, route: string}>
     */
    public static function all(): array
    {
        return self::PAGES;
    }

    /**
     * @return array<int, string>
     */
    public static function slugs(): array
    {
        return array_keys(self::PAGES);
    }

    public static function isInstitutional(?string $slug): bool
    {
        return $slug !== null && array_key_exists($slug, self::PAGES);
    }

    public static function labelFor(?string $slug): ?string
    {
        return self::PAGES[$slug]['label'] ?? null;
    }

    /**
     * URL publica que a pagina alimenta, para o painel mostrar ao editor.
     */
    public static function urlFor(?string $slug): ?string
    {
        $route = self::PAGES[$slug]['route'] ?? null;

        return $route ? route($route) : null;
    }
}
