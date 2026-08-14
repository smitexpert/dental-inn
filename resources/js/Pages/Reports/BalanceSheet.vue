<script setup>
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiInput from '@/Components/ui/Input.vue'
import { useCurrency } from '@/Composables/useCurrency.js'
import { ref, watch } from 'vue'

const props = defineProps({
  as_of: { type: String, required: true },
  assets: { type: Array, required: true },
  liabilities: { type: Array, required: true },
  equity: { type: Array, required: true },
  totals: { type: Object, required: true },
})

const { format } = useCurrency()
const asOf = ref(props.as_of)

let timer = null
watch(asOf, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get('/reports/balance-sheet', { as_of: asOf.value || undefined }, { preserveState: true, replace: true })
  }, 300)
})

function tableFor(label, accounts, total) {
  return { label, accounts, total }
}
</script>

<template>
  <Head title="Balance sheet" />
  <AuthenticatedLayout>
    <div class="max-w-5xl space-y-6">
      <h1 class="text-2xl font-semibold text-foreground">Balance sheet</h1>

      <UiCard title="As of">
        <div class="flex items-end gap-4">
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">As of</label>
            <UiInput v-model="asOf" type="date" />
          </div>
        </div>
      </UiCard>

      <UiCard v-for="section in [tableFor('Assets', props.assets, props.totals.assets), tableFor('Liabilities', props.liabilities, props.totals.liabilities), tableFor('Equity', props.equity, props.totals.equity)]" :key="section.label" :title="section.label">
        <table class="w-full text-sm">
          <tbody>
            <tr v-for="acc in section.accounts" :key="acc.id" class="border-b border-border/40">
              <td class="py-2">
                <span class="font-mono text-xs mr-2">{{ acc.code }}</span>
                {{ acc.name }}
              </td>
              <td class="py-2 text-right tabular-nums">{{ format(acc.balance) }}</td>
            </tr>
            <tr class="font-semibold border-t border-border">
              <td class="py-2">Total {{ section.label.toLowerCase() }}</td>
              <td class="py-2 text-right tabular-nums">{{ format(section.total) }}</td>
            </tr>
          </tbody>
        </table>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
