# SyncFlow — Tasks CRUD API

## Overview

Full RESTful CRUD for tasks within a project scope. Supports filtering, drag-drop position updates, and role-based authorization.

---

## Implementation Steps

### 1. Policy & Authorization

File: `app/Policies/TaskPolicy.php`

| Method | Access Rule |
|--------|-------------|
| `viewAny` | Any project member (owner + all roles) |
| `view` | Any project member |
| `create` | Owner or member with `editor`/`owner` role |
| `update` | Owner or member with `editor`/`owner` role |
| `delete` | Owner, editor, OR task creator (even if viewer) |

Policy registered in `AppServiceProvider`:

```php
Gate::policy(Task::class, TaskPolicy::class);
```

### 2. Scope & Routes

All task routes nested under projects:

```
/projects/{project}/tasks           GET, POST
/projects/{project}/tasks/{task}    GET, PUT, DELETE
/projects/{project}/tasks/{task}/position  PATCH
```

Laravel implicit route-model binding resolves both `Project` and `Task` from the route.

### 3. TaskController

File: `app/Http/Controllers/Api/TaskController.php`

| Action | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `index` | GET `/api/projects/{project}/tasks` | Sanctum + viewAny | List with filters |
| `store` | POST `/api/projects/{project}/tasks` | Sanctum + create | Auto-assigns position |
| `show` | GET `/api/projects/{project}/tasks/{task}` | Sanctum + view | Full detail with attachments + comments |
| `update` | PUT `/api/projects/{project}/tasks/{task}` | Sanctum + update | Partial update |
| `destroy` | DELETE `/api/projects/{project}/tasks/{task}` | Sanctum + delete | Soft delete |
| `updatePosition` | PATCH `/api/projects/{project}/tasks/{task}/position` | Sanctum + update | Drag-drop position + status |

### 4. Position Auto-Increment

On create, position is computed as `max(position) + 1` for the target status column:

```php
$maxPosition = $project->tasks()
    ->where('status', $request->status ?? 'todo')
    ->max('position') ?? 0;

$task = $project->tasks()->create([
    ...$request->validated(),
    'created_by' => auth()->id(),
    'position' => $maxPosition + 1,
]);
```

### 5. Filtering

The `index` endpoint supports multiple query parameters:

| Param | Example | Behavior |
|-------|---------|----------|
| `status` | `todo,in_progress` | Comma-separated, `WHERE IN` |
| `priority` | `high,urgent` | Comma-separated, `WHERE IN` |
| `assigned_user` | `3` | Exact match on `assigned_user_id` |
| `search` | `deploy` | `LIKE %term%` on title + description |

---

## API Endpoints

### `GET /api/projects/{project}/tasks`

List tasks with optional filters.

