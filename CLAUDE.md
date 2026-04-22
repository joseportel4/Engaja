# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Engaja** is a Laravel 12 application for managing educational events, enrollments, attendance, and engagement reports for the Alfa-EJA project. It tracks formations, workshops, meetings, and live sessions for educational institutions.

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Bootstrap 5 + Blade + Livewire 4
- **Database:** PostgreSQL
- **Auth:** Laravel Breeze + Spatie Laravel Permission
- **PDF:** barryvdh/laravel-dompdf + setasign/fpdi
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
- `Presenca` — attendance record of a Participante at an Atividade.
- `Agendamento` — scheduling of a Participante for an AtividadeAcao.

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
- **PDF:** `app/Pdf/` — PDF generation classes; views in `resources/views/layouts/pdf-alfa-eja.blade.php`.
- **ViewModels:** `app/ViewModels/` — view data transformation.
- **Policies:** `app/Policies/` — model-level authorization.

### Import Flow (Presença/Inscrição)

Imports follow a multi-step preview/confirm pattern:
1. Upload → parse xlsx → store in session
2. Preview page (paginated)
3. Confirm → persist to database

### Seeding

`RolesPermissionsSeeder` sets up all roles and permissions. `DatabaseSeeder` creates a default admin user (`admin@engaja.local`) with sample events/activities. Always run `--seed` on fresh installs.

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
