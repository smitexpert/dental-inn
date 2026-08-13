import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm'

let _confirm = null

export function provideConfirmInstance(instance) {
  _confirm = instance
}

export function useConfirm() {
  return _confirm ?? usePrimeConfirm()
}