**Query Params:** `?status=todo,done&priority=high&assigned_user=3&search=deploy`

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "title": "Set up CI/CD pipeline",
      "description": null,
      "status": "in_progress",
      "priority": "high",
      "due_date": "2026-06-20",
      "position": 2,
      "assigned_user": { "id": 3, "name": "Jane" },
      "creator": { "id": 1, "name": "Abdelghafaar" },
      "attachments_count": 1,
      "comments_count": 3,
      "created_at": "2026-06-12T19:03:14.000000Z",
      "updated_at": "2026-06-12T19:03:14.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "total": 50 }
}
```

Paginated 50 per page (large for board views). Ordered by `position`, then `created_at desc`.

### `POST /api/projects/{project}/tasks`

Create task within project.

**Request:**
```json
{
  "title": "Fix login bug",
  "description": "Token expired edge case",
  "status": "todo",
  "priority": "high",
  "due_date": "2026-06-20",
  "assigned_user_id": 3
}
```

**Response 201:**
```json
{
  "data": {
    "id": 203,
    "title": "Fix login bug",
    "status": "todo",
    "position": 12,
    ...
  }
}
```

Position is auto-set to `max(position) + 1` in the target status column.

### `GET /api/projects/{project}/tasks/{task}`

Full task detail with nested attachments and comments.

**Response 200:**
```json
{
  "data": {
    "id": 1,
    "project_id": 1,
    "title": "...",
    "description": "...",
    "status": "in_progress",
    "priority": "high",
    "due_date": "2026-06-20",
    "position": 2,
    "assigned_user": { "id": 3, "name": "Jane" },
    "creator": { "id": 1, "name": "Abdelghafaar" },
    "attachments": [
      {
        "id": 1,
        "task_id": 1,
        "original_name": "report.pdf",
        "mime_type": "application/pdf",
        "size": 204800,
        "user": { "id": 1, "name": "Abdelghafaar" },
        "created_at": "..."
      }
    ],
    "comments": [
      {
        "id": 1,
        "task_id": 1,
        "content": "Looking good!",
        "user": { "id": 2, "name": "Jane" },
        "created_at": "..."
      }
    ]
  }
}
```

### `PUT /api/projects/{project}/tasks/{task}`

Update task fields. Partial update supported.

**Request:**
```json
{ "status": "done", "priority": "low" }
```

**Response 200:** `{ "data": { ... } }`

### `DELETE /api/projects/{project}/tasks/{task}`

Delete task.

**Response 200:** `{ "message": "Task deleted" }`

### `PATCH /api/projects/{project}/tasks/{task}/position`

Update position and/or status (drag-drop support).

**Request:**
```json
{ "position": 3, "status": "in_progress" }
```

**Response 200:** `{ "data": { ... "position": 3, "status": "in_progress" } }`

---

## Form Requests

| Request | Key Rules |
|---------|-----------|
| `StoreTaskRequest` | `title` required, `status` in: `todo,in_progress,review,done`, `priority` in: `low,medium,high,urgent`, `due_date` after_or_equal:today, `assigned_user_id` exists:users |
| `UpdateTaskRequest` | Same rules, all fields `sometimes` |
| `UpdateTaskPositionRequest` | `position` required integer min:0, `status` nullable in values |

---

## API Resources

| Resource | Used By | Fields |
|----------|---------|--------|
| `TaskResource` | index, store, update, updatePosition | Basic fields + assignedUser + creator + counts |
| `TaskDetailResource` | show | All above + attachments (AttachmentResource[]) + comments (CommentResource[]) |
| `AttachmentResource` | TaskDetailResource, AttachmentController | id, task_id, original_name, mime_type, size, user, created_at |
| `CommentResource` | TaskDetailResource, CommentController | id, task_id, content, user, created_at, updated_at |

---

## Files Created

```
app/Http/
├── Controllers/Api/TaskController.php
├── Requests/
│   ├── StoreTaskRequest.php
│   ├── UpdateTaskRequest.php
│   └── UpdateTaskPositionRequest.php
└── Resources/
    ├── TaskResource.php
    ├── TaskDetailResource.php
    ├── AttachmentResource.php
    └── CommentResource.php

app/Policies/TaskPolicy.php
```

---

## Tests: TaskTest (20 tests, all passing)

| # | Test | Verifies |
|---|------|----------|
| 1 | `test_can_list_tasks` | GET returns project tasks |
| 2 | `test_can_filter_tasks_by_status` | `?status=todo,done` filters correctly |
| 3 | `test_can_filter_tasks_by_priority` | `?priority=high,medium` filters correctly |
| 4 | `test_can_search_tasks` | `?search=Deploy` finds by title |
| 5 | `test_can_filter_by_assigned_user` | `?assigned_user=3` filters correctly |
| 6 | `test_owner_can_create_task` | 201, correct created_by, DB record |
| 7 | `test_editor_can_create_task` | Editor role gets 201 |
| 8 | `test_viewer_cannot_create_task` | Viewer role gets 403 |
| 9 | `test_non_member_cannot_list_tasks` | Non-member gets 403 |
| 10 | `test_can_view_task_detail` | Full detail with nested resources |
| 11 | `test_owner_can_update_task` | Owner PUT updates fields |
| 12 | `test_editor_can_update_task` | Editor PUT succeeds |
| 13 | `test_viewer_cannot_update_task` | Viewer PUT gets 403 |
| 14 | `test_owner_can_delete_task` | Owner DELETE removes record |
| 15 | `test_editor_can_delete_task` | Editor DELETE succeeds |
| 16 | `test_viewer_cannot_delete_task` | Viewer DELETE gets 403 |
| 17 | `test_creator_can_delete_task_even_as_viewer` | Creator (viewer role) can still delete own task |
| 18 | `test_can_update_task_position` | PATCH position + status updates correctly |
| 19 | `test_task_position_auto_increments` | Two tasks get positions 1, 2 |
| 20 | `test_unauthenticated_user_cannot_access_tasks` | 401 without token |

All pass: `✓ 20 tests, 35 assertions`
