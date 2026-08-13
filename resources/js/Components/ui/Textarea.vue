<script setup>
import { computed } from 'vue'
import PrimeTextarea from 'primevue/textarea'

const props = defineProps({
  modelValue: { type: String, default: '' },
  rows: { type: Number, default: 4 },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])

const classes = computed(() => [
  'w-full px-3 py-2 rounded-md bg-surface text-foreground placeholder:text-muted-foreground',
  'focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50',
  props.invalid ? 'border border-danger' : 'border border-border',
].join(' '))
</script>

<template>
  <PrimeTextarea
    :rows="rows"
    :model-value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :pt="{ root: { class: classes } }"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
