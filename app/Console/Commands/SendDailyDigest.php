<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyDigest extends Command
{
    protected $signature = 'app:send-daily-digest';
    protected $description = 'Send daily digest of overdue tasks to all users';

    public function handle(): int
    {
        $overdueTasks = Task::query()
            ->with(['project:id,name', 'assignedUser'])
            ->where('due_date', '<', Carbon::today())
            ->where('status', '!=', 'done')
            ->orderBy('assigned_user_id')
            ->get()
            ->groupBy('assigned_user_id');

        if ($overdueTasks->isEmpty()) {
            $this->info('No overdue tasks found.');
            Log::info('[SyncFlow Daily Digest] No overdue tasks.');
            return Command::SUCCESS;
        }

        $userIds = $overdueTasks->keys()->filter();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $digestDate = Carbon::today()->format('Y-m-d');
        $totalOverdue = $overdueTasks->flatten()->count();

        $this->info("[SyncFlow Daily Digest] {$digestDate}");
        $this->line("Total overdue tasks: {$totalOverdue}");
        $this->newLine();

        $logLines = ["[SyncFlow Daily Digest] {$digestDate} — {$totalOverdue} overdue tasks"];

        foreach ($overdueTasks as $userId => $tasks) {
            $user = $users->get($userId);
            $userName = $user?->name ?? 'Unassigned';
            $userEmail = $user?->email ?? 'N/A';

            $this->line("User: {$userName} ({$userEmail}) — {$tasks->count()} overdue tasks:");
            $logLines[] = "User: {$userName} ({$userEmail}) — {$tasks->count()} overdue tasks:";

            foreach ($tasks as $task) {
                $dueDate = $task->due_date->format('Y-m-d');
                $this->line("  - {$task->title} (due: {$dueDate}) [Project: {$task->project->name}]");
                $logLines[] = "  - {$task->title} (due: {$dueDate}) [Project: {$task->project->name}]";
            }

            $this->newLine();
        }

        Log::info(implode("\n", $logLines));

        return Command::SUCCESS;
    }
}
