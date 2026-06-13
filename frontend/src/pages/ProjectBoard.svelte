<script>
  import { onMount } from 'svelte';
  import { projectsApi } from '../lib/api/projects.js';
  import { tasksApi } from '../lib/api/tasks.js';
  import { push } from 'svelte-spa-router';
  import LoadingSpinner from '../lib/components/common/LoadingSpinner.svelte';
  import ErrorMessage from '../lib/components/common/ErrorMessage.svelte';
  import Modal from '../lib/components/common/Modal.svelte';
  import { addToast } from '../lib/stores/ui.js';

  export let params = {};

  let project = null;
  let tasks = [];
  let loading = true;
  let error = null;

  // Create task modal
  let showCreateModal = false;
  let createTitle = '';
  let createDescription = '';
  let createDueDate = '';
  let createStatus = 'todo';
  let creating = false;
  let createError = '';

  // Edit task modal
  let showEditModal = false;
  let editingTask = null;

  // Delete confirm
  let showDeleteModal = false;
  let deletingTask = null;

  const statusColumns = [
    { key: 'todo', label: 'To Do', color: 'border-t-gray-400' },
    { key: 'in_progress', label: 'In Progress', color: 'border-t-blue-500' },
    { key: 'review', label: 'Review', color: 'border-t-yellow-500' },
    { key: 'done', label: 'Done', color: 'border-t-green-500' },
  ];

  onMount(() => fetchData());

  async function fetchData() {
    loading = true;
    error = null;
    try {
      const [projRes, taskRes] = await Promise.all([
        projectsApi.show(params.id),
        tasksApi.list(params.id),
      ]);
      project = projRes.data.data;
      tasks = taskRes.data.data;
    } catch (e) {
      if (e.response?.status === 403) {
        error = 'You do not have permission to view this project';
      } else {
        error = 'Failed to load project';
      }
    } finally {
      loading = false;
    }
  }

  function tasksByStatus(status) {
    return tasks.filter(t => t.status === status);
  }

  async function handleCreate() {
    if (!createTitle.trim()) return;
    createError = '';
    creating = true;
    try {
      const res = await tasksApi.create(params.id, {
        title: createTitle,
        description: createDescription,
        due_date: createDueDate || null,
        status: createStatus,
      });
      tasks = [...tasks, res.data.data];
      addToast('Task created', 'success');
      showCreateModal = false;
      createTitle = '';
      createDescription = '';
      createDueDate = '';
      createStatus = 'todo';
    } catch (e) {
      createError = e.response?.data?.message || 'Failed to create task';
    } finally {
      creating = false;
    }
  }

  function openEdit(task) {
    editingTask = { ...task };
    showEditModal = true;
  }

  async function handleEdit() {
    if (!editingTask) return;
    try {
      const res = await tasksApi.update(params.id, editingTask.id, {
        title: editingTask.title,
        description: editingTask.description,
        due_date: editingTask.due_date,
        status: editingTask.status,
      });
      tasks = tasks.map(t => t.id === editingTask.id ? res.data.data : t);
      addToast('Task updated', 'success');
      showEditModal = false;
      editingTask = null;
    } catch (e) {
      addToast('Failed to update task', 'error');
    }
  }

  function confirmDelete(task) {
    deletingTask = task;
    showDeleteModal = true;
  }

  async function handleDelete() {
    if (!deletingTask) return;
    try {
      await tasksApi.delete(params.id, deletingTask.id);
      tasks = tasks.filter(t => t.id !== deletingTask.id);
      addToast('Task deleted', 'success');
    } catch (e) {
      addToast('Failed to delete task', 'error');
    } finally {
      showDeleteModal = false;
      deletingTask = null;
    }
  }

  async function moveTask(task, newStatus) {
    if (task.status === newStatus) return;
    try {
      const res = await tasksApi.update(params.id, task.id, { status: newStatus });
      tasks = tasks.map(t => t.id === task.id ? res.data.data : t);
    } catch (e) {
      addToast('Failed to move task', 'error');
    }
  }

  function dueDateClass(task) {
    if (!task.due_date) return '';
    const due = new Date(task.due_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (due < today && task.status !== 'done') return 'text-red-600 font-medium';
    return 'text-gray-500';
  }

  function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(s => s[0]).join('').toUpperCase().slice(0, 2);
  }
</script>

