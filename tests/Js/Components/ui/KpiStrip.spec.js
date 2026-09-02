import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import KpiStrip from '@/Components/ui/KpiStrip.vue'
import KpiTile from '@/Components/ui/KpiTile.vue'

const sample = [
  { label: 'A', value: '1' },
  { label: 'B', value: '2' },
  { label: 'C', value: '3' },
]

describe('KpiStrip', () => {
  it('renders one KpiTile per item', () => {
    const w = mount(KpiStrip, { props: { tiles: sample } })
    expect(w.findAllComponents(KpiTile).length).toBe(3)
  })

  it('applies kpi-strip grid class', () => {
    const w = mount(KpiStrip, { props: { tiles: sample } })
    expect(w.classes().join(' ')).toMatch(/kpi-strip/)
  })

  it('passes tile props through unchanged', () => {
    const w = mount(KpiStrip, {
      props: { tiles: [{ label: 'Revenue', value: '৳84,250', series: [1, 2, 3] }] },
    })
    expect(w.text()).toContain('Revenue')
    expect(w.find('svg').exists()).toBe(true)
  })
})
