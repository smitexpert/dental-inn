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
    <label v-if="label" :for="htmlFor" class="block text-sm font-medium text-base-content mb-1">
      {{ label }}
      <span v-if="required" class="text-error">*</span>
    </label>
    <slot />
    <p v-if="hint && !error" class="mt-1 text-xs text-base-content/70">{{ hint }}</p>
    <p v-if="error" class="mt-1 text-sm text-error">{{ error }}</p>
  </div>
</template>
