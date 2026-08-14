<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  label: { type: String, default: null },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <label class="label cursor-pointer gap-2 justify-start">
    <input
      type="checkbox"
      class="checkbox checkbox-primary"
      :disabled="disabled"
      :checked="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    <span v-if="label" class="label-text">{{ label }}</span>
  </label>
</template>
