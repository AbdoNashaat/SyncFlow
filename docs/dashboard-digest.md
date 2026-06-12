# SyncFlow — Dashboard & Daily Digest

## Overview

Two features: a real-time dashboard API endpoint and a scheduled CLI command for overdue task digests.

---

## Dashboard Endpoint

### Implementation

File: `app/Http/Controllers/Api/DashboardController.php`

Single endpoint that aggregates stats for the authenticated user across all their projects.

**Scope:** Only counts tasks in projects where the user is owner or member. Does NOT include other users' projects.

### `GET /api/dashboard`

**Response 200:**
```json
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
      {
        "type": "task_updated",
        "task_title": "Fix login bug",
        "task_id": 42,
        "project_name": "Q3 Product Launch",
        "assigned_user": "Jane Doe",
        "time": "2 hours ago"
      }
    ]
  }
}
```

**Aggregation Logic:**

```php
// Find user's projects (owned + member)
$projectIds = Project::query()
    ->where('owner_id', $userId)
    ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId))
    ->pluck('id');

// Scope tasks to those projects, filtered to user's assignments
$userTaskQuery = Task::whereIn('project_id', $projectIds)
    ->where('assigned_user_id', $userId);

// Overdue: due_date < today AND status != done
$overdueTasks = (clone $userTaskQuery)
    ->where('due_date', '<', Carbon::today())
    ->where('status', '!=', 'done')
    ->count();

// Completed this week: status = done AND updated_at >= start of week
$completedThisWeek = (clone $userTaskQuery)
    ->where('status', 'done')
    ->where('updated_at', '>=', Carbon::now()->startOfWeek())
    ->count();

// By status: 4 separate counts
$byStatus = [
    'todo' => (clone $userTaskQuery)->where('status', 'todo')->count(),
    'in_progress' => ...,
    'review' => ...,
    'done' => ...,
];

// Recent activity: last 5 updated tasks across all user's projects
$recentActivity = (clone $taskQuery)
    ->with(['project:id,name', 'assignedUser:id,name'])
    ->latest('updated_at')
    ->take(5)
    ->get()
    ->map(fn ($task) => [
        'type' => 'task_updated',
        'task_title' => $task->title,
        'task_id' => $task->id,
        'project_name' => $task->project->name,
        'assigned_user' => $task->assignedUser?->name,
        'time' => $task->updated_at->diffForHumans(),
    ]);
```

---

## Daily Digest Command

### Implementation

File: `app/Console/Commands/SendDailyDigest.php`

A Laravel command that queries overdue tasks, groups them by assignee, and logs the report.

**Command signature:** `app:send-daily-digest`

### Schedule

File: `routes/console.php`

```php
Schedule::command('app:send-daily-digest')->dailyAt('08:00');
```

This runs at 8:00 AM daily via Laravel's scheduler. The scheduler itself is triggered by a server cron job:

```
* * * * * cd /var/www/syncflow && php artisan schedule:run >> /dev/null 2>&1
```

### Manual Execution

```bash
php artisan app:send-daily-digest
```

### Output Format

When run in the terminal:
```
[SyncFlow Daily Digest] 2026-06-13
Total overdue tasks: 3

User: Jane (jane@example.com) — 2 overdue tasks:
  - Fix login bug (due: 2026-06-10) [Project: Q3 Product Launch]
  - Update API docs (due: 2026-06-11) [Project: Mobile App]

User: John (john@example.com) — 1 overdue tasks:
  - Deploy hotfix (due: 2026-06-09) [Project: Security Audit]
```

### Log Output

Same content written to `storage/logs/laravel.log` via `Log::info()`:

```
[SyncFlow Daily Digest] 2026-06-13 — 3 overdue tasks
User: Jane (jane@example.com) — 2 overdue tasks:
  - Fix login bug (due: 2026-06-10) [Project: Q3 Product Launch]
  - Update API docs (due: 2026-06-11) [Project: Mobile App]
User: John (john@example.com) — 1 overdue tasks:
  - Deploy hotfix (due: 2026-06-09) [Project: Security Audit]
```

### Query Logic

```php
Task::with(['project:id,name', 'assignedUser'])
    ->where('due_date', '<', Carbon::today())
    ->where('status', '!=', 'done')
    ->orderBy('assigned_user_id')
    ->get()
    ->groupBy('assigned_user_id');
```

- Filters: `due_date < today` AND `status != done`
- Eager loads: project (name), assignedUser (name, email)
- Groups by `assigned_user_id` — tasks with `null` assigned user appear under "Unassigned"

### Exit Codes

| Code | Constant | Meaning |
|------|----------|---------|
| 0 | `Command::SUCCESS` | No overdue tasks or digest sent successfully |

---

## Files Created

```
app/
├── Console/Commands/SendDailyDigest.php
└── Http/Controllers/Api/DashboardController.php

routes/console.php  (schedule registration)
```

---

## Tests

### DashboardTest (6 tests, all passing)

| # | Test | Verifies |
|---|------|----------|
| 1 | `test_returns_dashboard_stats` | Correct JSON structure + status counts |
| 2 | `test_counts_overdue_tasks` | Only overdue (not done) tasks counted |
| 3 | `test_counts_completed_this_week` | Only tasks completed this week |
| 4 | `test_includes_recent_activity` | Recent activity array with correct count |
| 5 | `test_only_counts_users_tasks_in_by_status` | Dashboard scoped to user's projects |
| 6 | `test_unauthenticated_user_cannot_access_dashboard` | 401 without token |

### DailyDigestTest (6 tests, all passing)

| # | Test | Verifies |
|---|------|----------|
| 1 | `test_command_reports_no_overdue_tasks` | "No overdue tasks found" output |
| 2 | `test_command_reports_overdue_tasks_grouped_by_user` | User name + task title in output |
| 3 | `test_command_ignores_completed_tasks` | Done tasks excluded even if past due |
| 4 | `test_command_logs_digest_to_laravel_log` | Log::info called with digest content |
| 5 | `test_command_groups_tasks_by_user` | Both users appear with their tasks |
| 6 | `test_command_returns_success_code` | Exit code 0 |

All pass: `✓ 12 tests, 35 assertions`
