/**
 * Toast composable.
 *
 * DaisyUI-based toast notifications. Pages call useToast().success(msg) /
 * .error(msg) / etc. and the returned functions dispatch a DaisyUI-styled
 * toast into a global container.
 *
 * The global container is mounted once by the Authenticated layout via
 * <ToastContainer /> — see resources/js/Components/ui/ToastContainer.vue.
 */

import { ref } from 'vue'

const toasts = ref([])
let nextId = 1

function push(severity, summary, detail, life) {
  const id = nextId++
  toasts.value.push({ id, severity, summary, detail, life })
  setTimeout(() => {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }, life)
}

export function useToasts() {
  return toasts
}

export function useToast() {
  return {
    success: (msg) => push('success', 'Success', msg, 4000),
    error: (msg) => push('error', 'Error', msg, 5000),
    warn: (msg) => push('warning', 'Warning', msg, 4000),
    info: (msg) => push('info', 'Info', msg, 4000),
  }
}
