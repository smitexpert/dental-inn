import { mount } from '@vue/test-utils'
import { describe, expect, it, vi, beforeEach } from 'vitest'
import { nextTick } from 'vue'
import ResponsiveTable from '@/Components/ui/ResponsiveTable.vue'

const columns = [
  { key: 'name',  label: 'Name' },
  { key: 'email', label: 'Email' },
]

const rows = [
  { id: 1, name: 'Alice', email: 'a@x.test' },
  { id: 2, name: 'Bob',   email: 'b@x.test' },
]

describe('ResponsiveTable', () => {
  beforeEach(() => {
    Object.defineProperty(window, 'matchMedia', {
      writable: true,
      value: vi.fn().mockImplementation((q) => ({
        matches: false,        // default: wide screen
        media: q,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
      })),
    })
  })

  it('renders a table on wide screens', async () => {
    const w = mount(ResponsiveTable, { props: { columns, rows } })
    await nextTick()
    expect(w.find('table').exists()).toBe(true)
    expect(w.findAll('tbody tr').length).toBe(2)
  })

  it('renders card list on narrow screens', async () => {
    window.matchMedia = vi.fn().mockImplementation((q) => ({
      matches: true,           // narrow
      media: q,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
    }))
    const w = mount(ResponsiveTable, {
      props: { columns, rows },
      slots: { card: '<div class="card-stub" />' },
    })
    await nextTick()
    expect(w.find('table').exists()).toBe(false)
    expect(w.findAll('.card-stub').length).toBe(2)
  })
})