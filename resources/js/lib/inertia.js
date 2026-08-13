import { useForm } from '@inertiajs/vue3'

/**
 * Thin wrapper around Inertia's useForm that:
 *  - exposes a `processing` ref bound to the form's transform callback
 *  - optionally resets specific fields on success
 *
 * @param {Record<string, any>} initial
 * @param {{ resetOnSuccess?: string[] }} [options]
 */
export function useInertiaForm(initial, options = {}) {
  const form = useForm(initial)

  form.submit = (method, url, extra = {}) => {
    const submitConfig = {
      onSuccess: () => {
        if (options.resetOnSuccess?.length) {
          form.reset(...options.resetOnSuccess)
        }
      },
      ...extra,
    }
    form.submit(method, url, submitConfig)
  }

  return form
}
