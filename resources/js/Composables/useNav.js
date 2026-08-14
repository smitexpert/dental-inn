import { computed } from 'vue'
import { useCurrentUser } from './useCurrentUser.js'
import { usePage } from '@inertiajs/vue3'

/**
 * Returns the navigation items visible to the current user.
 * Each item declares an optional `permission` or `role`; if the user
 * doesn't satisfy it, the item is filtered out.
 *
 * @returns {import('vue').ComputedRef<import('@/types/nav.js').NavItem[]>}
 */
export function useNav() {
  const { can } = useCurrentUser()
  const page = usePage()

  const lowStockCount = computed(
    () => Number(page.props.stats?.low_stock_items?.value ?? 0),
  )

  const all = [
    { label: 'Dashboard', href: '/dashboard', icon: 'home', permission: 'view-dashboard' },
    { label: 'Appointments', href: '/appointments', icon: 'calendar', permission: 'view-appointments' },
    { label: 'Doctors', href: '/doctors', icon: 'stethoscope', permission: 'view-doctors' },
    { label: 'Patients', href: '/patients', icon: 'users', permission: 'view-patients' },
    {
      label: 'Inventory',
      href: '/inventory/products',
      icon: 'package',
      permission: 'view-products',
      badge: () => lowStockCount.value,
      children: [
        { label: 'Products', href: '/inventory/products' },
        { label: 'Suppliers', href: '/inventory/suppliers', permission: 'view-suppliers' },
        { label: 'Locations', href: '/inventory/locations' },
        { label: 'Low stock', href: '/inventory/low-stock' },
      ],
    },
    { label: 'Billing', href: '#', icon: 'receipt', comingSoon: true, permission: 'view-dashboard' },
    { label: 'Exchange', href: '#', icon: 'arrows', comingSoon: true, permission: 'view-dashboard' },
    { label: 'Settings', href: '/settings', icon: 'cog', permission: 'manage-settings' },
    { label: 'Users', href: '/admin/users', icon: 'shield', permission: 'manage-users' },
  ]

  return computed(() =>
    all
      .filter((item) => {
        if (item.permission && !can(item.permission)) return false
        if (item.role && !can('admin')) return false
        return true
      })
      .map((item) => ({
        ...item,
        children: item.children?.filter((c) => !c.permission || can(c.permission)),
        badge: item.badge ? item.badge() : null,
      })),
  )
}
