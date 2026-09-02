<script setup>
/**
 * Field
 * -----
 * Label + control + helper/error message group. Optional `description` sits
 * under the label, `aside` slot floats right (for "forgot?" links and the
 * like), `required` adds an asterisk, `hint`/`error` print below the input.
 */
defineProps({
  label: { type: String, default: null },
  description: { type: String, default: null },
  error: { type: String, default: null },
  required: { type: Boolean, default: false },
  hint: { type: String, default: null },
  htmlFor: { type: String, default: null },
})
</script>

<template>
  <div>
    <div v-if="label || $slots.aside" class="flex items-start justify-between gap-2 mb-1.5">
      <div class="min-w-0">
        <label
          v-if="label"
          :for="htmlFor"
          class="block text-sm font-medium text-base-content"
        >
          {{ label }}
          <span v-if="required" class="text-error">*</span>
        </label>
        <p v-if="description" class="text-xs text-(--color-muted) mt-0.5">
          {{ description }}
        </p>
      </div>
      <div v-if="$slots.aside" class="shrink-0 text-sm">
        <slot name="aside" />
      </div>
    </div>
    <slot />
    <p v-if="hint && !error" class="mt-1.5 text-xs text-(--color-muted)">{{ hint }}</p>
    <p v-if="error" class="mt-1.5 text-xs text-error">{{ error }}</p>
  </div>
</template>
