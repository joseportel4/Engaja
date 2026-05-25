# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Engaja** is a Laravel 12 application for managing educational events, enrollments, attendance, and engagement reports for the Alfa-EJA project. It tracks formations, workshops, meetings, and live sessions for educational institutions.

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Bootstrap 5 + Blade + Livewire 4
- **Database:** PostgreSQL
- **Auth:** Laravel Breeze + Spatie Laravel Permission
- **PDF:** spatie/laravel-pdf (Browsershot/Puppeteer/Chromium)
- **Imports:** maatwebsite/excel (xlsx)
- **QR Code:** simplesoftwareio/simple-qrcode

## Common Commands

```bash
# Development
php artisan serve        # Start dev server (http://localhost:8000)
npm run dev              # Compile assets (Vite, watch mode)
npm run build            # Compile for production

# Database
php artisan migrate
php artisan migrate --seed   # Fresh setup with roles, permissions, seed data
php artisan migrate:fresh --seed

# Code quality
./vendor/bin/pint        # Laravel Pint (PSR-12 code style fixer)

# Tests
php artisan test
php artisan test --filter TestClassName   # Run single test

# Scheduling & Cache
php artisan schedule:list                 # List all scheduled tasks
php artisan schedule:run                  # Simulate scheduler (runs due tasks)
php artisan limesurvey:importar-dados     # Import LimeSurvey data to cache (24h TTL)
```

## Architecture

### Domain Model

The core hierarchy is: **Evento → Atividade → Presença/Inscrição**

- `Evento` — an educational event, tied to an `Eixo` (thematic axis), `acao_geral`, and `subacao` (from Alfa-EJA project constants defined in the model). Has checklists for planning/closure.
- `Atividade` — a session/moment within an event, tied to a `Municipio`. Stores `carga_horaria` in **minutes** (column name is legacy). Has planning and closure checklists (JSON columns).
- `Participante` — person participating; separate from `User`.
- `Inscricao` — enrollment of a Participante in an Evento.
- `Presenca` — attendance record of a Participante at an Atividade. Has `avaliacao_respondida` (bool) flag.
- `Agendamento` — scheduling of a Participante for an AtividadeAcao.

### Naming Conventions

| Term in UI | Model | Table |
|---|---|---|
| Ação pedagógica | `Evento` | `eventos` |
| Momento / Encontro | `Atividade` | `atividades` |
| Relatório do Momento | `AvaliacaoAtividade` | `avaliacao_atividades` |

### Authorization

Uses **Spatie Laravel Permission** with roles and permissions.

Roles: `administrador`, `gerente`, `eq_pedagogica`, `articulador`, `participante`, `SME`

Permissions follow the pattern `resource.action` (e.g., `evento.criar`, `presenca.abrir`). Route middleware uses `role:` and `permission:` guards. Most management routes require role `administrador|gerente|eq_pedagogica|articulador`.

### Key Patterns

- **Repositories:** `app/Repositories/` — currently only `BiValorRepository` for BI queries.
- **Services:** `app/Services/` — `LimeSurveyDashboardService`, `ParticipantesExclusivosService`, and BI services.
- **Console Commands:** `app/Console/Commands/` — `ImportLimeSurveyData` (daily cache warm-up), `ImportBiGeral` (CSV import).
- **Livewire:** `app/Livewire/Dashboards/` and `app/Livewire/Graficos/` — interactive dashboard components.
- **Imports:** `app/Imports/` — Excel importers via maatwebsite/excel with tolerant header parsing.
- **Exports:** `app/Exports/` — Excel exports.
- **PDF:** gerado via `spatie/laravel-pdf` (Browsershot/Puppeteer). Macro `->withAlfaEjaBrand()` em `AppServiceProvider` aplica cabeçalho, rodapé e margens institucionais. Layouts em `resources/views/layouts/pdf-alfa-eja.blade.php` (portrait) e `pdf-alfa-eja-landscape.blade.php` (landscape).
- **ViewModels:** `app/ViewModels/` — view data transformation.
- **Policies:** `app/Policies/` — model-level authorization.

### Filter + Sort Pattern (Blade reports)

