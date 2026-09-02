<script setup>
/**
 * Switch
 * ------
 * Custom toggle styled to match `.app-card` aesthetic. Optional label and
 * description render alongside the control and stay clickable.
 */
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
      'inline-flex items-start gap-3 select-none',
      disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
    ]"
  >
    <button
      type="button"
      role="switch"
      :aria-checked="modelValue"
      :disabled="disabled"
      :class="[
        'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 ease-(--ease-out-soft) focus:outline-none app-focus-ring',
        modelValue ? 'bg-primary' : 'bg-base-300',
      ]"
      @click.prevent="!disabled && $emit('update:modelValue', !modelValue)"
    >
      <span
        :class="[
          'inline-block h-5 w-5 rounded-full bg-white shadow transform transition-transform duration-200 ease-(--ease-out-soft)',
          modelValue ? 'translate-x-5' : 'translate-x-0.5',
        ]"
      />
    </button>
    <span v-if="label || description" class="flex flex-col">
      <span v-if="label" class="text-sm font-medium text-base-content">{{ label }}</span>
      <span v-if="description" class="text-xs text-(--color-muted)">{{ description }}</span>
    </span>
  </label>
</template>
