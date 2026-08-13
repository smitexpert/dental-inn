import { useToast as usePrimeToast } from 'primevue/usetoast'

let _toast = null

export function provideToastInstance(instance) {
  _toast = instance
}

export function useToast() {
  const toast = _toast ?? usePrimeToast()

  return {
    success: (msg) => toast.add({ severity: 'success', summary: 'Success', detail: msg, life: 4000 }),
    error:   (msg) => toast.add({ severity: 'error',   summary: 'Error',   detail: msg, life: 5000 }),
    warn:    (msg) => toast.add({ severity: 'warn',    summary: 'Warning', detail: msg, life: 4000 }),
    info:    (msg) => toast.add({ severity: 'info',    summary: 'Info',    detail: msg, life: 4000 }),
  }
}