<div class="p-6 max-w-7xl mx-auto">
  <!-- Header -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <button
        class="text-sm text-indigo-600 hover:text-indigo-800 mb-1 transition-colors"
        on:click={() => push('/projects')}
      >
        &larr; Back to Projects
      </button>
      <h1 class="text-2xl font-bold text-gray-900">{project?.name || 'Project'}</h1>
      {#if project?.description}
        <p class="text-sm text-gray-500 mt-1">{project.description}</p>
      {/if}
    </div>
    <button
      class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium shrink-0"
      on:click={() => showCreateModal = true}
    >
      + Add Task
    </button>
  </div>

  {#if loading}
    <LoadingSpinner size="lg" />
  {:else if error}
    <ErrorMessage {error} onRetry={fetchData} />
  {:else}
    <!-- Kanban columns -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      {#each statusColumns as col}
        <div class="bg-gray-50 rounded-xl border border-t-2 {col.color} flex flex-col">
          <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">{col.label} ({tasksByStatus(col.key).length})</h3>
          </div>
          <div class="p-2 flex-1 space-y-2 min-h-[200px]">
            {#each tasksByStatus(col.key) as task}
              <div class="bg-white rounded-lg border p-3 shadow-sm hover:shadow-md transition-shadow">
                <button class="w-full text-left" on:click={() => openEdit(task)}>
                  <h4 class="text-sm font-medium text-gray-900 mb-1">{task.title}</h4>
                  {#if task.description}
                    <p class="text-xs text-gray-500 line-clamp-2 mb-2">{task.description}</p>
                  {/if}
                  <div class="flex items-center justify-between text-xs">
                    <span class="{dueDateClass(task)}">
                      {#if task.due_date}
                        {formatDate(task.due_date)}
                      {/if}
                    </span>
                    {#if task.assignee}
                      <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-medium" title={task.assignee.name}>
                        {initials(task.assignee.name)}
                      </span>
                    {/if}
                  </div>
                </button>
                <!-- Quick status move -->
                <div class="flex gap-1 mt-2 pt-2 border-t border-gray-100">
                  {#each ['todo', 'in_progress', 'review', 'done'] as s}
                    <button
                      class="w-2 h-2 rounded-full {s === task.status ? 'ring-2 ring-offset-1' : ''}"
                      class:bg-gray-400={s === 'todo'}
                      class:bg-blue-500={s === 'in_progress'}
                      class:bg-yellow-500={s === 'review'}
                      class:bg-green-500={s === 'done'}
                      on:click={() => moveTask(task, s)}
                      title="Move to {s.replace('_', ' ')}"
                    ></button>
                  {/each}
                </div>
              </div>
            {:else}
              <div class="flex items-center justify-center h-20 text-xs text-gray-400">
                No tasks
              </div>
            {/each}
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<!-- Create task modal -->
{#if showCreateModal}
  <Modal title="Add Task" show={showCreateModal} on:close={() => showCreateModal = false}>
    <form on:submit|preventDefault={handleCreate} class="space-y-4">
      <div>
        <label for="ttitle" class="block text-sm font-medium text-gray-700">Title</label>
        <input
          id="ttitle"
          type="text"
          bind:value={createTitle}
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
          placeholder="Task title"
        />
      </div>
      <div>
        <label for="tdesc" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea
          id="tdesc"
          bind:value={createDescription}
          rows="3"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
          placeholder="Optional description"
        ></textarea>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="tstatus" class="block text-sm font-medium text-gray-700">Status</label>
          <select
            id="tstatus"
            bind:value={createStatus}
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-sm"
          >
            <option value="todo">To Do</option>
            <option value="in_progress">In Progress</option>
            <option value="review">Review</option>
            <option value="done">Done</option>
          </select>
        </div>
        <div>
          <label for="tdate" class="block text-sm font-medium text-gray-700">Due Date</label>
          <input
            id="tdate"
            type="date"
            bind:value={createDueDate}
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-sm"
          />
        </div>
      </div>
      {#if createError}
        <p class="text-sm text-red-600">{createError}</p>
      {/if}
      <div class="flex justify-end gap-3">
        <button
          type="button"
          class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 transition-colors"
          on:click={() => showCreateModal = false}
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={creating}
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors text-sm"
        >
          {creating ? 'Adding...' : 'Add Task'}
        </button>
      </div>
    </form>
  </Modal>
{/if}

<!-- Edit task modal -->
{#if showEditModal && editingTask}
  <Modal title="Edit Task" show={showEditModal} on:close={() => { showEditModal = false; editingTask = null; }}>
    <div class="space-y-4">
      <div>
        <label for="ettitle" class="block text-sm font-medium text-gray-700">Title</label>
        <input
          id="ettitle"
          type="text"
          bind:value={editingTask.title}
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
        />
      </div>
      <div>
        <label for="etdesc" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea
          id="etdesc"
          bind:value={editingTask.description}
          rows="3"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
        ></textarea>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="etstatus" class="block text-sm font-medium text-gray-700">Status</label>
          <select
            id="etstatus"
            bind:value={editingTask.status}
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-sm"
          >
            <option value="todo">To Do</option>
            <option value="in_progress">In Progress</option>
            <option value="review">Review</option>
            <option value="done">Done</option>
          </select>
        </div>
        <div>
          <label for="etdate" class="block text-sm font-medium text-gray-700">Due Date</label>
          <input
            id="etdate"
            type="date"
            bind:value={editingTask.due_date}
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-sm"
          />
        </div>
      </div>
      <div class="flex justify-between">
        <button
          class="text-sm text-red-600 hover:text-red-800 transition-colors"
          on:click={() => confirmDelete(editingTask)}
        >
          Delete task
        </button>
        <div class="flex gap-3">
          <button
            class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 transition-colors"
            on:click={() => { showEditModal = false; editingTask = null; }}
          >
            Cancel
          </button>
          <button
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm"
            on:click={handleEdit}
          >
            Save
          </button>
        </div>
      </div>
    </div>
  </Modal>
{/if}

<!-- Delete confirm modal -->
{#if showDeleteModal}
  <Modal title="Delete Task" show={showDeleteModal} on:close={() => { showDeleteModal = false; deletingTask = null; }}>
    <p class="text-sm text-gray-600 mb-4">
      Delete <strong>{deletingTask?.title}</strong>? This cannot be undone.
    </p>
    <div class="flex justify-end gap-3">
      <button
        class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 transition-colors"
        on:click={() => { showDeleteModal = false; deletingTask = null; }}
      >
        Cancel
      </button>
      <button
        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm"
        on:click={handleDelete}
      >
        Delete
      </button>
    </div>
  </Modal>
{/if}
