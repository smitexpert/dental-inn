import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Input from '@/Components/ui/Input.vue'

describe('UiInput', () => {
  it('renders base input classes', () => {
    const wrapper = mount(Input, { props: { modelValue: '' } })
    expect(wrapper.html()).toContain('border')
    expect(wrapper.html()).toContain('rounded-md')
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(Input, { props: { modelValue: '' } })
    await wrapper.find('input').setValue('hello')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['hello'])
  })

  it('applies error border when invalid', () => {
    const wrapper = mount(Input, { props: { modelValue: '', invalid: true } })
    expect(wrapper.html()).toContain('border-danger')
  })
})
