<script setup>
defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => ['neutral', 'primary', 'success', 'warning', 'danger'].includes(v),
  },
})

const classes = {
  neutral: 'bg-base-200 text-base-content/70 border-base-300',
  primary: 'bg-primary/10 text-primary border-primary-200',
  success: 'bg-success/10 text-success border-success/30',
  warning: 'bg-warning/10 text-warning border-warning/30',
  danger: 'bg-error/10 text-error border-error/30',
}
</script>

<template>
  <span
    :class="['inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border', classes[variant]]"
  >
    <slot />
  </span>
</template>
