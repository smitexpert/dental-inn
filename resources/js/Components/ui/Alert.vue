<script setup>
/**
 * Alert
 * -----
 * Inline feedback block with a tone-matched icon on the left of the title
 * and the message body beneath. Variants: neutral, info, success, warning,
 * danger.
 */
import { computed } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'info',
    validator: (v) => ['neutral', 'info', 'success', 'warning', 'danger'].includes(v),
  },
  title: { type: String, default: null },
  icon: { type: String, default: null },
})

const toneClass = {
  neutral: 'bg-base-200 text-base-content border-(--color-line)',
  info: 'bg-primary/10 text-primary border-primary/30',
  success: 'bg-success/10 text-success border-success/30',
  warning: 'bg-warning/10 text-warning border-warning/30',
  danger: 'bg-error/10 text-error border-error/30',
}

const defaultIcon = {
  neutral: 'info',
  info: 'info',
  success: 'check-circle',
  warning: 'alert-triangle',
  danger: 'alert-triangle',
}

const iconName = computed(() => props.icon ?? defaultIcon[props.variant])
</script>

<template>
  <div
    :class="['rounded-md border px-3 py-2.5 text-sm flex items-start gap-2.5', toneClass[variant]]"
    role="alert"
  >
    <Icon v-if="iconName" :name="iconName" size="md" class="mt-0.5 shrink-0" />
    <div class="min-w-0 flex-1">
      <p v-if="title" class="font-semibold leading-5">{{ title }}</p>
      <p :class="title ? 'mt-0.5' : ''"><slot /></p>
    </div>
  </div>
</template>