Used in `DashboardController::index()` and `RelatorioQuantitativoController::index()`:
- `$sortable = ['key' => 'db_column']` map; direction validated to `asc`/`desc`.
- Filters applied with `->when($value, fn($q) => ...)`.
- `->withCount(['relation as alias' => fn($q) => $q->where(...)])` for aggregated counts.
- `->appends($request->query())` preserves filter state across pagination.
- Sort links built inline in Blade with `http_build_query` — no shared helper function.

### Import Flow (Presença/Inscrição)

Imports follow a multi-step preview/confirm pattern:
1. Upload → parse xlsx → store in session
2. Preview page (paginated)
3. Confirm → persist to database

### Seeding

`RolesPermissionsSeeder` sets up all roles and permissions. `DatabaseSeeder` creates a default admin user (`admin@engaja.local`) with sample events/activities. Always run `--seed` on fresh installs.

### Reports (Relatórios)

**Relatórios do Momento** (`/relatorios-avaliacao`):
- `AvaliacaoAtividadeController` — qualitative reports per atividade, grouped by ação/momento.
- Views: `resources/views/avaliacao-atividade/`.

**Relatório Quantitativo** (`/relatorio-quantitativo`):
- `RelatorioQuantitativoController` — attendance and evaluation counts per encontro.
- Filters: ação, momento, município, date range, período (manhã/tarde/noite via `hora_inicio`).
- Table grouped by ação with subtotal rows; all columns sortable.
- Cascading filters: selecting ação triggers a `fetch` to `GET /relatorio-quantitativo/momentos` (JSON) which returns filtered `momentos` and `municipios`.
- Views: `resources/views/relatorio-quantitativo/`.

### LimeSurvey Integration & Avaliacoes Dashboard

**Routes & Views:**
- `GET /dashboards/leitura-mundo` (`dashboards.leitura-mundo`) — survey list via `DashboardController::leituraMundo()`.
- `GET /dashboards/avaliacoes?fonte=limesurvey&survey_id=X` (`dashboards.avaliacoes`) — dashboard entry point.
- `GET /dashboards/avaliacoes/dados?...` (`dashboards.avaliacoes.data`) — AJAX endpoint returning `{totais, perguntas, bi_matrizes, question_blocks, recentes}`.
- View partials in `resources/views/dashboards/avaliacoes/`: `_filtros`, `_cards-totais`, `_bi-matriz`, `_modal-respostas`.

**Data Flow:**
1. Frontend JS (`resources/js/dashboards/avaliacoes.js`) fetches `/dashboards/avaliacoes/dados` with filters.
2. `DashboardController::avaliacoesDataLimeSurvey()` → `LimeSurveyDashboardService::buildPayload($request)`.
3. Service returns structured payload with questions, responses, matrix analyses, organized blocks.

**Caching & Scheduling:**
- Service uses `Cache::remember()` with **database driver** (configurable TTL via `LIMESURVEY_CACHE_MINUTES`, default 5 min).
- **Cache keys:** `limesurvey:{surveyId}:questions`, `limesurvey:{surveyId}:responses`, `limesurvey:{surveyId}:answer_options:{qid}` (type-L questions only).
- **Daily warm-up:** `php artisan limesurvey:importar-dados` runs at 00:00 UTC (see `routes/console.php` for schedule), caching all active surveys for 24 hours. Fallback to on-demand fetch if scheduler fails.
- Manual trigger: `php artisan limesurvey:importar-dados` or `php artisan limesurvey:importar-dados --survey_id=X`.

**LimeSurvey Client** (`app/Services/LimeSurvey/LimeSurveyClient.php`):
- JSON-RPC 2.0 client; session-based (auto-acquired/released per call).
- Methods: `listQuestions(surveyId)`, `exportResponses(surveyId)` (CSV base64), `listParticipants()`, `getQuestionProperties(qid)`, `listSurveys()`.
- Config via `config/services.php` (env: `LIMESURVEY_URL`, `USERNAME`, `PASSWORD`, `SURVEY_ID`, `CACHE_MINUTES`, `VERIFY_SSL`, `TIMEOUT`).

