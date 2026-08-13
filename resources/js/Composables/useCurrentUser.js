import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useCurrentUser() {
  const page = usePage()

  const user = computed(() => page.props.auth?.user ?? null)
  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => user.value?.roles?.includes('admin') ?? false)
  const isDoctor = computed(() => user.value?.roles?.includes('doctor') ?? false)
  const isReceptionist = computed(() => user.value?.roles?.includes('receptionist') ?? false)
  const can = (permission) => user.value?.permissions?.includes(permission) ?? false

  return { user, isAuthenticated, isAdmin, isDoctor, isReceptionist, can }
}
