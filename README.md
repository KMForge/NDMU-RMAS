# NDMU-RMAS

**Notre Dame of Marbel University Research Management and Assistance System**

NDMU-RMAS is a role-based web application for managing the student research lifecycle at Notre Dame of Marbel University. It is designed to support research registration, proposal review, progress monitoring, adviser consultations, revisions, document review, defense scheduling, panel evaluations, approvals, reports, notifications, audit logs, and an institutional research repository.

The application uses a Laravel modular monolith architecture. Shared business rules are organized by research domain instead of being duplicated for every user role.

## Project status

This repository currently contains the production-oriented application foundation:

- Laravel 13 project setup
- Modular domain structure under `app/Modules`
- Multi-role access using Spatie Laravel Permission
- Email verification and active/approved account enforcement
- Role-specific route groups and dashboard placeholders
- Supabase PostgreSQL and private Storage configuration
- Secure storage contracts and signed URL support
- Livewire, Blade, Alpine.js, Tailwind CSS, and Vite setup
- Chart.js and FullCalendar frontend dependencies
- Database migrations and role/permission seeders
- Foundational automated tests
- GitHub Actions and deployment templates
- Architecture, routing, permissions, storage, and deployment documentation

The complete research workflow and authentication interface are not yet implemented. Current login and dashboard pages are placeholders for the next development phase.

## Planned system features

- Student registration, verification, and account approval
- Research project and group member management
- Proposal submission, review, revision, and approval
- Research milestone and progress tracking
- Adviser consultation records
- Research document versioning and protected downloads
- Defense requests, scheduling, panel assignments, and conflict checking
- Panel evaluation rubrics and scoring
- College and university research reports
- In-app, email, queued, and optional realtime notifications
- Administrative audit logs and system settings
- Searchable repository for approved and archived research

## Technology stack

### Backend

- PHP 8.3 or newer
- Laravel 13
- Laravel Eloquent ORM
- Laravel Form Requests, policies, gates, events, listeners, queues, and scheduler
- Livewire 4
- Spatie Laravel Permission
- DomPDF
- PHPUnit
- Laravel Pint

### Frontend

- Blade templates and reusable Blade components
- Livewire with bundled Alpine.js
- Tailwind CSS 4
- Vite 8
- Axios
- Chart.js
- FullCalendar

### Database and storage

- PostgreSQL hosted through Supabase
- Supabase Storage for private research documents
- Laravel local private storage as a development fallback
- Optional Supabase Realtime integration

### Development and deployment

- Composer and npm
- Git and GitHub
- GitHub Actions
- Hostinger-compatible deployment templates
- Nginx configuration template
- Optional Docker development configuration

## User roles

NDMU-RMAS supports multiple roles per user. It does not use a single `role` column in the users table.

| Role | Intended responsibilities |
|---|---|
| Student Researcher | Manage owned research, submit proposals and documents, respond to revisions, and view defense details |
| Research Adviser | Review assigned research, documents, progress, consultations, and revisions |
| Panelist | View assigned defenses and submit authorized evaluations |
| Research Facilitator | Coordinate proposals, documents, defenses, reports, and research workflows |
| College Dean | Review college-scoped research, approvals, evaluations, and reports |
| System Administrator | Manage users, permissions, settings, audit logs, and system operations |

Record access must still be checked through policies. Having a role or permission does not automatically grant access to every research record.

## System requirements

Install the following before setting up the project:

