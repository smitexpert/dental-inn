import { vi } from 'vitest'

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
