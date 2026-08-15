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

  it('hides Exchange group without view-exchange-rates', () => {
    window.__testPerms = ['view-dashboard']
    const items = useNav().value
    expect(items.find((i) => i.label === 'Exchange')).toBeFalsy()
  })

  it('exposes Bank & Cash group when user has view-accounts', () => {
    window.__testPerms = ['view-accounts', 'view-transfers', 'reconcile-accounts']
    const items = useNav().value
    const bank = items.find((i) => i.label === 'Bank & Cash')
    expect(bank).toBeTruthy()
    expect(bank.href).toBe('/bank')
    const labels = bank.children.map((c) => c.label)
    expect(labels).toContain('Dashboard')
    expect(labels).toContain('Accounts')
    expect(labels).toContain('Transfers')
    expect(labels).toContain('Reconcile')
  })

  it('hides Bank & Cash group without view-accounts', () => {
    window.__testPerms = ['view-dashboard']
    const items = useNav().value
    expect(items.find((i) => i.label === 'Bank & Cash')).toBeFalsy()
  })
})
