# queixa.me

Portal independente de reclamações de consumo: os consumidores publicam o que
aconteceu, as empresas respondem publicamente, e o desfecho de cada caso é
medido e comparado.

## Arrancar em local

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed     # inclui um portal de demonstração completo
npm run build                  # ou: npm run dev
php artisan serve
```

Contas de demonstração (apenas fora de produção):

| Conta | Email | Palavra-passe |
| --- | --- | --- |
| Administração | `admin@queixa.me` | `password` |
| Moderação | `moderador@queixa.me` | `password` |
| Gestor de empresa | `gestor@<slug-da-empresa>.pt` | `password` |

## Organização

O código está organizado por contexto de negócio, não por tipo de ficheiro:

```
app/Domain/<Contexto>/          Models, Enums, Actions, Services, Notifications
app/Http/Controllers/<Área>/    PublicSite, Consumer, Business, Admin, Auth
app/Http/Requests/              validação de entrada
app/Http/Middleware/            noindex, staff, company.member, cabeçalhos, redirects
resources/views/                public, consumer, business, admin, auth, components
```

As regras de negócio vivem em serviços do domínio
(`ComplaintWorkflow`, `SatisfactionIndexCalculator`, `ConversationService`),
nunca nos controllers. Os valores ajustáveis estão em `config/queixame.php` —
e é essa mesma configuração que alimenta a página pública de metodologia, para
que nunca possa ficar desatualizada face ao cálculo real.

## Decisões que estruturam o produto

- **Dois eixos de estado.** A moderação (`ModerationStatus`) e o ciclo de vida
  público (`ComplaintStage`) são independentes, o que permite representar casos
  reais como uma reclamação publicada que volta a moderação sem sair do ar.
- **Só o consumidor confirma a resolução.** A empresa propõe; se pudesse fechar
  os próprios processos, a taxa de resolução deixaria de medir resolução.
- **O ranking mede comportamento, não volume.** Índice bayesiano sobre uma
  janela de 12 meses, com mínimo de reclamações para figurar publicamente.
- **Separação de dados.** O conteúdo público e os dados de contacto do
  reclamante vivem em tabelas distintas; os segundos são cifrados e têm prazo
  de expurgo próprio.
- **URLs permanentes.** Slugs de reclamação imutáveis, histórico de slugs de
  empresa e tabela de redirecionamentos — nenhuma fusão de fichas gera 404.

## Comandos úteis

```bash
php artisan queixame:stats --awards   # recalcula índices e Marcas do Mês
php artisan test                      # testes de fumo + regras de negócio
./vendor/bin/pint                     # formatação
```

O recálculo de indicadores deve correr no agendador (janela de 12 meses
diariamente, snapshots mensais no início de cada mês). Nunca durante um
pedido HTTP.
