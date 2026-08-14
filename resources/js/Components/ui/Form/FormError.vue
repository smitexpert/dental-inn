<script setup>
defineProps({
  errors: { type: Object, default: () => ({}) },
})
</script>

<template>
  <div
    v-if="Object.keys(errors).length"
    class="rounded-md border border-error/30 bg-error/10 p-3 text-sm text-error"
  >
    <p class="font-semibold mb-1">Please correct the following:</p>
    <ul class="list-disc list-inside space-y-0.5">
      <li v-for="(msg, field) in errors" :key="field">{{ msg }}</li>
    </ul>
  </div>
</template>
