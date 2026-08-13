import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from './useToast.js'

export function useFlashMessages() {
  const page = usePage()
  const toast = useToast()

  watch(
    () => ({ ...page.props.flash }),
    (flash) => {
      if (flash.success) toast.success(flash.success)
      if (flash.error)   toast.error(flash.error)
      if (flash.warning) toast.warn(flash.warning)
    },
    { deep: true, immediate: true }
  )
}
