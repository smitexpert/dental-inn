<script setup>
import PrimeSelect from 'primevue/select'

defineProps({
  modelValue: { type: [String, Number, null], default: null },
  options: { type: Array, required: true },
  optionLabel: { type: [String, Function], default: 'label' },
  optionValue: { type: [String, Function], default: 'value' },
  placeholder: { type: String, default: 'Select…' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
})
defineEmits(['update:modelValue', 'change'])
</script>

<template>
  <PrimeSelect
    :model-value="modelValue"
    :options="options"
    :option-label="optionLabel"
    :option-value="optionValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :pt="{
      root: { class: 'h-10 w-full rounded-md border bg-surface' + (invalid ? ' border-danger' : ' border-border') },
      label: { class: 'px-3 text-foreground' },
      panel: { class: 'rounded-md border border-border bg-surface' },
      option: { class: 'px-3 py-2 hover:bg-surface-muted cursor-pointer' },
    }"
    @update:model-value="$emit('update:modelValue', $event)"
    @change="$emit('change', $event)"
  />
</template>
