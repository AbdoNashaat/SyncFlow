<script>
  import RegisterForm from '../lib/components/auth/RegisterForm.svelte';
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900">SyncFlow</h1>
      <p class="mt-2 text-gray-600">Create your account</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-6">
      <RegisterForm />
      <p class="mt-4 text-center text-sm text-gray-600">
        Already have an account?
        <a href="#/login" class="text-indigo-600 hover:text-indigo-800 font-medium">Sign in</a>
      </p>
    </div>
  </div>
</div>
