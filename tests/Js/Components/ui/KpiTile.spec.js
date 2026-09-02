import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import KpiTile from '@/Components/ui/KpiTile.vue'

describe('KpiTile', () => {
  it('renders label and value', () => {
    const w = mount(KpiTile, { props: { label: 'Revenue', value: '৳84,250' } })
    expect(w.text()).toContain('Revenue')
    expect(w.text()).toContain('৳84,250')
  })

  it('applies kpi-tile class', () => {
    const w = mount(KpiTile, { props: { label: 'X', value: 1 } })
    expect(w.classes().join(' ')).toMatch(/kpi-tile/)
  })

  it('renders sparkline when series is provided', () => {
    const w = mount(KpiTile, {
      props: { label: 'X', value: 1, series: [1, 2, 3, 4, 5] },
    })
    expect(w.find('svg').exists()).toBe(true)
  })

  it('renders progress bar when bar is provided', () => {
    const w = mount(KpiTile, {
      props: { label: 'X', value: '12/24', bar: { filled: 12, total: 24 } },
    })
    expect(w.find('[data-testid="kpi-bar"]').exists()).toBe(true)
  })
})
