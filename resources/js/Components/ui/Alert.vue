<script setup>
defineProps({
  variant: {
    type: String,
    default: 'info',
    validator: (v) => ['info', 'success', 'warning', 'danger'].includes(v),
  },
  title: { type: String, default: null },
})

const classes = {
  info: 'bg-primary/10 text-primary border-primary-200',
  success: 'bg-success/10 text-success border-success/30',
  warning: 'bg-warning/10 text-warning border-warning/30',
  danger: 'bg-error/10 text-error border-error/30',
}
</script>

<template>
  <div :class="['rounded-md border px-3 py-2 text-sm', classes[variant]]" role="alert">
    <p v-if="title" class="font-semibold mb-0.5">{{ title }}</p>
    <p><slot /></p>
  </div>
</template>
