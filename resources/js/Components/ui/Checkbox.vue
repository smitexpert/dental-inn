<script setup>
/**
 * Checkbox
 * --------
 * Custom checkbox that renders a check icon when active. Optional
 * description sits below the label.
 */
import Icon from './Icon.vue'

defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  label: { type: String, default: null },
  description: { type: String, default: null },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <label
    :class="[
      'inline-flex items-start gap-2 select-none',
      disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
    ]"
  >
    <span
      :class="[
        'relative inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition-colors duration-150 ease-(--ease-out-soft)',
        modelValue
          ? 'bg-primary border-primary text-primary-content'
          : 'bg-base-100 border-(--color-line-strong) text-transparent',
      ]"
    >
      <input
        type="checkbox"
        class="absolute inset-0 h-full w-full opacity-0 cursor-pointer"
        :disabled="disabled"
        :checked="modelValue"
        @change="$emit('update:modelValue', $event.target.checked)"
      />
      <Icon v-if="modelValue" name="check" size="sm" stroke-width="3" />
    </span>
    <span v-if="label || description || $slots.default" class="flex flex-col">
      <span v-if="label" class="text-sm font-medium text-base-content leading-5">{{ label }}</span>
      <span v-if="description" class="text-xs text-(--color-muted)">{{ description }}</span>
      <slot />
    </span>
  </label>
</template>
