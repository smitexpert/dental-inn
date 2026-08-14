<script setup>
import DatePicker from 'primevue/datepicker'

defineProps({
  modelValue: { type: [Date, String, null], default: null },
  placeholder: { type: String, default: 'Pick a time' },
  timeOnly: { type: Boolean, default: true },
  hourFormat: { type: String, default: '24' },
  disabled: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <DatePicker
    :model-value="modelValue"
    :placeholder="placeholder"
    :time-only="timeOnly"
    :hour-format="hourFormat"
    :disabled="disabled"
    :pt="{
      root: { class: 'w-full' },
      input: {
        class:
          'w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500',
      },
    }"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
