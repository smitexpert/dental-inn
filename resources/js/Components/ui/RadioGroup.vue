<script setup>
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
  <div class="flex flex-col gap-2">
    <label
      v-for="opt in options"
      :key="String(opt[optionValue])"
      class="label cursor-pointer gap-2 justify-start"
    >
      <input
        type="radio"
        :name="name"
        :value="opt[optionValue]"
        :checked="modelValue === opt[optionValue]"
        :disabled="disabled"
        class="radio radio-primary"
        @change="$emit('update:modelValue', opt[optionValue])"
      />
      <span class="label-text">{{ opt[optionLabel] }}</span>
    </label>
  </div>
</template>
