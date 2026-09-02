<script setup>
/**
 * Toast
 * -----
 * DaisyUI-based toast container. Reads from `useToast()` composable and
 * renders each toast with a tone-tinted Icon and animated entry.
 */
import { useToasts } from '@/Composables/useToast.js'
import Icon from './Icon.vue'

const toasts = useToasts()

const toneClass = {
  success: 'bg-success/10 text-success border-success/30',
  error: 'bg-error/10 text-error border-error/30',
  warning: 'bg-warning/10 text-warning border-warning/30',
  info: 'bg-primary/10 text-primary border-primary/30',
}

const iconName = {
  success: 'check-circle',
  error: 'alert-triangle',
  warning: 'alert-triangle',
  info: 'info',
}
</script>

<template>
  <div class="toast toast-top toast-end z-[100]">
    <TransitionGroup
      tag="div"
      class="flex flex-col gap-2"
      enter-active-class="transition duration-200 ease-(--ease-out-soft)"
      leave-active-class="transition duration-150 ease-(--ease-out-soft)"
      enter-from-class="opacity-0 translate-x-2"
      leave-to-class="opacity-0 translate-x-2"
    >
      <div
        v-for="t in toasts"
        :key="t.id"
        role="status"
        :class="['alert flex items-start gap-2.5 border shadow-(--shadow-pop)', toneClass[t.severity] || toneClass.info]"
      >
        <Icon :name="iconName[t.severity] || 'info'" size="md" class="mt-0.5 shrink-0" />
        <div class="min-w-0">
          <h4 class="font-semibold text-sm">{{ t.summary }}</h4>
          <p class="text-sm opacity-90">{{ t.detail }}</p>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>
