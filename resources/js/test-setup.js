import { vi } from 'vitest'
import { config as vueTestConfig } from '@vue/test-utils'

// jsdom does not implement matchMedia; PrimeVue Select calls it on mount.
if (typeof window !== 'undefined' && !window.matchMedia) {
  window.matchMedia = (query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  })
}

// Stub Inertia's usePage so composables can run in isolation.
vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({ props: { auth: { user: null }, settings: {}, flash: {} } }),
  useForm: (initial) => ({
    data: () => initial,
    errors: {},
    processing: false,
    post: vi.fn(),
    reset: vi.fn(),
  }),
}))

vi.mock('ziggy-js', () => ({
  ZiggyVue: { install: () => {} },
}))

vi.mock('primevue/usetoast', () => ({
  useToast: () => ({ add: vi.fn() }),
}))

vi.mock('primevue/useconfirm', () => ({
  useConfirm: () => ({ require: vi.fn() }),
}))

// Install a stub plugin that populates the global PrimeVue properties components
// read from `this.$primevue`, `this.$toast`, `this.$confirm`. Without these, every
// PrimeVue component throws because their BaseInput/BaseComponent computeds
// dereference `$primevue.config` at render time.
const primevueStubPlugin = {
  install(app) {
    app.config.globalProperties.$primevue = {
      config: { locale: {}, unstyled: true, ripple: false, pt: {} },
    }
    app.config.globalProperties.$toast = { add: vi.fn() }
    app.config.globalProperties.$confirm = { require: vi.fn() }
  },
}
vueTestConfig.global.plugins = vueTestConfig.global.plugins || []
vueTestConfig.global.plugins.push(primevueStubPlugin)
