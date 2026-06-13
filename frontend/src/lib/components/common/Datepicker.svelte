<script>
  import { onMount, onDestroy } from 'svelte';

  /**
   * Legacy jQuery Datepicker wrapper.
   * Deliberately demonstrates legacy jQuery widget integration in Svelte 5.
   * This component is a showpiece — not recommended for production apps.
   */

  // We use `as any` to bypass the build system treating this as a module import.
  // jQuery + jQuery-UI are loaded via CDN script tags in index.html, not bundled.
  const $ = window.jQuery;

  // Fallback: if CDN didn't load, use native date input.
  let loaded = false;

  /** Exported props */
  export let value = '';
  export let placeholder = 'Pick a date';
  export let minDate = null;
  export let maxDate = null;
  export let disabled = false;

  let inputEl;
  let useNative = false;

  onMount(() => {
    if (typeof $ !== 'undefined' && $.fn && $.fn.datepicker) {
      try {
        const opts = {
          dateFormat: 'yy-mm-dd',
          changeMonth: true,
          changeYear: true,
          yearRange: '-5:+5',
          onSelect: (dateText) => {
            value = dateText;
          },
        };
        if (minDate) opts.minDate = minDate;
        if (maxDate) opts.maxDate = maxDate;
        $(inputEl).datepicker(opts);
        if (value) $(inputEl).datepicker('setDate', value);
        loaded = true;
      } catch (e) {
        console.warn('jQuery datepicker init failed, falling back to native', e);
        useNative = true;
      }
    } else {
      useNative = true;
    }
  });

  onDestroy(() => {
    if (loaded && typeof $ !== 'undefined' && $.fn && $.fn.datepicker) {
      try {
        $(inputEl).datepicker('destroy');
      } catch (e) {
        // ignore
      }
    }
  });
</script>

<div class="relative">
  {#if useNative}
    <input
      type="date"
      bind:value
      {disabled}
      class="block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-sm {disabled ? 'bg-gray-100 cursor-not-allowed' : ''}"
    />
  {:else}
    <input
      bind:this={inputEl}
      type="text"
      autocomplete="off"
      readonly
      {placeholder}
      {disabled}
      class="block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none text-sm {disabled ? 'bg-gray-100 cursor-not-allowed' : ''}"
    />
  {/if}
</div>
