import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { formatMoney } from '@/lib/money.js'

export function useCurrency() {
  const page = usePage()
  const settings = computed(() => page.props.settings)
  return { settings, format: (amount) => formatMoney(amount, settings.value) }
}
