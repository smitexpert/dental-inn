import { describe, it, expect, vi, beforeEach } from 'vitest'

const mockState = { props: { auth: { user: { name: 'Jane', roles: ['admin'], permissions: ['manage-doctors'] } } } }

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => mockState,
}))

import { useCurrentUser } from '@/Composables/useCurrentUser.js'

describe('useCurrentUser', () => {
  it('returns the user', () => {
    const { user } = useCurrentUser()
    expect(user.value.name).toBe('Jane')
  })

  it('detects admin role', () => {
    const { isAdmin } = useCurrentUser()
    expect(isAdmin.value).toBe(true)
  })

  it('checks permissions', () => {
    const { can } = useCurrentUser()
    expect(can('manage-doctors')).toBe(true)
    expect(can('manage-appointments')).toBe(false)
  })
})
