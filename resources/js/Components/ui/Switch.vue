<script setup>
import InputSwitch from 'primevue/inputswitch'

defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <InputSwitch
    :model-value="modelValue"
    :disabled="disabled"
    :pt="{
      root: { class: 'relative inline-block w-10 h-6 rounded-full transition-colors cursor-pointer' + (modelValue ? ' bg-primary-600' : ' bg-surface-muted border border-border') },
      slider: { class: 'absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform shadow' + (modelValue ? ' translate-x-4' : '') },
    }"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
