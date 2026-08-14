<script setup>
defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'ghost', 'danger'].includes(v),
  },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
  loading: { type: Boolean, default: false },
  type: { type: String, default: 'button', validator: (v) => ['button', 'submit', 'reset'].includes(v) },
  disabled: { type: Boolean, default: false },
})

const sizeClass = { sm: 'btn-sm', md: '', lg: 'btn-lg' }
const variantClass = {
  primary: 'btn-primary',
  secondary: 'btn-neutral',
  ghost: 'btn-ghost',
  danger: 'btn-error',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="btn"
    :class="[variantClass[variant], sizeClass[size]]"
  >
    <span v-if="loading" class="loading loading-spinner loading-sm" />
    <slot />
  </button>
</template>
