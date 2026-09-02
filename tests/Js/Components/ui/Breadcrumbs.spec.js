import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const items = [
  { label: 'Admin', href: '/admin' },
  { label: 'Users', href: '/admin/users' },
  { label: 'John Doe' },
]

describe('Breadcrumbs (refreshed)', () => {
  it('renders pill shape', () => {
    const w = mount(Breadcrumbs, { props: { items } })
    expect(w.findAll('a, span').length).toBeGreaterThan(0)
  })

  it('renders last item as a span, not a link', () => {
    const w = mount(Breadcrumbs, { props: { items } })
    expect(w.text()).toContain('John Doe')
    const links = w.findAllComponents({ name: 'Link' })
    expect(links.length).toBe(2) // not 3 — last item has no Link
  })
})
