# Como colaborar

Bem-vindo. Este é o portal da Associação Tech PG, em produção em
<https://atpg.net.br>. O que entra na branch `main` vai para o ar — por isso
existe revisão antes de juntar.

## Subindo o projeto na sua máquina

Você precisa de **PHP 8.3+**, **Composer** e **Node 20+**.

```bash
git clone git@github.com:<organizacao>/atpg.git
cd atpg
```

```bash
composer install && npm install
```

```bash
cp .env.example .env && php artisan key:generate
```

O jeito mais rápido de ter banco é SQLite, sem instalar nada:

```bash
touch database/database.sqlite
```

Troque `DB_CONNECTION=mysql` por `DB_CONNECTION=sqlite` no `.env`, comente as
outras linhas `DB_*`, e então:

```bash
php artisan migrate --seed
```

O seeder cria o administrador e mostra a senha gerada **uma única vez** no
console. Anote.

```bash
composer dev
```

Isso sobe o servidor, o Vite e o log ao mesmo tempo. O site fica em
<http://localhost:8000> e o painel em <http://localhost:8000/entrar>.

## O fluxo de trabalho

A branch `main` é protegida: ninguém envia direto para ela.

```bash
git checkout -b ajuste/nome-curto-do-que-voce-vai-fazer
```

Faça a alteração, rode os testes, e abra um pull request. Alguém revisa e junta.

Prefixos que usamos no nome da branch: `ajuste/` para correção, `novo/` para
funcionalidade, `doc/` para documentação.

## Antes de abrir o pull request

```bash
php artisan test
```

```bash
vendor/bin/pint
```

Os dois precisam passar. O `pint` formata o código no padrão do projeto — rode
antes de commitar para o diff não encher de mudança de espaçamento.

**Toda alteração de comportamento precisa de teste.** Não é burocracia: este
sistema tem dados reais de empresas associadas, e a suíte é o que permite mexer
sem medo. Olhe `tests/Feature` para ver o estilo — os testes têm nome em
português e descrevem o comportamento, não o método.

## O que este projeto espera de você

**Escreva o porquê, não o quê.** O código já diz o que faz. Comentário bom
explica a decisão: por que assim e não do jeito óbvio. Veja
`app/Support/PortalData.php` para um exemplo.

**Não confie no que parece óbvio — meça.** Boa parte dos problemas que este
projeto já teve pareciam uma coisa e eram outra.

**Cuidado com dado de pessoa.** O portal guarda e-mail, telefone e CNPJ de
empresas e profissionais. A API pública, por exemplo, omite contato de propósito:
uma lista paginada de e-mails é coleta em massa. Se for expor algo novo,
pergunte-se quem consegue ler.

## Onde as coisas estão

| Pasta | O que tem |
|---|---|
| `app/Http/Controllers/Portal` | site público |
| `app/Http/Controllers/Admin` | painel da associação |
| `app/Http/Controllers/Company` | painel da empresa associada |
| `app/Http/Controllers/Api` | API pública v1 |
| `app/Actions` | operações com regra de negócio (moderação, geração de ata) |
| `resources/views/portal` | telas |
| `resources/views/components/admin` | componentes do painel |
| `public/css/portal.css` | CSS do site público, escrito à mão, fora do Vite |

Duas coisas que confundem quem chega:

**O site responde na raiz e também sob `/atpg`.** As rotas são declaradas uma
vez e registradas nos dois prefixos. Não duplique rota.

**O painel usa Tailwind; o site público, não.** O site tem CSS próprio em
`public/css/portal.css`. Classe do Tailwind não funciona lá — foi erro
frequente. Migrar esse arquivo para o Vite está na lista de melhorias.

## Publicação

O deploy é feito por scripts que ficam no servidor, fora deste repositório.
Fale com quem administra antes de mexer em produção.

## Dúvidas

Abra uma issue descrevendo o que você quer fazer antes de escrever muito código
— é mais barato alinhar antes do que refazer depois.
