<script>
  import { onMount } from 'svelte';
  import { dashboardApi } from '../lib/api/dashboard.js';
  import { push } from 'svelte-spa-router';
  import LoadingSpinner from '../lib/components/common/LoadingSpinner.svelte';
  import ErrorMessage from '../lib/components/common/ErrorMessage.svelte';

  let stats = null;
  let loading = true;
  let error = null;

  onMount(() => fetchStats());

  async function fetchStats() {
    loading = true;
    error = null;
    try {
      const res = await dashboardApi.stats();
      stats = res.data.data;
    } catch (e) {
      error = 'Failed to load dashboard';
    } finally {
      loading = false;
    }
  }

  const statusColors = {
    todo: 'bg-gray-400',
    in_progress: 'bg-blue-500',
    review: 'bg-yellow-500',
    done: 'bg-green-500',
  };
</script>

<div class="p-6 max-w-6xl mx-auto">
  <div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <button
      class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
      on:click={() => push('/projects')}
    >
      View Projects
    </button>
  </div>

  {#if loading}
    <LoadingSpinner size="lg" />
  {:else if error}
    <ErrorMessage {error} onRetry={fetchStats} />
  {:else if stats}
    <!-- Stats cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="bg-white rounded-xl border p-5">
        <div class="text-sm text-gray-500 mb-1">Total Projects</div>
        <div class="text-3xl font-bold text-gray-900">{stats.total_projects}</div>
      </div>
      <div class="bg-white rounded-xl border p-5">
        <div class="text-sm text-gray-500 mb-1">Overdue Tasks</div>
        <div class="text-3xl font-bold text-red-600">{stats.overdue_tasks}</div>
      </div>
      <div class="bg-white rounded-xl border p-5">
        <div class="text-sm text-gray-500 mb-1">Completed This Week</div>
        <div class="text-3xl font-bold text-green-600">{stats.completed_this_week}</div>
      </div>
      <div class="bg-white rounded-xl border p-5">
        <div class="text-sm text-gray-500 mb-1">My Tasks</div>
        <div class="text-3xl font-bold text-gray-900">
          {stats.by_status.todo + stats.by_status.in_progress + stats.by_status.review + stats.by_status.done}
        </div>
      </div>
    </div>

    <!-- Status breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <div class="bg-white rounded-xl border p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Tasks by Status</h2>
        {#if stats}
          {@const total = stats.by_status.todo + stats.by_status.in_progress + stats.by_status.review + stats.by_status.done}
          <div class="space-y-3">
            {#each ['todo', 'in_progress', 'review', 'done'] as status}
              {@const pct = total > 0 ? Math.round((stats.by_status[status] / total) * 100) : 0}
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full {statusColors[status]}"></div>
                <span class="text-sm text-gray-600 capitalize flex-1">{status.replace('_', ' ')}</span>
                <span class="text-sm font-semibold text-gray-900">{stats.by_status[status]}</span>
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full {statusColors[status]}" style="width: {pct}%"></div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>

      <!-- Recent activity -->
      <div class="bg-white rounded-xl border p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
        {#if stats.recent_activity.length === 0}
          <p class="text-sm text-gray-400">No recent activity</p>
        {:else}
          <div class="space-y-3">
            {#each stats.recent_activity as activity}
              <div class="flex items-start gap-3 text-sm">
                <div class="w-2 h-2 mt-1.5 rounded-full bg-indigo-400 shrink-0"></div>
                <div class="flex-1 min-w-0">
                  <a
                    href="#/projects/{activity.task_id}"
                    class="font-medium text-gray-900 hover:text-indigo-600 truncate block"
                  >
                    {activity.task_title}
                  </a>
                  <p class="text-gray-500 truncate">{activity.project_name}</p>
                  <p class="text-gray-400 text-xs mt-0.5">{activity.time}</p>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>
