<script setup>
defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => ['neutral', 'primary', 'success', 'warning', 'danger'].includes(v),
  },
})

const classes = {
  neutral: 'bg-surface-muted text-muted-foreground border-border',
  primary: 'bg-primary-50 text-primary-700 border-primary-200',
  success: 'bg-success/10 text-success border-success/30',
  warning: 'bg-warning/10 text-warning border-warning/30',
  danger: 'bg-danger/10 text-danger border-danger/30',
}
</script>

<template>
  <span
    :class="['inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border', classes[variant]]"
  >
    <slot />
  </span>
</template>
