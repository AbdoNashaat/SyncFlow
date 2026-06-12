# SyncFlow — Projects CRUD API

## Overview

Full RESTful CRUD for projects with role-based authorization. Users see only projects they own or are members of.

---

## Implementation Steps

### 1. Policy & Authorization

File: `app/Policies/ProjectPolicy.php`

| Method | Access Rule |
|--------|-------------|
| `view` | Owner OR any member |
| `create` | Any authenticated user |
| `update` | Owner OR member with `owner`/`editor` role |
| `delete` | Owner only |

Policy registered in `AppServiceProvider`:

```php
Gate::policy(Project::class, ProjectPolicy::class);
```

### 2. Controller

File: `app/Http/Controllers/Api/ProjectController.php`

Laravel route-model binding (`Project $project`) — auto-resolves from route `{project}` param.

| Action | Method | Auth Check |
|--------|--------|------------|
| `index` | Query scoped to user's projects | Sanctum middleware |
| `store` | Create + auto-join as owner member | Sanctum middleware |
| `show` | Policy `view` gate | Sanctum + Policy |
| `update` | Policy `update` gate | Sanctum + Policy |
| `destroy` | Policy `delete` gate | Sanctum + Policy |

### 3. Member Auto-Join

On `store`, the creator is automatically added to `project_members`:

```php
$project->members()->attach(auth()->id(), ['role' => 'owner']);
```

### 4. Scope Query — User Sees Only Relevant Projects

```php
Project::query()
    ->where('owner_id', auth()->id())
    ->orWhereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
    ->withCount(['members', 'tasks'])
    ->with('owner')
    ->latest()
    ->paginate(15);
```

---

## API Endpoints

### `GET /api/projects`

List user's projects (owned + member). Paginated 15/page.

**Response 200:**
```json
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
      "created_at": "2026-06-12T19:03:14.000000Z",
      "updated_at": "2026-06-12T19:03:14.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "total": 10 }
}
```

### `POST /api/projects`

Create project. Auto-joins creator as owner member.

**Request:**
```json
{ "name": "New Project", "description": "Optional desc", "status": "active" }
```

**Response 201:** `{ "data": { ... } }`

### `GET /api/projects/{project}`

Show project detail with members list.

**Response 200:**
```json
{
  "data": {
    "id": 1,
    "name": "Q3 Product Launch",
    "description": "...",
    "status": "active",
    "owner": { "id": 1, "name": "Abdelghafaar" },
    "members": [
      { "id": 2, "name": "John Doe", "timezone": null }
    ],
    "tasks_count": 18,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### `PUT /api/projects/{project}`

Update project. Owner or editor only.

**Request:** `{ "name": "Updated Name", "status": "archived" }`

**Response 200:** `{ "data": { ... } }`

### `DELETE /api/projects/{project}`

Delete project. Owner only.

**Response 200:** `{ "message": "Project deleted" }`

---

## Files Created

```
app/Http/
├── Controllers/Api/ProjectController.php
├── Requests/
│   ├── StoreProjectRequest.php
│   └── UpdateProjectRequest.php
└── Resources/
    ├── ProjectResource.php        (list + summary)
    └── ProjectDetailResource.php  (with members)

app/Policies/ProjectPolicy.php

app/Providers/AppServiceProvider.php  (policy registration)
```

### Form Requests

**StoreProjectRequest:**
```php
rules: [
    'name' => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
    'status' => ['nullable', 'in:active,archived,completed'],
]
```

**UpdateProjectRequest:** Same fields, all optional (`sometimes`).

### API Resources

**ProjectResource:** Returns `id, name, description, status, owner, members_count, tasks_count, created_at, updated_at`.

**ProjectDetailResource:** Returns same + `members` (array of UserResource).

---

## Tests: ProjectTest (11 tests, all passing)

| Test | What it verifies |
|------|------------------|
| `test_user_can_list_their_projects` | Owned + member projects returned; non-member project excluded |
| `test_user_can_create_project` | 201, creator auto-added as owner member |
| `test_user_can_view_project_they_own` | Owner can see project detail |
| `test_user_can_view_project_they_are_member_of` | Member can see project detail |
| `test_user_cannot_view_project_they_are_not_member_of` | 403 forbidden |
| `test_owner_can_update_project` | Owner can update name |
| `test_editor_can_update_project` | Editor role can update |
| `test_viewer_cannot_update_project` | Viewer role gets 403 |
| `test_owner_can_delete_project` | Owner deletes, DB record gone |
| `test_non_owner_cannot_delete_project` | Editor gets 403 |
| `test_unauthenticated_user_cannot_access_projects` | 401 without token |

All pass: `✓ 11 tests, 19 assertions`
