<script>
  import LoginForm from '../lib/components/auth/LoginForm.svelte';
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900">SyncFlow</h1>
      <p class="mt-2 text-gray-600">Sign in to your workspace</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-6">
      <LoginForm />
      <p class="mt-4 text-center text-sm text-gray-600">
        Don't have an account?
        <a href="#/register" class="text-indigo-600 hover:text-indigo-800 font-medium">Sign up</a>
      </p>
    </div>
  </div>
</div>
