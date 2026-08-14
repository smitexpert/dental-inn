import { computed } from 'vue'
import { useCurrentUser } from './useCurrentUser.js'

/**
 * Returns the navigation items visible to the current user.
 * Each item declares an optional `permission` or `role`; if the user
 * doesn't satisfy it, the item is filtered out.
 *
 * @returns {import('vue').ComputedRef<import('@/types/nav.js').NavItem[]>}
 */
export function useNav() {
  const { can, isAdmin } = useCurrentUser()

  const all = [
    { label: 'Dashboard', href: '/dashboard', icon: 'home', permission: 'view-dashboard' },
    { label: 'Appointments', href: '#', icon: 'calendar', comingSoon: true, permission: 'view-dashboard' },
    { label: 'Patients', href: '#', icon: 'users', comingSoon: true, permission: 'view-dashboard' },
    { label: 'Billing', href: '#', icon: 'receipt', comingSoon: true, permission: 'view-dashboard' },
    { label: 'Inventory', href: '#', icon: 'package', comingSoon: true, permission: 'view-dashboard' },
    { label: 'Exchange', href: '#', icon: 'arrows', comingSoon: true, permission: 'view-dashboard' },
    { label: 'Settings', href: '/settings', icon: 'cog', permission: 'manage-settings' },
    { label: 'Users', href: '/admin/users', icon: 'shield', permission: 'manage-users' },
  ]

  return computed(() =>
    all.filter((item) => {
      if (item.permission && !can(item.permission)) return false
      if (item.role && !isAdmin.value) return false
      return true
    }),
  )
}
