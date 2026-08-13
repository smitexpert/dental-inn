<script setup>
import PrimeRadioButton from 'primevue/radiobutton'

defineProps({
  modelValue: { type: [String, Number, null], default: null },
  options: { type: Array, required: true },
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },
  name: { type: String, required: true },
  disabled: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <div class="space-y-2">
    <label
      v-for="opt in options"
      :key="opt[optionValue]"
      class="flex items-center gap-2 cursor-pointer text-sm text-foreground"
    >
      <PrimeRadioButton
        :model-value="modelValue"
        :value="opt[optionValue]"
        :name="name"
        :disabled="disabled"
        :pt="{ root: { class: 'w-4 h-4 text-primary-600 focus:ring-2 focus:ring-primary-500' } }"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      {{ opt[optionLabel] }}
    </label>
  </div>
</template>
