import { ref, watch, onMounted } from 'vue'

const STORAGE_KEY = 'dental-color-mode'

// DaisyUI theme names. `corporate` is the light theme; `business` is the dark
// theme (configured in resources/css/app.css).
const LIGHT = 'corporate'
const DARK = 'business'

const mode = ref(LIGHT)
let initialized = false

function apply(m) {
  if (typeof document === 'undefined') return
  document.documentElement.dataset.theme = m === DARK ? DARK : LIGHT
}

export function useColorMode() {
  onMounted(() => {
    if (initialized) return
    initialized = true
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored === DARK || stored === LIGHT) {
      mode.value = stored
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
      mode.value = DARK
    }
    apply(mode.value)
  })

  watch(mode, (m) => {
    localStorage.setItem(STORAGE_KEY, m)
    apply(m)
  })

  function toggle() {
    mode.value = mode.value === DARK ? LIGHT : DARK
  }

  return { mode, toggle }
}
