<script setup>
/**
 * Textarea
 * --------
 * Multi-line text input with `.input-shell` styling and a min-height that
 * prevents collapse for short content.
 */
defineProps({
  modelValue: { type: String, default: '' },
  rows: { type: Number, default: 3 },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <textarea
    :rows="rows"
    :value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :class="['input-shell min-h-20 resize-y w-full', invalid ? 'input-shell-error' : '']"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
