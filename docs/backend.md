# SyncFlow Backend — Phase 2

## Overview

RESTful API built with Laravel 11, authenticated via Sanctum tokens. Serves the Svelte SPA frontend and mobile clients.

**Base URL:** `http://localhost:8000/api`

---

## Authentication (Laravel Sanctum)

Token-based auth. Svelte stores token in `Authorization: Bearer` header.

### Endpoints

#### `POST /api/register`
Create new user.

```json
// Request
{ "name": "Abdelghafaar", "email": "a@b.com", "password": "secret", "password_confirmation": "secret" }

// Response 201
{ "user": { "id": 1, "name": "Abdelghafaar", "email": "a@b.com", "avatar_url": null }, "token": "1|abc..." }
```

#### `POST /api/login`
Authenticate and receive token.

```json
// Request
{ "email": "a@b.com", "password": "secret" }

// Response 200
{ "user": { ... }, "token": "1|abc..." }

// Response 401
{ "message": "Invalid credentials" }
```

#### `POST /api/logout`
Revoke current token. Requires `Authorization: Bearer`.

```json
// Response 200
{ "message": "Logged out" }
```

#### `GET /api/user`
Get authenticated user. Requires token.

```json
// Response 200
{ "data": { "id": 1, "name": "Abdelghafaar", "email": "a@b.com", "avatar_url": null, "timezone": "Africa/Cairo" } }
```

---

## Projects

All project endpoints require auth. User sees only projects they own or are members of.

#### `GET /api/projects`
List user's projects. Paginated (15 per page).

```json
// Response 200
{
  "data": [
    {
      "id": 1,
      "name": "Q3 Product Launch",
      "description": "...",
      "status": "active",
      "owner": { "id": 1, "name": "Abdelghafaar" },
      "members_count": 5,
      "tasks_count": 18,
      "created_at": "2026-06-12T19:03:14.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "total": 10 }
}
```

#### `POST /api/projects`
Create project. Creator becomes owner + member.

```json
// Request
{ "name": "New Project", "description": "Optional desc", "status": "active" }

// Response 201
{ "data": { "id": 11, "name": "New Project", ... } }
```

#### `GET /api/projects/{project}`
Show project with members.

```json
// Response 200
{
  "data": {
    "id": 1,
    "name": "Q3 Product Launch",
    "description": "...",
    "status": "active",
    "owner": { "id": 1, "name": "Abdelghafaar" },
    "members": [
      { "id": 2, "name": "John Doe", "pivot": { "role": "editor" } }
    ],
    "tasks_count": 18
  }
}
```

#### `PUT /api/projects/{project}`
Update project. Owner or editor role required.

```json
// Request
{ "name": "Updated Name", "status": "archived" }

// Response 200
{ "data": { ... } }
```

#### `DELETE /api/projects/{project}`
Delete (archive) project. Owner only.

```json
// Response 200
{ "message": "Project deleted" }
```

---

## Tasks

All task endpoints scoped under a project.

#### `GET /api/projects/{project}/tasks`
List tasks with filters. Supports query params.

| Param | Example | Description |
|-------|---------|-------------|
| `status` | `todo,in_progress` | Comma-separated filter |
| `priority` | `high,urgent` | Comma-separated filter |
| `assigned_user` | `3` | Filter by assignee |
| `search` | `deploy` | Search title + description |

```json
// Response 200
{
  "data": [
    {
      "id": 1,
      "title": "Set up CI/CD pipeline",
      "status": "in_progress",
      "priority": "high",
      "due_date": "2026-06-20",
      "position": 2,
      "assigned_user": { "id": 3, "name": "Jane" },
      "creator": { "id": 1, "name": "Abdelghafaar" },
      "attachments_count": 1,
      "comments_count": 3,
      "created_at": "2026-06-12T19:03:14.000000Z"
    }
  ],
  "meta": { ... }
}
```

#### `POST /api/projects/{project}/tasks`
Create task within project. `created_by` set to auth user.

```json
// Request
{
  "title": "Fix login bug",
  "description": "Token expired edge case",
  "status": "todo",
  "priority": "high",
  "due_date": "2026-06-20",
  "assigned_user_id": 3
}

// Response 201
{ "data": { "id": 203, ... } }
```

#### `GET /api/projects/{project}/tasks/{task}`
Show full task with attachments + comments.

