import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Textarea from '@/Components/ui/Textarea.vue'
import Select from '@/Components/ui/Select.vue'
import Checkbox from '@/Components/ui/Checkbox.vue'
import Switch from '@/Components/ui/Switch.vue'
import RadioGroup from '@/Components/ui/RadioGroup.vue'

describe('UiTextarea', () => {
  it('renders', () => {
    const wrapper = mount(Textarea, { props: { modelValue: '' } })
    expect(wrapper.find('textarea').exists()).toBe(true)
  })
})

describe('UiSelect', () => {
  it('renders the placeholder', () => {
    const wrapper = mount(Select, { props: { modelValue: null, options: [] } })
    expect(wrapper.html()).toContain('Select…')
  })
})

describe('UiCheckbox', () => {
  it('renders a label when given', () => {
    const wrapper = mount(Checkbox, { props: { modelValue: false, label: 'Remember me' } })
    expect(wrapper.html()).toContain('Remember me')
  })
})

describe('UiSwitch', () => {
  it('renders on and off classes based on value', () => {
    const on = mount(Switch, { props: { modelValue: true } })
    const off = mount(Switch, { props: { modelValue: false } })
    expect(on.html()).toContain('bg-primary-600')
    expect(off.html()).toContain('bg-surface-muted')
  })
})

describe('UiRadioGroup', () => {
  it('renders one option per item', () => {
    const wrapper = mount(RadioGroup, {
      props: {
        modelValue: 'a',
        name: 'r',
        options: [
          { label: 'A', value: 'a' },
          { label: 'B', value: 'b' },
        ],
      },
    })
    expect(wrapper.findAll('label')).toHaveLength(2)
  })
})
