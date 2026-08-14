<script setup>
defineProps({})
defineEmits([])
</script>

<template>
  <div class="dropdown">
    <div tabindex="0" role="button">
      <slot name="trigger" />
    </div>
    <div tabindex="0" class="dropdown-content z-40 mt-2 bg-base-100 border border-base-300 rounded-md shadow-md p-3">
      <slot />
    </div>
  </div>
</template>
