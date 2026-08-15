import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref, reactive } from 'vue'

// Stub the Ziggy `route()` global. Vue's render context resolves bare
// identifiers against `app.config.globalProperties` when they aren't
// setup bindings, so we wire the stub in both places.
const installRouteStub = () => {
  const stub = vi.fn((name) => `/stub/${name}`)
  globalThis.route = stub
  if (typeof window !== 'undefined') window.route = stub
  return stub
}

installRouteStub()

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3')
  return {
    ...actual,
    // Make `errors` reactive so the template re-renders when the test
    // mutates them. The component reads `form.errors.from_account_id`
    // etc. directly.
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

import CreateTransfer from '@/Pages/Bank/Transfers/Create.vue'

const accountsFixture = [
  { id: 1, name: 'Main checking', balance: 1200.5 },
  { id: 2, name: 'Cash drawer', balance: 300 },
  { id: 3, name: 'Savings', balance: 9800 },
]

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
  props: { accounts: accountsFixture, currency: 'USD', ...extraProps },
  global: {
    stubs: {
      AuthenticatedLayout: AuthenticatedLayoutStub,
    },
    config: {
      globalProperties: {
        route: globalThis.route,
      },
    },
  },
})

describe('Bank/Transfers/Create', () => {
  beforeEach(() => {
    installRouteStub()
  })

  it('renders the transfer form', () => {
    const wrapper = mount(CreateTransfer, mountOptions())
    expect(wrapper.text()).toContain('New transfer')
    expect(wrapper.text()).toContain('From')
    expect(wrapper.text()).toContain('To')
    expect(wrapper.text()).toContain('Amount')
  })

  it('lists accounts in the from and to selects', () => {
    const wrapper = mount(CreateTransfer, mountOptions())
    const selects = wrapper.findAll('select.select-bordered')
    expect(selects.length).toBe(2)

    const fromOptions = selects[0].findAll('option')
    const toOptions = selects[1].findAll('option')

    expect(fromOptions.length).toBe(accountsFixture.length)
    expect(toOptions.length).toBe(accountsFixture.length)

    // Every account should be present in both dropdowns.
    for (const a of accountsFixture) {
      expect(fromOptions.some((o) => o.attributes('value') === String(a.id))).toBe(true)
      expect(toOptions.some((o) => o.attributes('value') === String(a.id))).toBe(true)
    }
  })

  it('submits via form.post to bank.transfers.store', async () => {
    const wrapper = mount(CreateTransfer, mountOptions())
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    expect(globalThis.route).toHaveBeenCalledWith('bank.transfers.store')
  })

  it('shows an error message when validation fails', async () => {
    const wrapper = mount(CreateTransfer, mountOptions())
    const vm = wrapper.vm
    if (vm.form) {
      vm.form.errors.from_account_id = 'Choose a source account'
      vm.form.errors.amount = 'Amount must be greater than zero'
    }
    await flushPromises()
    expect(wrapper.text()).toContain('Choose a source account')
    expect(wrapper.text()).toContain('Amount must be greater than zero')
  })
})
