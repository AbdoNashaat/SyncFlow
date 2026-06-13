# SyncFlow

A collaborative workspace where teams create projects, assign tasks, upload attachments, and track progress.

---

## Tech Stack

| Layer       | Technology                              |
|-------------|-----------------------------------------|
| Backend     | PHP 8.3, Laravel 11 (MVC, RESTful API) |
| Database    | SQLite (dev) / MySQL 8.0 (production)   |
| Frontend    | Svelte, Tailwind CSS                     |
| Auth        | Laravel Sanctum (token-based API auth)  |
| File Storage| AWS S3 (via Flysystem)                  |
| DevOps      | GitHub Actions CI/CD                    |
| Infrastructure | AWS EC2, RDS, S3                     |

---

## Project Status

All phases are complete ✅
- Phase 1: Planning & database schema completed
- Phase 2: Backend API and authentication implemented
- Phase 3: Frontend app scaffold and feature integration completed
- Phase 4: Deployment and CI/CD prepared

See the `docs/` folder for full API and feature documentation, including auth, projects, tasks, attachments/comments, dashboard, and daily digest.

---

## Phase 1 — Planning & Database Schema (Complete ✅)

### 1.1 Git Repository

| Action | Detail |
|--------|--------|
| `git init` | Repository initialized at `/home/abdelghafaar/Desktop/Abdo/SyncFlow` |
| `.gitignore` | Excludes `vendor/`, `node_modules/`, `.env`, `*.sqlite`, `storage/`, `frontend/dist/` |
| **Branch: `main`** | Production-ready code |
| **Branch: `dev`** | Integration branch (default for PRs) |
| **Branch: `feature/database-schema`** | Schema work — merged into `dev` via `--no-ff` |

### 1.2 Laravel Scaffold

```
composer create-project laravel/laravel .
composer require laravel/sanctum
```

Laravel 11 boilerplate installed with:
- Sanctum for API token auth
- SQLite database at `database/syncflow.sqlite`
- `.env` configured with `APP_NAME=SyncFlow`, commented MySQL config ready

### 1.3 Database Schema — 6 Custom Tables + Users Enhancement

#### `users` (modified default migration)

| Column | Type | Constraints |
|--------|------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| name | VARCHAR(255) | NOT NULL |
| email | VARCHAR(255) | UNIQUE, NOT NULL |
| password | VARCHAR(255) | NOT NULL |
| avatar_url | VARCHAR(255) | NULLABLE |
| timezone | VARCHAR(50) | NULLABLE |
#### `projects`

| Column | Type | Constraints |
|--------|------|-------------|
| id | BIGINT UNSIGNED | PK |
| name | VARCHAR(255) | NOT NULL |
| description | TEXT | NULLABLE |
| owner_id | BIGINT UNSIGNED | FK → users, CASCADE DELETE, **INDEXED** |
| status | ENUM('active','archived','completed') | DEFAULT 'active', **INDEXED** |
| timestamps | |

#### `project_members`

| Column | Type | Constraints |
|--------|------|-------------|
| id | BIGINT UNSIGNED | PK |
| project_id | BIGINT UNSIGNED | FK → projects, CASCADE DELETE, **INDEXED** |
| user_id | BIGINT UNSIGNED | FK → users, CASCADE DELETE, **INDEXED** |
| role | ENUM('owner','editor','viewer') | DEFAULT 'viewer' |
| timestamps | |
| | | **UNIQUE(project_id, user_id)** |

#### `tasks`

| Column | Type | Constraints |
|--------|------|-------------|
| id | BIGINT UNSIGNED | PK |
| project_id | BIGINT UNSIGNED | FK → projects, CASCADE DELETE, **INDEXED** |
| title | VARCHAR(255) | NOT NULL |
| description | TEXT | NULLABLE |
| status | ENUM('todo','in_progress','review','done') | DEFAULT 'todo', **INDEXED** |
| priority | ENUM('low','medium','high','urgent') | DEFAULT 'medium' |
| due_date | DATE | NULLABLE, **INDEXED** |
| assigned_user_id | BIGINT UNSIGNED | FK → users, NULL ON DELETE, **INDEXED** |
| created_by | BIGINT UNSIGNED | FK → users |
| position | INT UNSIGNED | DEFAULT 0 |
| timestamps | |

**Composite Indexes for Performance:**
- `(project_id, status, position)` — board column queries
- `(assigned_user_id, status)` — "My Tasks" filter

#### `task_attachments`

