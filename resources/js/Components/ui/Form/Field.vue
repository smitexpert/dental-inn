<script setup>
defineProps({
  label: { type: String, default: null },
  error: { type: String, default: null },
  required: { type: Boolean, default: false },
  hint: { type: String, default: null },
  htmlFor: { type: String, default: null },
})
</script>

<template>
  <div>
    <label v-if="label" :for="htmlFor" class="block text-sm font-medium text-foreground mb-1">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    <slot />
    <p v-if="hint && !error" class="mt-1 text-xs text-muted-foreground">{{ hint }}</p>
    <p v-if="error" class="mt-1 text-sm text-danger">{{ error }}</p>
  </div>
</template>
