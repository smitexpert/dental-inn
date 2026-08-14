<script setup>
import PrimeCheckbox from 'primevue/checkbox'

defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  label: { type: String, default: null },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <label class="inline-flex items-center gap-2 cursor-pointer">
    <PrimeCheckbox
      :model-value="modelValue"
      :disabled="disabled"
      :pt="{
        root: {
          class: 'w-4 h-4 rounded border border-border text-primary-600 focus:ring-2 focus:ring-primary-500',
        },
        box: { class: 'rounded' },
      }"
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <span v-if="label" class="text-sm text-foreground">{{ label }}</span>
  </label>
</template>
