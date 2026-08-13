# VSP CRM

A business management application containing two independent modules on one Laravel codebase:

1. **CRM & Campaign Management** — clients, Meta/Google/LinkedIn/WhatsApp campaigns, lead capture and assignment, follow-ups, sales pipeline, conversion tracking, campaign reports, and a client portal.
2. **Internal Task Management** — work companies, projects, departments, tasks, direct assignment and open tasks, employee acceptance, availability and workload, a work timer, timesheets, file uploads, and creative review.

The two modules share authentication, employees, roles and permissions, notifications, file storage, and the activity log. They share nothing else.

## The module boundary

Dependencies point one way only:

```
App\Modules\Crm            ─┐
                            ├─►  App\Modules\Core
App\Modules\TaskManagement ─┘
```

- `App\Modules\Crm` must never reference `App\Modules\TaskManagement`, or the reverse.
- `App\Modules\Core` must never reference either module.
- No foreign key may cross between a `crm_*` table and a `tm_*` table.
- A sales follow-up (`crm_follow_ups`) is not a work task (`tm_tasks`). They are separate concepts with separate tables.

`tests/Arch/ModuleBoundaryTest.php` turns these rules into build failures. Run `composer test` before pushing.

## Layout

| Path | Contents |
| --- | --- |
| `app/Modules/Core/` | Shared kernel: users, auth, employees, departments, roles, notifications, media, activity log |
| `app/Modules/Crm/` | Everything CRM. Owns all `crm_*` tables |
| `app/Modules/TaskManagement/` | Everything task management. Owns all `tm_*` tables |
| `routes/web.php` | Shared routes only (home, dashboard, auth, settings) |
| `routes/crm.php` | Staff CRM, mounted at `/crm` |
| `routes/portal.php` | Client portal, mounted at `/portal` |
| `routes/tasks.php` | Task management, mounted at `/tasks` |
| `database/migrations/{core,crm,tasks}/` | One folder per module, each loaded by its own service provider |
| `database/factories/{Core,Crm,TaskManagement}/` | Factory namespace mirrors the module |
| `resources/js/Pages/{Core,Crm,TaskManagement}/` | Inertia pages, one folder per module |

Each module registers its own routes and migrations from its service provider in `app/Modules/*/Providers/`.

## Access control

Permissions are granted through roles, never directly to a person. The full catalogue of permission names lives in `App\Modules\Core\Enums\Ability`, so a typo in a check is a fatal error instead of a silent `false`. `Database\Seeders\Core\RolesAndPermissionsSeeder` writes the catalogue and the five built-in roles, and is safe to re-run after adding a case to the enum.

| Role | Holds |
| --- | --- |
| `super-admin` | Everything, via a `Gate::before` hook rather than permission rows. Cannot be edited or deleted through the UI |
| `admin` | Every ability in the catalogue |
| `manager` | Read access to people and the audit log, both modules, and full control of work companies, projects and task assignment |
| `employee` | Read access to people, plus Task Management: they can raise tasks and claim open work, but not hand work to anyone else |
| `client` | The client portal only. Never assignable from a staff screen |

Staff and client-portal users share the `users` table with a `user_type` discriminator instead of a second auth guard, which would have forked notifications, media ownership and the activity log's causer column into parallel implementations. Portal isolation is enforced in four independent layers: the route group, the `internal` middleware, a query scope, and policies. The permission list shared with the frontend only hides navigation and buttons — every action is authorized again server side.

Administration of the shared kernel lives under `/admin` (employees, departments, roles) and is registered by `CoreServiceProvider`, since it belongs to no single module.

## How work reaches a person

Task Management is built around one question: who is doing this, and did they agree to it? There are two answers, and the module treats them differently.

**Direct assignment.** A manager hands the task to someone. It sits at `assigned` until that person accepts, and only they can answer — not their manager, not an admin. Declining sends the task back to the open board with the reason recorded, rather than leaving it parked on someone who has said no.

**Open tasks.** A task published to the board has no assignee. Anyone with a staff profile can claim it, and a claim skips the acceptance step entirely: they chose it, so there is nothing left to agree to. Claims lock the row, so two people clicking at once cannot both win.

Both paths write to `tm_task_assignments`, which is why "who has held this task, and what did they say" is one query even for a task that has bounced between several people. Status changes go to `tm_task_status_history` instead — who holds a task and what state it is in are different questions with different tables.

```
draft ──► open ◄────────── (decline)
  │        │  └──► accepted (claim)
  └──► assigned ──► accepted ──► in_progress ──┬─► completed
                                               └─► in_review ⇄ revision
                                                        └─► approved ──► completed
```

`App\Modules\TaskManagement\Enums\TaskStatus` owns these transitions and `TaskWorkflow` is the only thing that writes them, so no controller can move a task sideways. Review is optional: work that produces nothing to approve closes out straight from `in_progress`. Editing a task cannot change its status, its assignment mode or its assignee — those go through the workflow endpoints under `/tasks/{task}/…` and nowhere else.

## Requirements

- PHP 8.3+ with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `intl`, `gd`, `exif`, `tokenizer`, `xml`, `bcmath`, `sodium`
- MySQL 8.0+
- Composer 2
- Node.js 20+

## Local setup (WampServer)

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Create the two databases:

```sql
CREATE DATABASE vsp_crm         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE vsp_crm_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then migrate and seed:

```bash
php artisan migrate --seed
```

Run the app with the dev server rather than an Apache vhost — Vite's HMR client is origin-sensitive and `artisan serve` avoids the friction:

```bash
composer dev
```

That runs the PHP server, the queue worker, the log tailer and Vite together on <http://localhost:8000>. To run them separately, use `php artisan serve` and `npm run dev`.

The seeded local login is `admin@vsp.test` with password `password`, which holds the `super-admin` role. Locally the seeder also creates `manager@vsp.test` and `designer@vsp.test` (same password) along with a sample company, project and tasks at each interesting point of the lifecycle — sign in as the designer to see the acceptance prompt and the open board from the other side. That demo data is skipped outside `local`.

### WampServer notes

- WampServer defaults to the **MyISAM** storage engine, whose 1000-byte key limit is too small for a `utf8mb4` unique index on `varchar(255)`. `config/database.php` pins `InnoDB` via `DB_ENGINE`; leave it set.
- Xdebug is loaded in the WampServer PHP CLI and slows Composer and the test suite considerably. Disable it for CLI runs and enable it only when debugging.
- The MySQL `root` account has no password by default. That is fine locally but must not carry into staging.

## Commands

| Command | Purpose |
| --- | --- |
| `composer dev` | Server, queue, logs and Vite together |
| `composer test` | Pest suite, including the architecture boundary tests |
| `composer analyse` | PHPStan / Larastan static analysis |
| `npm run build` | Production asset build |
| `npx tsc --noEmit` | Type-check the frontend |
| `npm run lint` | ESLint with autofix |
| `npm run format` | Prettier |

Tests run against the `vsp_crm_testing` MySQL database rather than SQLite, so MySQL-specific schema stays honest.

Feature tests render real Inertia pages, which resolve through the Vite manifest. Run `npm run build` after adding a page, or the test for it fails with `Unable to locate file in Vite manifest`.
