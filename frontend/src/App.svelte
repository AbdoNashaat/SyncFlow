<script>
  import Router, { push, location } from 'svelte-spa-router';
  import { onMount } from 'svelte';
  import { isAuthenticated, currentUser, setAuth } from './lib/stores/auth.js';
  import { authApi } from './lib/api/auth.js';
  import Sidebar from './lib/components/layout/Sidebar.svelte';
  import Toast from './lib/components/common/Toast.svelte';
  import LoadingSpinner from './lib/components/common/LoadingSpinner.svelte';

  import Dashboard from './pages/Dashboard.svelte';
  import Login from './pages/Login.svelte';
  import Register from './pages/Register.svelte';
  import ProjectList from './pages/ProjectList.svelte';
  import ProjectBoard from './pages/ProjectBoard.svelte';
  import NotFound from './pages/NotFound.svelte';

  let appReady = false;
  let initializing = true;

  const routes = {
    '/': Dashboard,
    '/login': Login,
    '/register': Register,
    '/projects': ProjectList,
    '/projects/:id': ProjectBoard,
    '*': NotFound,
  };

  onMount(async () => {
    const token = localStorage.getItem('token');
    if (token) {
      try {
        const res = await authApi.user();
        setAuth(res.data.data, token);
      } catch {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
      }
    }
    initializing = false;
    appReady = true;
  });

  // Auth guard
  $: if (appReady && !$isAuthenticated && $location && $location !== '/login' && $location !== '/register') {
    push('/login');
  }

  // Redirect logged-in users away from auth pages
  $: if (appReady && $isAuthenticated && ($location === '/login' || $location === '/register')) {
    push('/');
  }
</script>

<Toast />

{#if initializing}
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <LoadingSpinner size="lg" />
  </div>
{:else if $isAuthenticated}
  <div class="min-h-screen bg-gray-50 flex">
    <Sidebar />
    <main class="flex-1 overflow-auto">
      <Router {routes} />
    </main>
  </div>
{:else}
  <Router {routes} />
{/if}
