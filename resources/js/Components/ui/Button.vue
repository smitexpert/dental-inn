<script setup>
/**
 * Button
 * ------
 * Primary action element. Supports icons (left/right or icon-only), loading
 * state with a spinner, and five visual variants. Uses DaisyUI `.btn` plus
 * a couple of Tailwind-only variants (soft) that need precise color control.
 */
import { computed } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'ghost', 'danger', 'soft', 'outline'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v),
  },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: {
    type: String,
    default: 'button',
    validator: (v) => ['button', 'submit', 'reset'].includes(v),
  },
  icon: { type: String, default: null },
  iconRight: { type: String, default: null },
  iconOnly: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
})

const sizeClass = {
  xs: 'btn-xs h-7 min-h-7 px-2 text-xs',
  sm: 'btn-sm h-9 min-h-9 px-3 text-sm',
  md: 'h-10 min-h-10 px-4 text-sm',
  lg: 'btn-lg h-12 min-h-12 px-6 text-base',
}

const variantClass = {
  primary: 'btn-primary',
  secondary: 'btn-neutral',
  ghost: 'btn-ghost',
  danger: 'btn-error',
  soft: 'bg-primary/10 text-primary hover:bg-primary/15 border-0',
  outline: 'btn-outline btn-primary',
}

const classes = computed(() => [
  'btn gap-2 rounded-lg font-medium normal-case shadow-none border-0',
  sizeClass[props.size],
  variantClass[props.variant],
  props.block ? 'w-full' : '',
  props.iconOnly ? 'btn-square px-0' : '',
  props.loading ? 'opacity-90 pointer-events-none' : '',
])
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="classes"
  >
    <span v-if="loading" class="loading loading-spinner loading-xs" />
    <Icon v-else-if="icon && !iconOnly" :name="icon" :size="size === 'lg' ? 'md' : 'sm'" />
    <Icon v-if="iconOnly && !loading" :name="icon ?? iconRight" :size="size === 'lg' ? 'md' : 'sm'" />
    <slot v-if="!iconOnly" />
    <Icon v-if="iconRight && !loading && !iconOnly" :name="iconRight" :size="size === 'lg' ? 'md' : 'sm'" />
  </button>
</template>
