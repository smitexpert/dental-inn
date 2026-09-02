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
  // Stub <Link> as a plain <a> so components that use it can render in
  // isolation without pulling the full Inertia runtime.
  Link: {
    name: 'Link',
    props: ['href', 'method', 'as', 'data'],
    template: '<a :href="href"><slot /></a>',
  },
  Head: { name: 'Head', template: '<template><slot /></template>' },
}))

vi.mock('ziggy-js', () => ({
  ZiggyVue: { install: () => {} },
}))
