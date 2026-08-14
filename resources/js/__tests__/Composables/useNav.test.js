import { describe, it, expect, vi } from 'vitest'
import { ref } from 'vue'

vi.mock('@inertiajs/vue3', () => ({ usePage: () => ({ url: '/dashboard' }) }))

const mockUser = (permissions = []) => ({ auth: { user: { name: 'X', roles: ['admin'], permissions } } })

vi.mock('@/Composables/useCurrentUser.js', () => ({
  useCurrentUser: () => {
    const p = (window.__testPerms ?? [])
    return {
      isAdmin: { value: true },
      can: (perm) => p.includes(perm),
    }
  },
}))

import { useNav } from '@/Composables/useNav.js'

describe('useNav', () => {
  it('includes appointments when user has view-appointments', () => {
    window.__testPerms = ['view-appointments', 'view-dashboard']
    const items = useNav().value
    expect(items.find((i) => i.href === '/appointments')).toBeTruthy()
  })

  it('includes doctors when user has view-doctors', () => {
    window.__testPerms = ['view-doctors']
    const items = useNav().value
    expect(items.find((i) => i.href === '/doctors')).toBeTruthy()
  })

  it('excludes appointments without permission', () => {
    window.__testPerms = ['view-dashboard']
    const items = useNav().value
    expect(items.find((i) => i.href === '/appointments')).toBeFalsy()
  })

  it('includes patients link with view-patients permission and no comingSoon', () => {
    window.__testPerms = ['view-patients']
    const items = useNav().value
    const patients = items.find((i) => i.href === '/patients')
    expect(patients).toBeTruthy()
    expect(patients.comingSoon).toBeFalsy()
  })

  it('exposes Exchange group when user has view-exchange-rates', () => {
    window.__testPerms = ['view-exchange-rates', 'view-cash-movements', 'reconcile-cash-drawer']
    const items = useNav().value
    const exchange = items.find((i) => i.label === 'Exchange')
    expect(exchange).toBeTruthy()
    expect(exchange.href).toBe('/exchange')
    const labels = exchange.children.map((c) => c.label)
    expect(labels).toContain('Dashboard')
    expect(labels).toContain('Rates')
    expect(labels).toContain('Movements')
    expect(labels).toContain('Reconcile')
  })

  it('hides Exchange group without view-exchange-rates', () => {
    window.__testPerms = ['view-dashboard']
    const items = useNav().value
    expect(items.find((i) => i.label === 'Exchange')).toBeFalsy()
  })

  it('hides Reconcile child without reconcile permission', () => {
    window.__testPerms = ['view-exchange-rates', 'view-cash-movements']
    const items = useNav().value
    const exchange = items.find((i) => i.label === 'Exchange')
    expect(exchange.children.find((c) => c.label === 'Reconcile')).toBeFalsy()
  })
})
