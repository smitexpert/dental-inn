<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import { ref, computed } from 'vue'

const props = defineProps({
  drawers: { type: Array, default: () => [] },
  currencies: { type: Array, default: () => [] },
})

const page = usePage()
const errors = computed(() => page.props.errors ?? {})

const selectedCode = ref(props.drawers[0]?.currency_code ?? '')
const counted = ref('')

const selected = computed(() => props.drawers.find((d) => d.currency_code === selectedCode.value))

function selectCurrency(code) {
  selectedCode.value = code
  counted.value = ''
}

function submit() {
  router.post('/exchange/reconcile/run', {
    currency_code: selectedCode.value,
    counted_balance: counted.value,
  }, { preserveScroll: true })
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}

function variance() {
  if (!selected.value || counted.value === '') return null
  const diff = Number(counted.value) - Number(selected.value.balance)
  return Number.isFinite(diff) ? diff : null
}
</script>

<template>
  <Head title="Reconcile cash drawer" />
  <AuthenticatedLayout>
    <UiCard title="Reconcile drawer">
      <template #header>
        <h3 class="text-base font-semibold text-base-content">Reconcile cash drawer</h3>
      </template>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <UiSelect
          v-model="selectedCode"
          label="Drawer"
          :options="drawers.map((d) => ({ label: `${d.currency.code ?? d.currency_code} – expected ${d.balance}`, value: d.currency_code }))"
          @update:model-value="selectCurrency"
        />
        <UiInput
          v-model="counted"
          label="Counted balance"
          type="number"
          step="0.0001"
          :error="errors.counted_balance"
        />
      </div>

      <div v-if="selected" class="bg-base-200 rounded-md p-4 mb-4">
        <div class="grid grid-cols-3 gap-3 text-sm">
          <div>
            <div class="text-base-content/70 text-xs">Expected</div>
            <div class="text-base-content font-medium">{{ selected.balance }}</div>
          </div>
          <div>
            <div class="text-base-content/70 text-xs">Last reconciled</div>
            <div class="text-base-content">{{ formatDate(selected.last_reconciled_at) }}</div>
          </div>
          <div>
            <div class="text-base-content/70 text-xs">Variance</div>
            <div>
              <UiBadge v-if="variance() === null" variant="neutral">—</UiBadge>
              <UiBadge v-else :variant="(variance() ?? 0) === 0 ? 'success' : ((variance() ?? 0) > 0 ? 'success' : 'danger')">
                {{ (variance() ?? 0) > 0 ? '+' : '' }}{{ variance() }}
              </UiBadge>
            </div>
          </div>
        </div>
      </div>

      <div class="flex gap-2 justify-end">
        <Link href="/exchange"><UiButton variant="secondary">Cancel</UiButton></Link>
        <UiButton :disabled="!selected || counted === ''" @click="submit">Reconcile</UiButton>
      </div>
    </UiCard>

    <div class="mt-4">
      <UiCard title="All drawers">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-base-200 text-base-content/70">
              <tr>
                <th class="text-left px-4 py-2">Currency</th>
                <th class="text-right px-4 py-2">Balance</th>
                <th class="text-left px-4 py-2">Last reconciled</th>
                <th class="text-right px-4 py-2">Last reconciled balance</th>
                <th class="text-right px-4 py-2">Movements</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in drawers" :key="d.currency_code" class="border-b border-base-300 last:border-b-0">
                <td class="px-4 py-2 text-base-content font-mono text-xs">{{ d.currency?.symbol }} {{ d.currency_code }}</td>
                <td class="px-4 py-2 text-right text-base-content">{{ d.balance }}</td>
                <td class="px-4 py-2 text-base-content/70 text-xs">{{ formatDate(d.last_reconciled_at) }}</td>
                <td class="px-4 py-2 text-right text-base-content">{{ d.last_reconciled_balance ?? '—' }}</td>
                <td class="px-4 py-2 text-right text-base-content/70">{{ d.recent_movements }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
