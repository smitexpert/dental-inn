<script setup>
defineProps({
  modelValue: { type: String, default: '' },
  rows: { type: Number, default: 4 },
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
    class="textarea textarea-bordered w-full"
    :class="invalid ? 'textarea-error' : ''"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