- PHP 8.3-8.5
- PHP extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo`, `pdo_pgsql`, `tokenizer`, `xml`, and `zip`
- Composer 2
- Node.js 22 or newer with npm
- Git
- A Supabase project or another PostgreSQL database

SQLite can be used for automated tests and a basic local smoke test. Supabase/PostgreSQL requires the `pdo_pgsql` PHP extension.

## Clone the repository

The official repository is:

[https://github.com/KMForge/NDMU-RMAS](https://github.com/KMForge/NDMU-RMAS)

Clone it with HTTPS:

```bash
git clone https://github.com/KMForge/NDMU-RMAS.git
cd NDMU-RMAS
```

If GitHub SSH authentication is already configured, you may instead use:

```bash
git clone git@github.com:KMForge/NDMU-RMAS.git
cd NDMU-RMAS
```

## Publish this existing local project to GitHub

These instructions are for the project owner, **KMForge**, when publishing the existing local `C:\NDMU-RMAS` project for the first time.

Create an empty GitHub repository with these settings:

- Owner: `KMForge`
- Repository name: `NDMU-RMAS`
- Repository URL: `https://github.com/KMForge/NDMU-RMAS`
- Add README: Off
- Add `.gitignore`: None
- Add license: None for the initial push

Do not initialize the GitHub repository with generated files because this local project already contains a README and `.gitignore`.

Open PowerShell in `C:\NDMU-RMAS` and configure the Git identity for this repository:

```powershell
git config user.name "KMForge"
git config user.email "sulibagakent@gmail.com"
```

These commands configure the identity locally for NDMU-RMAS. They do not change the Git identity used by unrelated repositories.

Confirm the configured identity:

```powershell
git config user.name
git config user.email
```

Check that secrets and generated dependencies are ignored before committing:

```powershell
git check-ignore .env
git check-ignore vendor
git check-ignore node_modules
git status
```

The first three commands should print the ignored paths. Then create the initial commit:

```powershell
git add .
git commit -m "Initial NDMU-RMAS Laravel project structure"
```

Connect the local repository to GitHub and push the `main` branch:

```powershell
git remote add origin https://github.com/KMForge/NDMU-RMAS.git
git push -u origin main
```

If an `origin` remote was already added with the wrong URL, correct it instead of adding another remote:

```powershell
git remote set-url origin https://github.com/KMForge/NDMU-RMAS.git
git push -u origin main
```

Verify the configured remote with:

```powershell
git remote -v
```

## Installation

### 1. Install backend dependencies

```bash
composer install
```

### 2. Create the local environment file

Linux or macOS:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 3. Generate the application key

```bash
php artisan key:generate
```

### 4. Configure the environment

Update `.env` with the application URL and Supabase PostgreSQL connection:

```dotenv
APP_NAME="NDMU-RMAS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
DB_SSLMODE=require

SUPABASE_URL=
SUPABASE_ANON_KEY=
SUPABASE_SERVICE_ROLE_KEY=
SUPABASE_STORAGE_BUCKET=research-manuscripts
SUPABASE_SIGNED_URL_TTL=300
SUPABASE_REALTIME_ENABLED=false
SUPABASE_REALTIME_ENDPOINT=

QUEUE_CONNECTION=database
```

The Supabase service-role key is server-only. Never add it to a variable beginning with `VITE_`, expose it through JavaScript, or commit it to Git.

For a temporary local SQLite setup, use:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=C:/absolute/path/to/ndmu-rmas/database/database.sqlite
```

Create the SQLite file if it does not exist.

### 5. Run migrations and seed roles and permissions

```bash
php artisan migrate --seed
```

The administrator seeder does not include insecure default credentials. To create the initial administrator, set these values before running the seeder:

```dotenv
ADMIN_NAME="System Administrator"
ADMIN_EMAIL=administrator@example.edu
ADMIN_PASSWORD=replace-with-a-strong-password
```

The password must contain at least 12 characters. Then run:

```bash
php artisan db:seed --class=SystemAdministratorSeeder
```

Remove `ADMIN_PASSWORD` from the environment after the administrator is created if it is no longer required.

### 6. Install and build frontend dependencies

```bash
npm install
npm run build
```

For frontend development with automatic rebuilding:

```bash
npm run dev
```

### 7. Start the application

```bash
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000) in a browser.

For a combined development server, queue worker, log viewer, and Vite process, run:

```bash
composer run dev
```

Windows users may also run the project setup script:

