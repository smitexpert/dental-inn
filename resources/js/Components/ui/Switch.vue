<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <input
    type="checkbox"
    class="toggle toggle-primary"
    :disabled="disabled"
    :checked="modelValue"
    @change="$emit('update:modelValue', $event.target.checked)"
  />
</template>
