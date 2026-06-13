<script>
  import { onMount } from 'svelte';
  import { projectsApi } from '../lib/api/projects.js';
  import { push } from 'svelte-spa-router';
  import LoadingSpinner from '../lib/components/common/LoadingSpinner.svelte';
  import ErrorMessage from '../lib/components/common/ErrorMessage.svelte';
  import EmptyState from '../lib/components/common/EmptyState.svelte';
  import Modal from '../lib/components/common/Modal.svelte';
  import { addToast } from '../lib/stores/ui.js';

  let projects = [];
  let loading = true;
  let error = null;
  let showCreateModal = false;
  let showDeleteModal = false;
  let deletingProject = null;
  let creating = false;

  // Create form
  let createName = '';
  let createDescription = '';
  let createError = '';

  onMount(() => fetchProjects());

  async function fetchProjects() {
    loading = true;
    error = null;
    try {
      const res = await projectsApi.list();
      projects = res.data.data;
    } catch (e) {
      error = 'Failed to load projects';
    } finally {
      loading = false;
    }
  }

  async function handleCreate() {
    if (!createName.trim()) return;
    createError = '';
    creating = true;
    try {
      await projectsApi.create({ name: createName, description: createDescription });
      addToast('Project created', 'success');
      showCreateModal = false;
      createName = '';
      createDescription = '';
      await fetchProjects();
    } catch (e) {
      createError = e.response?.data?.message || 'Failed to create project';
    } finally {
      creating = false;
    }
  }

  function confirmDelete(project) {
    deletingProject = project;
    showDeleteModal = true;
  }

  async function handleDelete() {
    if (!deletingProject) return;
    try {
      await projectsApi.delete(deletingProject.id);
      addToast('Project deleted', 'success');
      projects = projects.filter(p => p.id !== deletingProject.id);
    } catch (e) {
      addToast('Failed to delete project', 'error');
    } finally {
      showDeleteModal = false;
      deletingProject = null;
    }
  }

  function roleBadge(role) {
    const colors = { owner: 'bg-purple-100 text-purple-800', editor: 'bg-blue-100 text-blue-800', viewer: 'bg-gray-100 text-gray-800' };
    return colors[role] || 'bg-gray-100 text-gray-800';
  }
</script>

<div class="p-6 max-w-6xl mx-auto">
  <div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
    <button
      class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
      on:click={() => showCreateModal = true}
    >
      + New Project
    </button>
  </div>

  {#if loading}
    <LoadingSpinner size="lg" />
  {:else if error}
    <ErrorMessage {error} onRetry={fetchProjects} />
  {:else if projects.length === 0}
    <EmptyState
      title="No projects yet"
      description="Create your first project to get started"
      actionLabel="Create Project"
      action={() => showCreateModal = true}
    />
  {:else}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {#each projects as project}
        <div class="bg-white rounded-xl border hover:shadow-md transition-shadow flex flex-col">
          <button
            class="p-5 text-left flex-1"
            on:click={() => push(`/projects/${project.id}`)}
          >
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{project.name}</h3>
            <p class="text-sm text-gray-500 line-clamp-2">{project.description || 'No description'}</p>
            <div class="flex items-center gap-3 mt-3 text-xs text-gray-400">
              <span>{project.task_count ?? 0} tasks</span>
              <span class="capitalize px-2 py-0.5 rounded-full text-xs font-medium {roleBadge(project.role || 'viewer')}">{project.role || 'viewer'}</span>
            </div>
          </button>
          <div class="px-5 pb-3 flex gap-2">
            <button
              class="text-xs text-indigo-600 hover:text-indigo-800 transition-colors"
              on:click={() => push(`/projects/${project.id}`)}
            >
              Open
            </button>
            <button
              class="text-xs text-red-600 hover:text-red-800 transition-colors"
              on:click={() => confirmDelete(project)}
            >
              Delete
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<!-- Create modal -->
{#if showCreateModal}
  <Modal title="New Project" show={showCreateModal} on:close={() => showCreateModal = false}>
    <form on:submit|preventDefault={handleCreate} class="space-y-4">
      <div>
        <label for="pname" class="block text-sm font-medium text-gray-700">Name</label>
        <input
          id="pname"
          type="text"
          bind:value={createName}
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
          placeholder="Project name"
        />
      </div>
      <div>
        <label for="pdesc" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea
          id="pdesc"
          bind:value={createDescription}
          rows="3"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
          placeholder="Optional description"
        ></textarea>
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
          {creating ? 'Creating...' : 'Create'}
        </button>
      </div>
    </form>
  </Modal>
{/if}

<!-- Delete confirm modal -->
{#if showDeleteModal}
  <Modal title="Delete Project" show={showDeleteModal} on:close={() => { showDeleteModal = false; deletingProject = null; }}>
    <p class="text-sm text-gray-600 mb-4">
      Are you sure you want to delete <strong>{deletingProject?.name}</strong>? This action cannot be undone.
    </p>
    <div class="flex justify-end gap-3">
      <button
        class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 transition-colors"
        on:click={() => { showDeleteModal = false; deletingProject = null; }}
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
