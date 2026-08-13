<script setup>
import DatePicker from 'primevue/datepicker'

defineProps({
  modelValue: { type: [Date, String, null], default: null },
  placeholder: { type: String, default: 'Pick a date' },
  showTime: { type: Boolean, default: false },
  dateFormat: { type: String, default: 'yy-mm-dd' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <DatePicker
    :model-value="modelValue"
    :placeholder="placeholder"
    :show-time="showTime"
    :date-format="dateFormat"
    :disabled="disabled"
    :pt="{
      root: { class: 'w-full' },
      input: { class: 'w-full h-10 px-3 rounded-md bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500' + (invalid ? ' border border-danger' : ' border border-border') },
      panel: { class: 'rounded-md border border-border bg-surface shadow-md mt-1' },
    }"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
