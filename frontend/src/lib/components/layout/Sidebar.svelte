<script>
  import { currentUser } from '../../stores/auth.js';
  import { authApi } from '../../api/auth.js';
  import { clearAuth } from '../../stores/auth.js';
  import { addToast } from '../../stores/ui.js';

  let mobileOpen = false;

  async function handleLogout() {
    try {
      await authApi.logout();
    } catch (e) {
      // ignore
    }
    clearAuth();
    addToast('Logged out', 'info');
  }

  function isActive(path) {
    return window.location.hash === `#${path}`;
  }
</script>

<!-- Mobile toggle -->
<button
  class="fixed top-4 left-4 z-30 lg:hidden p-2 rounded-lg bg-white shadow-md"
  on:click={() => mobileOpen = !mobileOpen}
  aria-label="Toggle menu"
>
  <svg class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    {#if mobileOpen}
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    {:else}
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    {/if}
  </svg>
</button>

<!-- Sidebar -->
<aside
  class="fixed inset-y-0 left-0 z-20 w-64 bg-gray-900 text-white transform transition-transform duration-200 lg:translate-x-0 lg:static lg:inset-auto flex flex-col {mobileOpen ? 'translate-x-0' : '-translate-x-full'}"
>
  <!-- Logo -->
  <div class="px-6 py-5 border-b border-gray-800">
    <a href="#/" class="text-xl font-bold tracking-tight">SyncFlow</a>
  </div>

  <!-- User info -->
  {#if $currentUser}
    <div class="px-6 py-3 border-b border-gray-800 text-sm text-gray-400">
      <div class="font-medium text-white">{$currentUser.name}</div>
      <div>{$currentUser.email}</div>
    </div>
  {/if}

  <!-- Nav links -->
  <nav class="flex-1 px-3 py-4 space-y-1">
    <a
      href="#/"
      class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {isActive('/') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'}"
      on:click={() => mobileOpen = false}
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      Dashboard
    </a>
    <a
      href="#/projects"
      class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {isActive('/projects') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'}"
      on:click={() => mobileOpen = false}
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
      </svg>
      Projects
    </a>
  </nav>

  <!-- Logout -->
  <div class="px-3 py-4 border-t border-gray-800">
    <button
      class="flex items-center gap-3 w-full px-3 py-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors text-sm"
      on:click={handleLogout}
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      Logout
    </button>
  </div>
</aside>

<!-- Backdrop for mobile -->
{#if mobileOpen}
  <!-- svelte-ignore a11y_click_events_have_key_events a11y_no_static_element_interactions -->
  <div class="fixed inset-0 bg-black/50 z-10 lg:hidden" on:click={() => mobileOpen = false}></div>
{/if}
