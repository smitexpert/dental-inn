import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from '@/Components/ui/Button.vue'

const factory = (props = {}, slots = { default: 'Click me' }) => mount(Button, { props, slots })

describe('UiButton', () => {
  it('renders default primary variant classes', () => {
    const wrapper = factory()
    const html = wrapper.html()
    expect(html).toContain('cta-gradient')
  })

  it('renders secondary variant', () => {
    const wrapper = factory({ variant: 'secondary' })
    expect(wrapper.html()).toContain('bg-base-100')
  })

  it('renders danger variant', () => {
    const wrapper = factory({ variant: 'danger' })
    expect(wrapper.html()).toContain('bg-error')
  })

  it('renders gradient variant', () => {
    const wrapper = factory({ variant: 'gradient' })
    expect(wrapper.html()).toContain('cta-gradient')
  })

  it('renders size sm', () => {
    const wrapper = factory({ size: 'sm' })
    expect(wrapper.html()).toContain('btn-sm')
  })

  it('disables when loading', () => {
    const wrapper = factory({ loading: true })
    expect(wrapper.html()).toMatch(/disabled/i)
  })
})
