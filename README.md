# Portal Associação Tech PG

Portal Laravel para apresentar e operar a associação de tecnologia de Ponta Grossa.
Reúne um site institucional público e um painel administrativo com CMS.

- **Stack:** Laravel 13 · PHP 8.3 · MariaDB · Tailwind 4 + Alpine (admin) · CSS próprio (site)

## Como o portal está organizado

O aplicativo responde na raiz **e** sob o prefixo `/atpg`. As rotas são declaradas
uma única vez em [routes/web.php](routes/web.php) e registradas nos dois prefixos;
o middleware `SetPortalUrlRoot` ajusta a raiz das URLs geradas quando a requisição
chega pelo prefixo.

### Site público

Home, diretório de empresas, diretório de membros, perfis públicos, agenda de
eventos, notícias, páginas institucionais (sobre, benefícios, governança,
associe-se, projetos), políticas de privacidade/LGPD/cookies e `sitemap.xml`.

Cadastro público de **empresa** e de **membro**, ambos sem senha e sujeitos a
aprovação.

### Painel administrativo

Gestão de membros, empresas e especialidades; filas de aprovação separadas para
empresa e membro; lixeira com restauração; usuários do painel e perfil próprio;
CMS com notícias, eventos, páginas, banners, destaques, categorias, projetos,
depoimentos, parceiros e configurações do portal.

## Fluxo de moderação

```text
Cadastro público  ->  pendente  ->  análise  ->  aprovado + publicado
                                            \->  rejeitado (com motivo)
```

Empresa e membro seguem exatamente o mesmo fluxo, centralizado em
[ModerateRegistration](app/Actions/ModerateRegistration.php).

Dois campos distintos, propositalmente:

| Campo       | Significado                                                       |
|-------------|-------------------------------------------------------------------|
| `status`    | Decisão da associação: `pending`, `approved` ou `rejected`         |
| `is_active` | Publicação: um cadastro aprovado pode sair do ar sem ser rejeitado |

Visível no portal = `status = approved` **e** `is_active = true`. Um membro só
aparece se a empresa dele também estiver publicada — membro sem empresa
(profissional independente) é válido e aparece normalmente.

Aprovação e rejeição disparam e-mail ao responsável pelo cadastro. A rejeição
exige um motivo, que vai no corpo do e-mail. Falha de envio é registrada em log e
não desfaz a decisão já gravada.

## API pública (v1)

Somente leitura, sem autenticação, servindo exatamente o mesmo conteúdo que já
está publicado no site. Limite de 60 requisições por minuto por IP.

Base: `https://atpg.net.br/api/v1`

| Método | Rota | Descrição |
|---|---|---|
| GET | `/empresas` | Empresas publicadas |
| GET | `/empresas/{slug}` | Empresa com seus membros |
| GET | `/membros` | Membros publicados |
| GET | `/membros/{slug}` | Perfil com trajetória completa |
| GET | `/especialidades` | Vocabulário para o filtro `especialidade` |

Filtros de `/empresas`: `busca`, `segmento`, `cidade`.
Filtros de `/membros`: `busca`, `especialidade` (slug), `empresa` (slug),
`experiencia_minima`.
Paginação em ambos: `pagina`, `por_pagina` (máximo 100).

```bash
curl "https://atpg.net.br/api/v1/membros?especialidade=devops&experiencia_minima=5"
```

**Contato pessoal fica fora de propósito.** E-mail e WhatsApp dos membros
aparecem na página do perfil, mas não na API: uma lista paginada permite coleta
em massa de dados pessoais, que é justamente o risco que a LGPD trata. Se a
associação decidir liberar, o caminho correto é um endpoint autenticado por
token, não abrir os campos aqui.

Um registro só aparece na API se estiver aprovado **e** publicado — e o membro
depende também da empresa dele estar publicada. Pendentes e rejeitados retornam
404, com a mesma regra do site.

## Segurança dos formulários abertos

Os cadastros públicos ficam sem senha, então contam com:

- `throttle:5,60` por IP;
- honeypot + descarte de envios instantâneos demais (`ProtectPublicForm`);
- consentimento LGPD obrigatório;
- CNPJ validado pelos dígitos verificadores ([Cnpj](app/Rules/Cnpj.php)), não só pelo formato;
- vínculo de membro restrito a empresas já publicadas.

## Rodando o projeto

```bash
composer setup
```

No servidor, dentro de `/var/www/sites/atpg`:

```bash
php artisan migrate --force
php artisan db:seed --class=PortalDemoSeeder --force
php artisan db:seed --class=CmsPresentationSeeder --force
php artisan db:seed --class=SiteSettingsSeeder --force
npm run build
php artisan optimize:clear
```

## Dados de demonstração

O `PortalDemoSeeder` cria empresas e membros fictícios e **se recusa a rodar em
produção**. Cada registro criado por ele fica marcado com
`registration_source = 'demo'`.

Para remover o que já entrou:

```bash
php artisan portal:limpar-demo --dry-run
```

```bash
php artisan portal:limpar-demo
```

O comando apaga em definitivo (inclusive o que estiver na lixeira) e remove os
arquivos enviados. Cadastros com origem `public_form` ou `admin` — os reais —
nunca são tocados.

## Testes

```bash
php artisan test
```

A suíte cobre o fluxo de moderação, os CRUDs do painel, os cadastros públicos
(incluindo honeypot e CNPJ) e a resposta de todas as telas nos dois prefixos.

## Contribuindo

O fluxo é branch + pull request; `main` é protegida. Veja
[CONTRIBUTING.md](CONTRIBUTING.md).

## Operação e publicação

Os scripts de deploy e diagnóstico ficam **no servidor**, em `.ops/`, e não
são versionados: eles carregam endereço de host, caminhos e o procedimento de
publicação. Quem administra o servidor tem acesso a eles por SSH.

## Notas de operação

- Uploads usam o disco `public` do Laravel; `php artisan storage:link` precisa estar feito.
- **Não** guarde modelos Eloquent no cache — vale para qualquer driver, e é o
  motivo de `CACHE_STORE=file` no `.env.example`. Com o driver `database`, a serialização
  de objetos contém bytes nulos que a coluna `mediumtext` utf8mb4 corrompe, e a
  leitura volta como `__PHP_Incomplete_Class`. Só escalares vão para o cache
  (ver [PortalData](app/Support/Portal/PortalData.php)).
- `APP_URL` deve ser a raiz do host, **sem** `/atpg`. O prefixo é resolvido em
  tempo de requisição; incluí-lo na variável duplica o caminho nas URLs geradas.
