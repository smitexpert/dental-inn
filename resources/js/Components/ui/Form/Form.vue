<script setup>
defineProps({
  method: { type: String, default: 'post' },
  action: { type: String, default: null },
})
</script>

<template>
  <form
    :method="method"
    :action="action"
    @submit.prevent="$emit('submit', $event)"
    class="space-y-4"
  >
    <slot />
  </form>
</template>
