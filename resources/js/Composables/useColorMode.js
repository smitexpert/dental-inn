import { ref, watch, onMounted } from 'vue'

const STORAGE_KEY = 'dental-color-mode'

const mode = ref('light')
let initialized = false

function apply(mode) {
  if (typeof document === 'undefined') return
  if (mode === 'dark') document.documentElement.dataset.theme = 'dark'
  else if (mode === 'light') document.documentElement.dataset.theme = 'light'
  else document.documentElement.dataset.theme = ''
}

export function useColorMode() {
  onMounted(() => {
    if (initialized) return
    initialized = true
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored === 'dark' || stored === 'light') {
      mode.value = stored
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
      mode.value = 'dark'
    }
    apply(mode.value)
  })

  watch(mode, (m) => {
    localStorage.setItem(STORAGE_KEY, m)
    apply(m)
  })

  function toggle() {
    mode.value = mode.value === 'dark' ? 'light' : 'dark'
  }

  return { mode, toggle }
}
