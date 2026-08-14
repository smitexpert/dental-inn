<script setup>
defineProps({
  errors: { type: Object, default: () => ({}) },
})
</script>

<template>
  <div
    v-if="Object.keys(errors).length"
    class="rounded-md border border-danger/30 bg-danger/10 p-3 text-sm text-danger"
  >
    <p class="font-semibold mb-1">Please correct the following:</p>
    <ul class="list-disc list-inside space-y-0.5">
      <li v-for="(msg, field) in errors" :key="field">{{ msg }}</li>
    </ul>
  </div>
</template>