```json
// Response 200
{
  "data": {
    "id": 1,
    "title": "...",
    "description": "...",
    "status": "in_progress",
    "priority": "high",
    "due_date": "2026-06-20",
    "position": 2,
    "assigned_user": { ... },
    "creator": { ... },
    "attachments": [
      { "id": 1, "original_name": "report.pdf", "size": 204800, "mime_type": "application/pdf" }
    ],
    "comments": [
      { "id": 1, "content": "Looking good!", "user": { "id": 2, "name": "Jane" }, "created_at": "..." }
    ]
  }
}
```

#### `PUT /api/projects/{project}/tasks/{task}`
Update task fields.

```json
// Request
{ "status": "done", "position": 0 }

// Response 200
{ "data": { ... } }
```

#### `DELETE /api/projects/{project}/tasks/{task}`
Delete task.

```json
// Response 200
{ "message": "Task deleted" }
```

#### `PATCH /api/projects/{project}/tasks/{task}/position`
Drag-drop position update.

```json
// Request
{ "status": "in_progress", "position": 3 }

// Response 200
{ "data": { ... } }
```

**Batch reorder:** Send `PATCH` on all affected tasks in the source + destination columns (optional, for multi-drag support).

---

## Attachments

#### `POST /api/projects/{project}/tasks/{task}/attachments`
Upload file(s). Multipart form-data.

```json
// Request (multipart/form-data)
{ "attachments[]": <file1>, "attachments[]": <file2> }

// Response 201
{
  "data": [
    { "id": 5, "original_name": "screenshot.png", "size": 102400, "mime_type": "image/png" }
  ]
}
```

Files stored on S3 at `attachments/{task_id}/{uuid}-{filename}`.
Max file size: 10MB. Allowed types: jpg, png, gif, pdf, docx, xlsx, zip.

#### `DELETE /api/tasks/{task}/attachments/{attachment}`
Delete attachment from S3 and database.

```json
// Response 200
{ "message": "Attachment deleted" }
```

---

## Comments

#### `GET /api/projects/{project}/tasks/{task}/comments`
List comments (paginated, newest last).

#### `POST /api/projects/{project}/tasks/{task}/comments`
Add comment.

```json
// Request
{ "content": "Let me review this" }

// Response 201
{ "data": { "id": 406, "content": "...", "user": { "id": 1, "name": "Abdelghafaar" }, "created_at": "..." } }
```

#### `DELETE /api/comments/{comment}`
Delete own comment (or any comment if project owner).

```json
// Response 200
{ "message": "Comment deleted" }
```

---

## Dashboard

#### `GET /api/dashboard`
Aggregated stats for authenticated user.

```json
// Response 200
{
  "data": {
    "total_projects": 4,
    "overdue_tasks": 3,
    "completed_this_week": 7,
    "by_status": {
      "todo": 12,
      "in_progress": 8,
      "review": 3,
      "done": 15
    },
    "recent_activity": [
      { "type": "task_updated", "task_title": "Fix login", "project_name": "Q3 Launch", "time": "2 hours ago" }
    ]
  }
}
```

---

## Validation Rules

| Endpoint | Field | Rules |
|----------|-------|-------|
| Register | name | required, string, max:255 |
| | email | required, email, unique:users |
| | password | required, string, min:8, confirmed |
| Login | email | required, email |
| | password | required |
| Create/Update Project | name | required, string, max:255 |
| | description | nullable, string |
| | status | nullable, in:active,archived,completed |
| Create/Update Task | title | required, string, max:255 |
| | description | nullable, string |
| | status | nullable, in:todo,in_progress,review,done |
| | priority | nullable, in:low,medium,high,urgent |
| | due_date | nullable, date, after_or_equal:today |
| | assigned_user_id | nullable, exists:users,id |
| Upload Attachment | file | required, file, max:10240, mimes:jpg,png,gif,pdf,docx,xlsx,zip |
| Create Comment | content | required, string, max:10000 |

---

## Error Responses

| Code | Body |
|------|------|
| 401 | `{ "message": "Unauthenticated" }` |
| 403 | `{ "message": "Forbidden", "errors": { "project": ["You are not a member of this project"] } }` |
| 404 | `{ "message": "Not found" }` |
| 422 | `{ "message": "Validation failed", "errors": { "title": ["The title field is required"] } }` |
| 500 | `{ "message": "Server error" }` |

---

## Authorization Gates

- **View project:** User is owner OR member
- **Update project:** User is owner OR member with `editor`/`owner` role
- **Delete project:** Owner only
- **View task:** User is member of parent project
- **Update task:** User is member with edit role
- **Delete task:** User is member with edit role or task creator
- **Delete comment:** Comment author OR project owner

