<script setup>
/**
 * Print layout — bare. Renders only the slot, with no sidebar,
 * top bar, or auth chrome so a printed page is just the content.
 * Used by the prescription print view to keep the page on a clean
 * sheet without `@media print` rules that fight the main layout.
 */
</script>

<template>
  <div class="min-h-screen bg-base-100 text-base-content">
    <slot />
  </div>
</template>
