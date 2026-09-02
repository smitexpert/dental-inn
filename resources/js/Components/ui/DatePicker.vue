<script setup>
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
  <input
    :type="showTime ? 'datetime-local' : 'date'"
    :value="modelValue ?? ''"
    :placeholder="placeholder"
    :disabled="disabled"
    class="input input-bordered w-full"
    :class="invalid ? 'input-error' : ''"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
