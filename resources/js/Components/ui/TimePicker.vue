<script setup>
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
  <input
    type="time"
    :value="modelValue ?? ''"
    :placeholder="placeholder"
    :disabled="disabled"
    class="input input-bordered w-full"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
