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
    {
      label: 'Billing',
      href: '/billing/invoices',
      icon: 'receipt',
      permission: 'view-invoices',
      children: [
        { label: 'Invoices', href: '/billing/invoices', permission: 'view-invoices' },
        { label: 'Payments', href: '/billing/payments', permission: 'view-payments' },
        { label: 'Expenses', href: '/billing/expenses', permission: 'view-expenses' },
        { label: 'Chart of accounts', href: '/billing/accounts', permission: 'view-invoices' },
      ],
    },
    {
      label: 'Exchange',
      href: '/exchange',
      icon: 'wallet',
      permission: 'view-exchange-rates',
      children: [
        { label: 'Dashboard', href: '/exchange', permission: 'view-exchange-rates' },
        { label: 'Rates', href: '/exchange/rates', permission: 'view-exchange-rates' },
        { label: 'Movements', href: '/exchange/movements', permission: 'view-cash-movements' },
        { label: 'Reconcile', href: '/exchange/reconcile', permission: 'reconcile-cash-drawer' },
      ],
    },
    {
      label: 'Reports',
      href: '/reports/ledger',
      icon: 'chart',
      permission: 'view-reports',
      children: [
        { label: 'Ledger', href: '/reports/ledger', permission: 'view-reports' },
        { label: 'Income statement', href: '/reports/income-statement', permission: 'view-reports' },
        { label: 'Balance sheet', href: '/reports/balance-sheet', permission: 'view-reports' },
        { label: 'A/R aging', href: '/reports/accounts-receivable', permission: 'view-reports' },
      ],
    },
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
