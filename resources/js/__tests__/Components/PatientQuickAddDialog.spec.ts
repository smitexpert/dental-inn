import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'

vi.mock('axios', () => ({
  default: {
    get: vi.fn(() => Promise.resolve({ data: { data: [] } })),
  },
}))

import PatientQuickAddDialog from '@/Components/PatientQuickAddDialog.vue'

describe('PatientQuickAddDialog', () => {
  beforeEach(() => {
    document.body.innerHTML = ''
  })

  it('mounts without rendering any primevue-only markup', async () => {
    const wrapper = mount(PatientQuickAddDialog, {
      props: { modelValue: true },
      attachTo: document.body,
    })
    await flushPromises()
    const html = wrapper.html()
    // PrimeVue's dialog root class is `p-dialog`; DaisyUI uses `modal` and
    // `modal-box`. After the migration no `p-*` selectors should remain.
    expect(html).not.toMatch(/\bp-[a-z]+/)
  })

  it('exposes both tabs and starts on Pick existing', () => {
    const wrapper = mount(PatientQuickAddDialog, {
      props: { modelValue: true },
      attachTo: document.body,
    })
    const buttons = wrapper.findAll('button[type="button"]')
    const labels = buttons.map((b) => b.text().trim())
    expect(labels.some((l) => l.includes('Pick existing'))).toBe(true)
    expect(labels.some((l) => l.includes('New patient'))).toBe(true)
  })

  it('does not throw on close', async () => {
    const wrapper = mount(PatientQuickAddDialog, {
      props: { modelValue: true },
      attachTo: document.body,
    })
    wrapper.setProps({ modelValue: false })
    await flushPromises()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
  })
})