| Column | Type | Constraints |
|--------|------|-------------|
| id | BIGINT UNSIGNED | PK |
| task_id | BIGINT UNSIGNED | FK → tasks, CASCADE DELETE, **INDEXED** |
| user_id | BIGINT UNSIGNED | FK → users, CASCADE DELETE, **INDEXED** |
| filename | VARCHAR(255) | NOT NULL |
| original_name | VARCHAR(255) | NOT NULL |
| mime_type | VARCHAR(127) | NOT NULL |
| size | INT UNSIGNED | NOT NULL (bytes) |
| s3_key | VARCHAR(512) | NOT NULL |
| timestamps | | |

#### `task_comments`

| Column | Type | Constraints |
|--------|------|-------------|
| id | BIGINT UNSIGNED | PK |
| task_id | BIGINT UNSIGNED | FK → tasks, CASCADE DELETE, **INDEXED** |
| user_id | BIGINT UNSIGNED | FK → users, CASCADE DELETE, **INDEXED** |
| timestamps | | |

### 1.4 Eloquent Models

All models in `app/Models/` with `HasFactory` trait and full relationship definitions:

| Model | Relationships |
|-------|--------------|
| `User` | `hasMany` ownedProjects, `belongsToMany` projects (via members), `hasMany` assignedTasks, `hasMany` createdTasks |
| `Project` | `belongsTo` owner, `belongsToMany` members (with pivot `role`), `hasMany` tasks |
| `Task` | `belongsTo` project, `belongsTo` assignedUser, `belongsTo` creator, `hasMany` attachments, `hasMany` comments |
| `TaskAttachment` | `belongsTo` task, `belongsTo` user |
| `TaskComment` | `belongsTo` task, `belongsTo` user |

### 1.5 Factories

| Factory | Faker Data |
|---------|------------|
| `UserFactory` | 25 users (1 demo admin: `abdelghafaar@syncflow.dev` / `password`) |
| `ProjectFactory` | 10 projects with realistic names (Q3 Launch, Mobile Redesign, etc.) |
| `TaskFactory` | ~200 tasks with varied status/priority/due dates |
| `TaskAttachmentFactory` | ~70 attachments with realistic mime types |
| `TaskCommentFactory` | ~400 comments across tasks |

**Seeder Summary:**
```
Seeded:
  - 25 users
  - 10 projects
  - 202 tasks
  - 73 attachments
  - 405 comments
```

### 1.6 Branch Structure

```
main ──── 774ccd1 chore: initialize git repo with .gitignore
  └── dev ──── 6905e28 feat: merge database schema, models, factories, and seeders
       └── feature/database-schema ──── 1189b8e feat: add database schema, models, factories, and seeders
```

---

## Phase 2 — Backend Development & API (Complete ✅)
- Laravel Sanctum authentication implemented for registration, login, logout, and current user.
- RESTful API controllers for projects, tasks, attachments, comments, and dashboard.
- Form requests, validation, API resources, policies, and feature tests are in place.
- AWS S3-backed attachment upload support and scheduled daily digest command.

## Phase 3 — Frontend Development (Complete ✅)
- Svelte + Vite application scaffolded in `frontend/`.
- Tailwind CSS and SPA routing integration.
- Auth flow, dashboard, project board, task detail UI, comments, and attachments support.
- Loading, empty, and error state handling included.

## Phase 4 — Deployment & CI/CD (Complete ✅)
- GitHub Actions CI/CD workflow prepared.
- AWS deployment guidance for EC2, RDS, and S3.
- Laravel scheduler support for the daily digest command.
- Production-ready MySQL configuration documented.

---

## Development Setup

```bash
# Clone & install
git clone <repo-url>
cd SyncFlow
composer install
cp .env.example .env
php artisan key:generate

# Database
touch database/syncflow.sqlite
php artisan migrate --seed

# Serve
php artisan serve
```

### MySQL (Production)

Uncomment MySQL block in `.env` and set credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=syncflow
DB_USERNAME=syncflow
DB_PASSWORD=syncflow_pass
```

---

## File Structure (Phase 1)

```
SyncFlow/
├── .gitignore
├── app/
│   └── Models/
│       ├── User.php
│       ├── Project.php
│       ├── Task.php
│       ├── TaskAttachment.php
│       └── TaskComment.php
├── database/
│   ├── factories/
│   │   ├── UserFactory.php
│   │   ├── ProjectFactory.php
│   │   ├── TaskFactory.php
│   │   ├── TaskAttachmentFactory.php
│   │   └── TaskCommentFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 0001_01_01_000003_create_projects_table.php
│   │   ├── 0001_01_01_000004_create_tasks_table.php
│   │   ├── 0001_01_01_000005_create_task_attachments_table.php
│   │   └── 0001_01_01_000006_create_task_comments_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── config/
│   ├── sanctum.php (published)
│   └── ...
├── routes/
│   ├── api.php
│   ├── web.php
│   └── console.php
└── README.md
```
