<script>
  import { authApi } from '../../api/auth.js';
  import { setAuth } from '../../stores/auth.js';
  import { addToast } from '../../stores/ui.js';
  import { push } from 'svelte-spa-router';

  let name = '';
  let email = '';
  let password = '';
  let confirmPassword = '';
  let loading = false;
  let error = '';

  async function handleSubmit() {
    error = '';
    loading = true;
    try {
      const res = await authApi.register({
        name,
        email,
        password,
        password_confirmation: confirmPassword,
      });
      setAuth(res.data.user, res.data.token);
      addToast('Account created!', 'success');
      push('/');
    } catch (e) {
      const data = e.response?.data;
      if (data?.errors) {
        error = Object.values(data.errors).flat().join(', ');
      } else {
        error = data?.message || 'Registration failed';
      }
    } finally {
      loading = false;
    }
  }
</script>

<form on:submit|preventDefault={handleSubmit} class="space-y-4">
  <div>
    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
    <input
      id="name"
      type="text"
      bind:value={name}
      required
      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
      placeholder="Your name"
    />
  </div>
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
      minlength="8"
      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
      placeholder="At least 8 characters"
    />
  </div>
  <div>
    <label for="confirm" class="block text-sm font-medium text-gray-700">Confirm Password</label>
    <input
      id="confirm"
      type="password"
      bind:value={confirmPassword}
      required
      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
      placeholder="Repeat your password"
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
    {loading ? 'Creating account...' : 'Create account'}
  </button>
</form>
