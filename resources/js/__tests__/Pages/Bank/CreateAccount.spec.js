import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref, reactive } from 'vue'

// Stub the Ziggy `route()` global. Vue's render context resolves bare
// identifiers against `app.config.globalProperties` when they aren't
// setup bindings, so we wire the stub in both places.
const installRouteStub = () => {
  const stub = vi.fn((name) => `/stub/${name}`)
  global.route = stub
  if (typeof window !== 'undefined') window.route = stub
  return stub
}

installRouteStub()

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3')
  return {
    ...actual,
    // Make `errors` reactive so the template re-renders when the test
    // mutates them. The component reads `form.errors.name` etc. directly.
    useForm: (initial) => {
      const errors = reactive({})
      return {
        ...initial,
        errors,
        processing: ref(false),
        post: vi.fn(),
        reset: vi.fn(),
      }
    },
    Head: { name: 'Head', template: '<title><slot /></title>' },
    Link: { name: 'Link', template: '<a><slot /></a>' },
  }
})

import CreateAccount from '@/Pages/Bank/Accounts/Create.vue'

// A layout stub that exposes both the default slot and a `header`
// named slot — the page template pushes the form heading through
// `<template #header>`.
const AuthenticatedLayoutStub = {
  template: `
    <div>
      <header><slot name="header" /></header>
      <main><slot /></main>
    </div>
  `,
}

const mountOptions = (extraProps = {}) => ({
  props: { currency: 'USD', ...extraProps },
  global: {
    stubs: {
      AuthenticatedLayout: AuthenticatedLayoutStub,
    },
    config: {
      globalProperties: {
        route: global.route,
      },
    },
  },
})

describe('Bank/Accounts/Create', () => {
  beforeEach(() => {
    installRouteStub()
  })

  it('renders the form heading', () => {
    const wrapper = mount(CreateAccount, mountOptions())
    expect(wrapper.text()).toContain('New account')
  })

  it('renders bank-specific inputs when type is "bank"', () => {
    const wrapper = mount(CreateAccount, mountOptions())
    // Default type is 'bank' per the page's useForm initial values.
    expect(wrapper.text()).toContain('Bank name')
    expect(wrapper.text()).toContain('Account name')
    expect(wrapper.text()).toContain('Branch')
    expect(wrapper.text()).toContain('Account number')

    const inputs = wrapper.findAll('input.input-bordered')
    // name + bank_name + account_name + branch + account_number + opening_balance
    expect(inputs.length).toBeGreaterThanOrEqual(5)
  })

  it('submits via form.post to bank.accounts.store', async () => {
    const wrapper = mount(CreateAccount, mountOptions())
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    expect(global.route).toHaveBeenCalledWith('bank.accounts.store')
  })

  it('shows an error message when validation fails', async () => {
    const wrapper = mount(CreateAccount, mountOptions())
    const vm = wrapper.vm
    if (vm.form) {
      vm.form.errors.name = 'Name is required'
      vm.form.errors.bank_name = 'Bank name is required'
    }
    await flushPromises()
    expect(wrapper.text()).toContain('Name is required')
    expect(wrapper.text()).toContain('Bank name is required')
  })
})