---

## Controllers Structure

```
app/Http/Controllers/Api/
├── AuthController.php       — register, login, logout, user
├── ProjectController.php    — index, store, show, update, destroy
├── TaskController.php       — index, store, show, update, destroy, updatePosition
├── AttachmentController.php — store, destroy
├── CommentController.php    — index, store, destroy
└── DashboardController.php  — index
```

---

## Form Requests

```
app/Http/Requests/
├── StoreProjectRequest.php
├── UpdateProjectRequest.php
├── StoreTaskRequest.php
├── UpdateTaskRequest.php
├── UpdateTaskPositionRequest.php
├── StoreCommentRequest.php
├── StoreAttachmentRequest.php
├── LoginRequest.php
└── RegisterRequest.php
```

---

## API Resources

```
app/Http/Resources/
├── UserResource.php
├── ProjectResource.php
├── ProjectDetailResource.php
├── TaskResource.php
├── TaskDetailResource.php
├── AttachmentResource.php
├── CommentResource.php
└── DashboardResource.php
```

---

## Daily Digest Command

```
app/Console/Commands/SendDailyDigest.php
```

Scheduled in `routes/console.php`:

```php
Schedule::command('app:send-daily-digest')->dailyAt('08:00');
```

- Queries tasks where `due_date < today()` AND `status != done`
- Groups by `assigned_user_id`
- Logs digest to `storage/logs/laravel.log` (or sends email when mail driver configured)
- Output format:

```
[SyncFlow Daily Digest] 2026-06-13
User: Jane (jane@example.com) — 3 overdue tasks:
  - Fix login bug (due: 2026-06-10) [Project: Q3 Launch]
  - Update API docs (due: 2026-06-11) [Project: Mobile App]
  - Deploy hotfix (due: 2026-06-09) [Project: Security Audit]
```

Manual run: `php artisan app:send-daily-digest`

---

## S3 File Storage

Configuration in `config/filesystems.php`:

```php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
],
```

Path pattern: `attachments/{task_id}/{uuid}-{original_name}`

Files served via temporary signed URLs (15-minute expiry).

---

## Tests

```
tests/Feature/
├── AuthTest.php           — register, login, logout, unauthenticated access
├── ProjectTest.php        — CRUD, ownership guard, member access
├── TaskTest.php           — CRUD, position update, status filter, search
├── AttachmentTest.php     — upload validation, S3 mock, delete
├── CommentTest.php        — create, delete ownership
├── DashboardTest.php      — correct aggregates
└── DailyDigestTest.php    — command output, overdue detection
```

Run: `php artisan test`

CI: GitHub Actions runs tests with MySQL service container before deploy.

---

## File Tree

```
SyncFlow/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── SendDailyDigest.php
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── ProjectController.php
│   │   │   ├── TaskController.php
│   │   │   ├── AttachmentController.php
│   │   │   ├── CommentController.php
│   │   │   └── DashboardController.php
│   │   ├── Requests/
│   │   │   ├── StoreProjectRequest.php
│   │   │   ├── UpdateProjectRequest.php
│   │   │   ├── StoreTaskRequest.php
│   │   │   ├── UpdateTaskRequest.php
│   │   │   ├── UpdateTaskPositionRequest.php
│   │   │   ├── StoreCommentRequest.php
│   │   │   ├── StoreAttachmentRequest.php
│   │   │   ├── LoginRequest.php
│   │   │   └── RegisterRequest.php
│   │   └── Resources/
│   │       ├── UserResource.php
│   │       ├── ProjectResource.php
│   │       ├── ProjectDetailResource.php
│   │       ├── TaskResource.php
│   │       ├── TaskDetailResource.php
│   │       ├── AttachmentResource.php
│   │       ├── CommentResource.php
│   │       └── DashboardResource.php
│   └── Models/ (Phase 1)
├── config/
│   └── filesystems.php (S3 disk config)
├── database/
│   └── migrations/ (Phase 1)
├── routes/
│   ├── api.php (all API routes)
│   └── console.php (digest schedule)
├── tests/
│   └── Feature/
│       ├── AuthTest.php
│       ├── ProjectTest.php
│       ├── TaskTest.php
│       ├── AttachmentTest.php
│       ├── CommentTest.php
│       ├── DashboardTest.php
│       └── DailyDigestTest.php
└── backend.md
```
