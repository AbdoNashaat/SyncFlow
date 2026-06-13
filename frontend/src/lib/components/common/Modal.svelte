<script>
  import { createEventDispatcher } from 'svelte';

  export let show = false;
  export let title = '';
  export let size = 'md';

  const dispatch = createEventDispatcher();

  const sizeClasses = {
    sm: 'max-w-md',
    md: 'max-w-2xl',
    lg: 'max-w-4xl',
  };

  function close() {
    dispatch('close');
  }

  function handleBackdrop(e) {
    if (e.target === e.currentTarget) close();
  }

  function handleKeydown(e) {
    if (e.key === 'Escape') close();
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show}
  <!-- svelte-ignore a11y_click_events_have_key_events a11y_no_static_element_interactions -->
  <div
    class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4"
    on:click={handleBackdrop}
  >
    <div class="bg-white rounded-xl shadow-2xl w-full {sizeClasses[size]} max-h-[90vh] flex flex-col">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-900">{title}</h2>
        <button
          class="text-gray-400 hover:text-gray-600 transition-colors"
          on:click={close}
          aria-label="Close"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="overflow-y-auto p-6">
        <slot />
      </div>
    </div>
  </div>
{/if}
