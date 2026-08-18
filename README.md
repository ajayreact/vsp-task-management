# VSP CRM

A business management application for internal studio work: **Core Administration** plus **Task Management**.

Staff sign in once, land on the Command Center at `/dashboard`, and run delivery from work clients through projects, tasks, time, and creative review.

## Main modules

- Employee Management
- Departments
- Designations
- Roles and Permissions
- Task Management Clients
- Projects
- Tasks
- Deliverables / Creative Proofs
- Shareable Client Links
- Proof Retention
- Comments
- Checklists
- Subtasks
- Reminders
- Recurring Tasks
- In-app Notifications
- Browser/System Notifications
- Notification Sound Management

## Removed modules

The following modules have been removed from this codebase:

- CRM
- Campaign Management
- Lead Management
- Client Portal

Legacy routes such as `/crm` and `/portal` return 404.

## Architecture

Dependencies point one way only:

```
App\Modules\TaskManagement  ──►  App\Modules\Core
```

- `App\Modules\Core` must never reference Task Management.
- Work clients live on `tm_companies` and are shown at `/tasks/clients`. They are not CRM clients.

| Path | Contents |
| --- | --- |
| `app/Modules/Core/` | Shared kernel: users, auth, employees, departments, designations, roles, notifications, media, activity log |
| `app/Modules/TaskManagement/` | Task management module. Owns all `tm_*` tables |
| `routes/web.php` | Shared routes (home, dashboard, auth, settings) |
| `routes/admin.php` | Administration, mounted at `/admin` |
| `routes/tasks.php` | Task management, mounted at `/tasks` |
| `routes/share.php` | Public deliverable share links at `/share/{token}` |
| `database/migrations/{core,tasks}/` | One folder per module, loaded by its service provider |
| `resources/js/Pages/{Core,TaskManagement}/` | Inertia pages, one folder per module |

Historical `database/migrations/crm/` files remain so already-run databases can migrate forward; they must not be edited.

## Requirements

- PHP 8.3+ with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `intl`, `gd`, `exif`, `tokenizer`, `xml`, `bcmath`, `sodium`
- MySQL 8.0+
- Composer 2
- Node.js 20+

## Local installation

```bash
git clone <your-private-repo-url> vsp-crm
cd vsp-crm

composer install
npm install
```

## Environment setup

Copy the example environment file and generate an application key:

```bash
cp .env.example .env
php artisan key:generate
```

Review `.env.example` for all required variables. Key defaults for local development:

| Variable | Development value |
| --- | --- |
| `APP_URL` | `http://localhost:8000` |
| `DB_DATABASE` | `vsp_crm` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | *(empty for local WampServer MySQL)* |
| `DB_ENGINE` | `InnoDB` |
| `MEDIA_DISK` | `public` |
| `BROADCAST_CONNECTION` | `reverb` |

For automated tests, use a separate testing database (`vsp_crm_testing`). Do **not** commit `.env.testing`. Either copy `.env.example` to `.env.testing` with testing overrides, or rely on `phpunit.xml` and `composer test` (see Testing below).

## Database setup

Create two MySQL databases:

```sql
CREATE DATABASE vsp_crm         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE vsp_crm_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

- **Development** uses `vsp_crm`
- **Tests** use `vsp_crm_testing`

WampServer defaults to the **MyISAM** storage engine, whose 1000-byte key limit is too small for a `utf8mb4` unique index on `varchar(255)`. `config/database.php` pins `InnoDB` via `DB_ENGINE`; leave it set.

## Migration commands

Run migrations for local development:

```bash
php artisan migrate
```

To migrate and seed in one step:

```bash
php artisan migrate --seed
```

## Seeder instructions

The main seeder creates roles, permissions, departments, designations, and the Super Admin account:

```bash
php artisan db:seed
```

Or run the full stack via `php artisan migrate --seed`.

After seeding, sign in with the Super Admin account created by `DatabaseSeeder`. In local environments, `DemoWorkSeeder` also creates sample work clients, projects, and tasks.

**Important:** Change the seeded Super Admin password immediately after first login in any shared or production environment.

## Running the application

For local development with Vite HMR, queue worker, logs, and Reverb:

```bash
composer dev
```

That runs the PHP server, queue worker, log tailer, Reverb, and Vite together on <http://localhost:8000>.

To run components separately:

```bash
php artisan serve
npm run dev
```

## Frontend build

Install dependencies and build production assets:

```bash
npm install
npm run build
```

Run `npm run build` after adding new Inertia pages — feature tests resolve pages through the Vite manifest and will fail if the manifest is stale.

Additional frontend commands:

| Command | Purpose |
| --- | --- |
| `npx tsc --noEmit` | Type-check the frontend |
| `npm run lint` | ESLint with autofix |
| `npm run format` | Prettier |

## Testing instructions

Run the full test suite:

```bash
composer test
```

This command:

1. Clears cached config
2. Runs `php artisan testing:prepare-database` to prepare the isolated test database
3. Runs `php artisan test`

Tests use the **`vsp_crm_testing`** database. Development uses **`vsp_crm`**. These must remain separate.

Testing is isolated from development and production:

- `.env.testing` (local only, not committed) pins `DB_DATABASE=vsp_crm_testing` and uses in-memory/array drivers for cache, mail, queue, and sessions
- `phpunit.xml` repeats the same database name and credentials so PHPUnit never falls back to `.env`
- `tests/bootstrap.php` acquires a process lock so two suites cannot reset the same database concurrently
- Do not run multiple `php artisan test` processes at the same time against the shared testing database

Static analysis:

```bash
composer analyse
```

## Upload limits

| Upload type | Maximum size |
| --- | --- |
| Images | 20 MB |
| Videos / Reels | 100 MB |
| Documents / Design proofs | 50 MB |
| Task attachments | 50 MB |
| Notification sounds | 5 MB |

See `docs/deployment-upload-limits.md` for production PHP and web server configuration.

## Useful commands

| Command | Purpose |
| --- | --- |
| `composer dev` | Server, queue, logs, Reverb, and Vite together |
| `composer test` | Pest suite, including architecture boundary tests |
| `composer analyse` | PHPStan / Larastan static analysis |
| `npm run build` | Production asset build |

## Documentation

- `docs/vsp-crm-handbook.html` — product handbook
- `docs/task-management-guide.html` — task management guide
- `docs/deployment-upload-limits.md` — production upload configuration

## License

MIT
