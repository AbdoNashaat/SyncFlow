# SyncFlow — Attachments & Comments API

## Overview

File upload (S3-backed) and commenting system for tasks. Both scoped under project → task hierarchy.

---

## Attachments

### Implementation

File: `app/Http/Controllers/Api/AttachmentController.php`

| Action | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `store` | POST `/api/projects/{project}/tasks/{task}/attachments` | Sanctum + any member | Upload up to 5 files |
| `destroy` | DELETE `/api/tasks/{task}/attachments/{attachment}` | Sanctum + any member | Delete from S3 + DB |

**S3 Storage:**
- Disk config: `config/filesystems.php` — uses `default` disk (S3 in prod, local in dev)
- Path pattern: `attachments/{task_id}/{uuid}.{ext}`
- Deletion: checks S3 key exists before deleting, then removes DB record

```php
$disk = Storage::disk(config('filesystems.default'));
$s3Key = "attachments/{$task->id}/{$uuid}.{$extension}";
$disk->put($s3Key, file_get_contents($file->getRealPath()));
```

**Authorization:** Any project member can upload/delete. Task must belong to the project (404 check).

### Endpoints

#### `POST /api/projects/{project}/tasks/{task}/attachments`

Upload files. Multipart form-data.

**Request (multipart/form-data):**
| Field | Type | Description |
|-------|------|-------------|
| `attachments[]` | File[] | Array of files (max 5) |

**Response 201:**
```json
{
  "data": [
    {
      "id": 1,
      "task_id": 1,
      "original_name": "screenshot.png",
      "mime_type": "image/png",
      "size": 102400,
      "user": { "id": 1, "name": "Abdelghafaar" },
      "created_at": "2026-06-12T19:03:14.000000Z"
    }
  ]
}
```

**Validation:**
- Max 5 files per request
- Max 10MB per file
- Allowed mimes: `jpg, jpeg, png, gif, pdf, docx, xlsx, zip`

**Error 422:**
```json
{
  "message": "Validation failed",
  "errors": {
    "attachments.0": ["Each file must be under 10MB."]
  }
}
```

#### `DELETE /api/tasks/{task}/attachments/{attachment}`

Delete attachment from S3 and database.

**Response 200:** `{ "message": "Attachment deleted" }`

### Form Request

File: `app/Http/Requests/StoreAttachmentRequest.php`

```php
rules: [
    'attachments' => ['required', 'array', 'max:5'],
    'attachments.*' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,docx,xlsx,zip'],
]
```

---

## Comments

### Implementation

File: `app/Http/Controllers/Api/CommentController.php`

| Action | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `index` | GET `/api/projects/{project}/tasks/{task}/comments` | Sanctum + any member | Paginated, oldest first |
| `store` | POST `/api/projects/{project}/tasks/{task}/comments` | Sanctum + any member | Add comment |
| `destroy` | DELETE `/api/comments/{comment}` | Sanctum + author or owner | Delete comment |

**Authorization Rules:**
- View/list: any project member
- Create: any project member (viewer can comment)
- Delete: comment author OR project owner (not just any editor)

**Comment ordering:** Oldest first (`->oldest()`) for natural conversation thread.

### Endpoints

#### `GET /api/projects/{project}/tasks/{task}/comments`

List comments. Paginated 20/page, oldest first.

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "task_id": 1,
      "content": "Looking good!",
      "user": { "id": 2, "name": "Jane" },
      "created_at": "2026-06-12T19:03:14.000000Z",
      "updated_at": "2026-06-12T19:03:14.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "total": 3 }
}
```

#### `POST /api/projects/{project}/tasks/{task}/comments`

Add comment.

**Request:** `{ "content": "This is a comment" }`

**Response 201:**
```json
{
  "data": {
    "id": 406,
    "task_id": 1,
    "content": "This is a comment",
    "user": { "id": 1, "name": "Abdelghafaar" },
    "created_at": "...",
    "updated_at": "..."
  }
}
```

#### `DELETE /api/comments/{comment}`

Delete comment. Author or project owner only.

**Response 200:** `{ "message": "Comment deleted" }`

**Error 403:** `{ "message": "Forbidden" }` — non-author, non-owner user.

### Form Request

File: `app/Http/Requests/StoreCommentRequest.php`

```php
rules: [
    'content' => ['required', 'string', 'max:10000'],
]
```

---

## API Resources

### AttachmentResource

```php
'id', 'task_id', 'original_name', 'mime_type', 'size',
'user' (UserResource, whenLoaded),
'created_at'
```

### CommentResource

```php
'id', 'task_id', 'content',
'user' (UserResource, whenLoaded),
'created_at', 'updated_at'
```

---

## Files Created

```
app/Http/
├── Controllers/Api/
│   ├── AttachmentController.php
│   └── CommentController.php
└── Requests/
    ├── StoreAttachmentRequest.php
    └── StoreCommentRequest.php
```

---

## Tests

### AttachmentTest (8 tests, all passing)

| # | Test | Verifies |
|---|------|----------|
| 1 | `test_can_upload_single_file` | 201, file on disk |
| 2 | `test_can_upload_multiple_files` | 201 + 2 items in response |
| 3 | `test_upload_rejects_invalid_file_type` | 422 for .exe |
| 4 | `test_upload_rejects_file_too_large` | 422 for >10MB |
| 5 | `test_upload_rejects_more_than_5_files` | 422 for 6 files |
| 6 | `test_non_member_cannot_upload` | 403 |
| 7 | `test_can_delete_attachment` | 200, file + DB record removed |

### CommentTest (7 tests, all passing)

| # | Test | Verifies |
|---|------|----------|
| 1 | `test_can_list_comments` | GET returns comments |
| 2 | `test_member_can_create_comment` | 201, correct content |
| 3 | `test_comment_has_user_data` | Response includes user id + name |
| 4 | `test_non_member_cannot_create_comment` | 403 |
| 5 | `test_comment_author_can_delete_own_comment` | Author can delete |
| 6 | `test_project_owner_can_delete_any_comment` | Owner can delete any |
| 7 | `test_other_user_cannot_delete_someone_elses_comment` | 403 for other member |
| 8 | `test_comments_are_ordered_oldest_first` | Oldest comment first |

All pass: `✓ 15 tests, 26 assertions`
