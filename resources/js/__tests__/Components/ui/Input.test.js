import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Input from '@/Components/ui/Input.vue'

describe('UiInput', () => {
  it('renders base input classes', () => {
    const wrapper = mount(Input, { props: { modelValue: '' } })
    expect(wrapper.html()).toContain('input')
    expect(wrapper.html()).toContain('input-shell')
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(Input, { props: { modelValue: '' } })
    await wrapper.find('input').setValue('hello')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['hello'])
  })

  it('applies error border when invalid', () => {
    const wrapper = mount(Input, { props: { modelValue: '', invalid: true } })
    expect(wrapper.html()).toContain('input-shell-error')
  })

  it('renders a suffix unit label when one is given', () => {
    const wrapper = mount(Input, { props: { modelValue: '20', suffix: 'Years' } })
    expect(wrapper.text()).toContain('Years')
  })

  it('omits the suffix element when none is given', () => {
    const wrapper = mount(Input, { props: { modelValue: '' } })
    expect(wrapper.text()).not.toContain('Years')
  })

  it('reserves right padding for the suffix', () => {
    const withSuffix = mount(Input, { props: { modelValue: '', suffix: 'Years' } })
    expect(withSuffix.find('input').classes()).toContain('pr-16')

    const withoutSuffix = mount(Input, { props: { modelValue: '' } })
    expect(withoutSuffix.find('input').classes()).not.toContain('pr-16')
  })

  it('reserves left padding only for a prefix icon', () => {
    const prefixed = mount(Input, { props: { modelValue: '', prefixIcon: 'search' } })
    expect(prefixed.find('input').classes()).toContain('pl-9')
  })
})