```powershell
.\scripts\setup.ps1
```

## Pull the latest changes

Before pulling, use `git status` and commit or stash any unfinished local work. Then download the latest NDMU-RMAS `main` branch from GitHub:

```bash
git remote -v
git switch main
git pull --ff-only origin main
composer install
npm install
php artisan migrate
npm run build
php artisan optimize:clear
```

For a newly cloned copy, the `origin` remote is configured automatically as:

```text
https://github.com/KMForge/NDMU-RMAS.git
```

Do not run `git reset --hard`, replace `.env`, or run destructive database commands just to update the project.

If you are working on a feature branch, update `main` first and then merge or rebase according to the team's Git policy:

```bash
git switch main
git pull --ff-only origin main
git switch <feature-branch>
git merge main
```

## Git contribution workflow

Create a focused branch for each change:

```bash
git switch -c feature/research-proposal-submission
```

After implementing and testing the change:

```bash
git status
git add <changed-files>
git commit -m "Add research proposal submission foundation"
git push -u origin feature/research-proposal-submission
```

Open a pull request into `main`. Do not commit `.env`, credentials, database dumps, uploaded research documents, generated build files, `vendor`, or `node_modules`.

## Main routes

The project currently registers the following routes:

| Method | URI | Route name | Access |
|---|---|---|---|
| GET | `/` | `home` | Public landing page |
| GET | `/login` | `login` | Guest; authentication placeholder |
| GET | `/verify-email` | `verification.notice` | Authenticated user |
| GET | `/student/dashboard` | `student.dashboard` | Verified, active Student Researcher with `research.view-own` |
| GET | `/adviser/dashboard` | `adviser.dashboard` | Verified, active Research Adviser with `research.view-assigned` |
| GET | `/panelist/dashboard` | `panelist.dashboard` | Verified, active Panelist with `evaluations.view-assigned` |
| GET | `/facilitator/dashboard` | `facilitator.dashboard` | Verified, active Research Facilitator with `defenses.manage` |
| GET | `/dean/dashboard` | `dean.dashboard` | Verified, active College Dean with `research.view-college` |
| GET | `/admin/dashboard` | `admin.dashboard` | Verified, active System Administrator with `settings.manage` |
| GET | `/api/v1/status` | `api.v1.status` | Public, API rate limited |

Display the current route table at any time with:

```bash
php artisan route:list --except-vendor
```

Additional routes are separated by responsibility:

```text
routes/
|-- web.php
|-- api.php
|-- auth.php
|-- student.php
|-- adviser.php
|-- panelist.php
|-- facilitator.php
|-- dean.php
|-- admin.php
`-- console.php
```

## Database and seeding notes

- Use migrations instead of making manual production schema changes.
- Do not edit migrations that have already been applied to a shared environment.
- Use foreign keys, indexes, unique constraints, and transactions where appropriate.
- The initial project convention uses integer primary keys.
- User roles and permissions are stored in Spatie Permission tables.
- Uploaded documents are not stored as binary database fields. PostgreSQL stores metadata and private object paths only.
- `RolePermissionSeeder` is safe to rerun and synchronizes the defined role-permission matrix.

Common database commands:

```bash
php artisan migrate
php artisan migrate:status
php artisan db:seed
```

Use `php artisan migrate:fresh --seed` only for a disposable local or test database because it deletes all existing tables and data.

## Private file storage

Confidential research files must use private Supabase buckets or the local `storage/app/private` fallback. They must never be uploaded directly to `public/`.

Configured private buckets include:

- `research-proposals`
- `research-manuscripts`
- `research-revisions`
- `defense-documents`
- `evaluation-sheets`
- `archived-research`
- `profile-photos`

Use randomized object names and authorized download endpoints or short-lived signed URLs. Keep original filenames only as metadata.

## Project structure

```text
app/
|-- APIs/Contracts/             # External integration contracts
|-- Clients/Supabase/           # Low-level Supabase HTTP clients
|-- Enums/                      # Workflow and account states
|-- ExternalServices/           # Calendar, PDF, export, and notification boundaries
|-- Http/                       # Controllers and middleware
|-- Integrations/Supabase/      # Storage and optional realtime adapters
|-- Models/                     # Shared Eloquent models
|-- Modules/                    # Business-domain modules
|-- Policies/                   # Record authorization
`-- Providers/                  # Service container bindings and rate limiting

database/
|-- factories/
|-- migrations/
`-- seeders/

