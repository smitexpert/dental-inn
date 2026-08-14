/**
 * Confirm composable.
 *
 * DaisyUI-based confirmation dialog. Replaces the previous PrimeVue
 * ConfirmationService. Pages call useConfirm().require({ message, header,
 * accept, reject, ... }) and the returned dialog renders via
 * <ConfirmDialog /> mounted in the Authenticated layout.
 */

import { ref } from 'vue'

const state = ref({
  open: false,
  header: '',
  message: '',
  acceptLabel: 'Yes',
  rejectLabel: 'Cancel',
  acceptClass: 'btn-primary',
  rejectClass: 'btn-ghost',
  accept: null,
  reject: null,
})

export function useConfirmDialog() {
  return state
}

export function useConfirm() {
  return {
    require(opts = {}) {
      state.value = {
        open: true,
        header: opts.header ?? 'Confirm',
        message: opts.message ?? '',
        acceptLabel: opts.acceptLabel ?? 'Yes',
        rejectLabel: opts.rejectLabel ?? 'Cancel',
        acceptClass: opts.acceptClass ?? 'btn-primary',
        rejectClass: opts.rejectClass ?? 'btn-ghost',
        accept: opts.accept ?? null,
        reject: opts.reject ?? null,
      }
    },
    close() {
      state.value.open = false
    },
  }
}
