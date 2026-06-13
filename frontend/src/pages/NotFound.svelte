<script>
  import { push } from 'svelte-spa-router';
</script>

<div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
  <h1 class="text-6xl font-bold text-gray-200 mb-4">404</h1>
  <p class="text-lg text-gray-600 mb-6">Page not found</p>
  <button
    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
    on:click={() => push('/')}
  >
    Go to Dashboard
  </button>
</div>
