import { vi } from 'vitest'

// jsdom does not implement matchMedia; some composables (e.g. useColorMode)
// call it on mount to detect the user's preferred color scheme.
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
