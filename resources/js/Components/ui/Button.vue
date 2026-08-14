<script setup>
import { computed } from 'vue'
import PrimeButton from 'primevue/button'

const props = defineProps({
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

const classes = computed(() => {
  const base =
    'inline-flex items-center justify-center gap-2 font-medium rounded-md transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed'
  const sizes = { sm: 'h-8 px-3 text-sm', md: 'h-10 px-4 text-sm', lg: 'h-12 px-6 text-base' }
  const variants = {
    primary: 'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-700',
    secondary: 'bg-surface-muted text-foreground border border-border hover:bg-surface-muted/70',
    ghost: 'text-foreground hover:bg-surface-muted',
    danger: 'bg-danger text-white hover:bg-danger/90',
  }
  return [base, sizes[props.size], variants[props.variant]].join(' ')
})
</script>

<template>
  <PrimeButton
    :type="type"
    :disabled="disabled || loading"
    :pt="{ root: { class: classes } }"
    :loading="loading"
  >
    <slot />
  </PrimeButton>
</template>