**Service** (`app/Services/LimeSurvey/LimeSurveyDashboardService.php`):
- Normalizes questions, builds simple questions and matrix blocks.
- Supports município-level aggregation (email-to-município mapping).
- Infers question types: `texto`, `boolean`, `escala`, `numero`.
- Date filters from `de` / `ate` request params applied post-cache.

**Frontend Rendering** (Chart.js, not ApexCharts):
- Two paths: **new** (`question_blocks`) uses `renderSimpleQuestionCard`/`renderMatrixBlockCard`; **legacy** (`perguntas`/`bi_matrizes`) uses `renderLegacyCharts`.
- Circular charts (doughnut, polarArea): use `maintainAspectRatio: false` + `canvas.style.height` to prevent excessive height.

### PDF Generation (spatie/laravel-pdf + Browsershot)

**Stack:** `Pdf::view()` → `BrowsershotDriver` → Puppeteer/Chromium. Node.js instalado via fnm; path configurado em `.env` como `LARAVEL_PDF_NODE_BINARY`.

**Macro `withAlfaEjaBrand()`** — registrada em `AppServiceProvider::registerPdfMacros()`:
```php
Pdf::view('minha.view', $dados)
    ->format('a4')
    ->withAlfaEjaBrand()              // portrait: margens 28 14 22 14 mm (padrão)
    ->download('arquivo.pdf');

->withAlfaEjaBrand(35, 10, 25, 10)   // landscape
```
O macro define as margens do Puppeteer e os templates de header/footer com as imagens institucionais (`public/images/Alfa-Eja Header.png` e `Alfa-Eja Footer.png`) em base64.

**Return type dos controllers:** `PdfBuilder` implementa `Responsable`; retorná-lo de um controller é válido — Laravel chama `toResponse()` automaticamente. Declare `: PdfBuilder` ou omita o tipo. **Não use** `: Symfony\Component\HttpFoundation\Response`.

**Armadilhas críticas do Puppeteer — leia antes de mexer em PDFs:**

1. **Header/footer rodam em contexto HTML completamente isolado.** Não herdam CSS nem recursos do documento principal. Sempre incluir reset completo no template:
   ```html
   <style>*{margin:0;padding:0;box-sizing:border-box}html,body{width:100%;height:100%;font-size:0;line-height:0;overflow:hidden}</style>
   ```

2. **Imagens base64 no header/footer podem não renderizar.** O contexto isolado do Puppeteer não carrega dados base64 não previamente "vistos". Solução: adicionar `<img>` invisíveis com o mesmo `src` nos layouts Blade para pré-carregar:
   ```html
   {{-- em pdf-alfa-eja.blade.php e pdf-alfa-eja-landscape.blade.php --}}
   <img src="data:image/png;base64,..." style="position:absolute;width:0;height:0;opacity:0;pointer-events:none" aria-hidden="true">
   ```

3. **Header descolado do topo: usar `position:absolute`, não confiar em `margin:0`.** Chrome print tem espaçamento padrão que `margin:0` no body não elimina. Solução robusta:
   ```html
   <body style='position:relative'>
     <img src='...' style='position:absolute;top:0;left:0;width:100%;display:block'>
   </body>
   ```

4. **Footer desalinhado: `display:flex` no body pode não funcionar** porque a largura do body no contexto isolado não corresponde à página. Solução:
   ```html
   <body style='position:relative;width:100%;height:100%'>
     <img src='...' style='position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:61mm;display:block'>
   </body>
   ```

5. **`position:fixed` em Chrome PDF NÃO repete em cada página** (ao contrário do wkhtmltopdf). Cria uma página extra em branco no final. Nunca use `position:fixed` para header/footer — use os templates nativos `headerHtml`/`footerHtml` via macro.

6. **Rodapés legados das views wkhtmltopdf.** Views migradas podem ter `<div class="footer">` com texto no corpo — isso vira conteúdo normal e cria páginas extras. Verificar e remover ao migrar:
   ```bash
   grep -rn 'class="footer"' resources/views/ --include="*.blade.php" | grep -v vendor | grep -v mail
   ```

7. **Cache de views.** Ao modificar layouts PDF rodar `php artisan view:clear`.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/breeze (BREEZE) - v2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
