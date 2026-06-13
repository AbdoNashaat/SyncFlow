<script>
  import { authApi } from '../../api/auth.js';
  import { setAuth } from '../../stores/auth.js';
  import { addToast } from '../../stores/ui.js';
  import { push } from 'svelte-spa-router';

  let email = '';
  let password = '';
  let loading = false;
  let error = '';

  async function handleSubmit() {
    error = '';
    loading = true;
    try {
      const res = await authApi.login({ email, password });
      setAuth(res.data.user, res.data.token);
      addToast('Welcome back!', 'success');
      push('/');
    } catch (e) {
      error = e.response?.data?.message || 'Login failed';
    } finally {
      loading = false;
    }
  }
</script>

<form on:submit|preventDefault={handleSubmit} class="space-y-4">
  <div>
    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
    <input
      id="email"
      type="email"
      bind:value={email}
      required
      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
      placeholder="you@example.com"
    />
  </div>
  <div>
    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
    <input
      id="password"
      type="password"
      bind:value={password}
      required
      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
      placeholder="Enter your password"
    />
  </div>
  {#if error}
    <p class="text-sm text-red-600">{error}</p>
  {/if}
  <button
    type="submit"
    disabled={loading}
    class="w-full flex justify-center py-2.5 px-4 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
  >
    {loading ? 'Signing in...' : 'Sign in'}
  </button>
</form>
