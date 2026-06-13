<script>
  import { toasts, dismissToast } from '../../stores/ui.js';
</script>

<div class="fixed top-4 right-4 z-50 flex flex-col gap-2">
  {#each $toasts as toast (toast.id)}
    <button
      class="px-4 py-3 rounded-lg shadow-lg text-white text-sm max-w-sm cursor-pointer transition-all animate-slide-in text-left"
      class:bg-green-600={toast.type === 'success'}
      class:bg-red-600={toast.type === 'error'}
      class:bg-blue-600={toast.type === 'info'}
      class:bg-yellow-600={toast.type === 'warning'}
      on:click={() => dismissToast(toast.id)}
    >
      <div class="flex items-center gap-2">
        {#if toast.type === 'success'}
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {:else if toast.type === 'error'}
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        {/if}
        <span>{toast.message}</span>
      </div>
    </button>
  {/each}
</div>

<style>
  @keyframes slide-in {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  .animate-slide-in { animation: slide-in 0.3s ease-out; }
</style>
