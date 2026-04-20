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
- **Services:** `app/Services/` — `AvaliacaoRespostasDashboardService`, `ParticipantesExclusivosService`, and BI services.
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

### Avaliacoes Dashboard (LimeSurvey integration)

Entry: `resources/views/dashboards/leitura-mundo.blade.php` → links to `dashboards.avaliacoes` with `?fonte=limesurvey&survey_id=`.

View partials in `resources/views/dashboards/avaliacoes/`: `_filtros`, `_cards-totais`, `_bi-matriz`, `_modal-respostas`.

The JS module `resources/js/dashboards/avaliacoes.js` renders all charts client-side via Chart.js (not ApexCharts, which is used only in Livewire `app/Livewire/Graficos/` components). Two rendering paths:
- **New path** (`question_blocks` in API payload): uses `renderSimpleQuestionCard` / `renderMatrixBlockCard` — full-width cards (`col-12`); circular charts (doughnut, polarArea) automatically reduced to `col-12 col-lg-6`.
- **Legacy path** (`perguntas` in API payload): uses `renderLegacyCharts` — full-width cards (`col-12`).

**Chart.js sizing constraint:** circular chart types default to aspect ratio 1:1 — always use `maintainAspectRatio: false` + `canvas.style.height` for doughnut/polarArea, otherwise they expand to fill the full column height and become huge.
