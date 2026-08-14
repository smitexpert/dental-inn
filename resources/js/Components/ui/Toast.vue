<script setup>
// DaisyUI toast container. Toasts are pushed via the useToast() composable
// (resources/js/Composables/useToast.js) into a shared reactive list; this
// component reads that list and renders DaisyUI alert rows.
import { useToasts } from '@/Composables/useToast.js'

const toasts = useToasts()
</script>

<template>
  <div class="toast toast-top toast-end z-[100]">
    <div
      v-for="t in toasts"
      :key="t.id"
      role="status"
      class="alert"
      :class="{
        'alert-success': t.severity === 'success',
        'alert-error': t.severity === 'error',
        'alert-warning': t.severity === 'warning',
        'alert-info': t.severity === 'info',
      }"
    >
      <div>
        <h4 class="font-semibold">{{ t.summary }}</h4>
        <p class="text-sm">{{ t.detail }}</p>
      </div>
    </div>
  </div>
</template>