resources/
|-- css/
|-- js/
`-- views/

routes/                         # Public, API, authentication, and role route files
storage/app/private/            # Local confidential-file fallback
tests/                          # Unit, feature, API, and integration tests
docs/                           # Architecture and operational documentation
deployment/                     # Hostinger deployment templates
docker/                         # Optional local container configuration
nginx/                          # Nginx virtual host example
scripts/                        # Setup, test, lint, and deployment helpers
```

Module folders start with a README explaining their responsibility. Add `Actions`, `Contracts`, `Data`, `Livewire`, `Policies`, `Queries`, or `Services` only when the module contains real implementation in that category.

## Testing and code quality

Run the backend test suite:

```bash
php artisan test
```

Check PHP formatting:

```bash
vendor/bin/pint --test
```

Automatically format PHP files:

```bash
vendor/bin/pint
```

Build production frontend assets:

```bash
npm run build
```

Run the combined project check:

```bash
composer run check
```

The current foundation includes tests for account approval, email verification, role-based route access, permission enforcement, user policy enforcement, API status, and Supabase object-path security.

## Queue and scheduler

Run a local queue worker with:

```bash
php artisan queue:work
```

Run the scheduler locally with:

```bash
php artisan schedule:work
```

Production should use a persistent process manager for queues and a cron entry that executes `php artisan schedule:run` every minute. Examples are available in `deployment/hostinger`.

## Deployment notes

- Configure the web server document root to the Laravel `public/` directory.
- Set `APP_ENV=production`, `APP_DEBUG=false`, and use HTTPS.
- Enable PHP `pdo_pgsql` and all required extensions.
- Store production environment values outside Git.
- Back up PostgreSQL before applying production migrations.
- Run queue workers and the Laravel scheduler.
- Create all Supabase document buckets as private and configure object-access policies.
- Review the Nginx and Hostinger templates before use; they intentionally contain no production credentials.

## Security rules

- Never rely on role checks in the interface alone.
- Authorize protected records through policies and permissions.
- Validate every form and uploaded file.
- Restrict file MIME types and maximum sizes.
- Reject path traversal and generate random storage object names.
- Use private storage for manuscripts, revisions, evaluations, and profile photos.
- Do not log passwords, tokens, signed URLs, or confidential document contents.
- Use database transactions for multi-step approvals.
- Audit important administrative actions.
- Rate limit authentication and public API routes.
- Never expose the Supabase service-role key to the browser.

## Documentation

More detailed project guidance is available in:

- [Architecture](docs/ARCHITECTURE.md)
- [Development setup](docs/DEVELOPMENT_SETUP.md)
- [Modules](docs/MODULES.md)
- [Database design](docs/DATABASE_DESIGN.md)
- [Permissions](docs/PERMISSIONS.md)
- [Routing](docs/ROUTING.md)
- [Storage security](docs/STORAGE.md)
- [Testing](docs/TESTING.md)
- [Deployment](docs/DEPLOYMENT.md)
- [Public asset guide](docs/ASSET_GUIDE.md)

## Important notes

- Authentication pages are placeholders and must not be treated as a completed login or registration flow.
- Laravel Excel is not installed because its current stable release is incompatible with this PHP 8.5/Laravel 13 environment. Do not bypass Composer platform or security checks.
- No default administrator password is committed to this repository.
- Run tests, formatting, and the frontend build before opening a pull request or declaring a task complete.
