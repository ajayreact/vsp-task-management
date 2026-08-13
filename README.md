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

The seeded local login is `admin@vsp.test` with password `password`.

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
