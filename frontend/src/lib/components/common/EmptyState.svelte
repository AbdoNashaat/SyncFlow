<script>
  export let title = 'Nothing here';
  export let description = '';
  export let icon = '📭';
</script>

<div class="flex flex-col items-center justify-center py-12 text-gray-400">
  <span class="text-4xl mb-4">{icon}</span>
  <h3 class="text-lg font-medium text-gray-500">{title}</h3>
  {#if description}
    <p class="text-sm mt-1">{description}</p>
  {/if}
</div>
