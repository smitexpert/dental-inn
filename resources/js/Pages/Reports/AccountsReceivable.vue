<script setup>
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import { useCurrency } from '@/Composables/useCurrency.js'
import { ref, watch } from 'vue'

const props = defineProps({
  as_of: { type: String, required: true },
  rows: { type: Array, required: true },
  buckets: { type: Object, required: true },
  total_outstanding: { type: Number, required: true },
})

const { format } = useCurrency()
const asOf = ref(props.as_of)

let timer = null
watch(asOf, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get('/reports/accounts-receivable', { as_of: asOf.value || undefined }, { preserveState: true, replace: true })
  }, 300)
})
</script>

<template>
  <Head title="Accounts receivable aging" />
  <AuthenticatedLayout>
    <div class="max-w-6xl space-y-6">
      <h1 class="text-2xl font-semibold text-foreground">Accounts receivable aging</h1>

      <UiCard title="As of">
        <div class="flex items-end gap-4">
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">As of</label>
            <UiInput v-model="asOf" type="date" />
          </div>
          <div class="ml-auto text-right">
            <p class="text-xs text-muted-foreground">Total outstanding</p>
            <p class="text-2xl font-semibold tabular-nums">{{ format(props.total_outstanding) }}</p>
          </div>
        </div>
      </UiCard>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <UiCard v-for="(amt, label) in props.buckets" :key="label" :title="label + ' days'">
          <p class="text-lg font-semibold tabular-nums">{{ format(amt) }}</p>
        </UiCard>
      </div>

      <UiCard title="Outstanding invoices">
        <table class="w-full text-sm">
          <thead class="border-b border-border text-left text-muted-foreground">
            <tr>
              <th class="py-2 pr-3">Invoice</th>
              <th class="py-2 pr-3">Patient</th>
              <th class="py-2 pr-3">Issued</th>
              <th class="py-2 pr-3">Due</th>
              <th class="py-2 pr-3">Status</th>
              <th class="py-2 pr-3 text-right">Total</th>
              <th class="py-2 pr-3 text-right">Paid</th>
              <th class="py-2 pr-3 text-right">Balance</th>
              <th class="py-2 pr-3 text-right">Days past due</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in props.rows" :key="row.id" class="border-b border-border/50">
              <td class="py-2 pr-3">
                <a :href="`/billing/invoices/${row.id}`" class="text-primary hover:underline">{{ row.invoice_number }}</a>
              </td>
              <td class="py-2 pr-3">
                <span v-if="row.patient_name">{{ row.patient_name }}</span>
                <span v-else class="text-muted-foreground italic">walk-in</span>
                <span v-if="row.patient_code" class="text-xs text-muted-foreground ml-1">({{ row.patient_code }})</span>
              </td>
              <td class="py-2 pr-3 whitespace-nowrap">{{ row.invoice_date }}</td>
              <td class="py-2 pr-3 whitespace-nowrap">{{ row.due_date }}</td>
              <td class="py-2 pr-3"><UiBadge variant="secondary">{{ row.status }}</UiBadge></td>
              <td class="py-2 pr-3 text-right tabular-nums">{{ format(row.total) }}</td>
              <td class="py-2 pr-3 text-right tabular-nums">{{ format(row.paid_amount) }}</td>
              <td class="py-2 pr-3 text-right tabular-nums font-semibold">{{ format(row.balance) }}</td>
              <td class="py-2 pr-3 text-right tabular-nums">{{ row.days_past_due }}</td>
            </tr>
            <tr v-if="props.rows.length === 0">
              <td colspan="9" class="py-6 text-center text-muted-foreground">No outstanding invoices.</td>
            </tr>
          </tbody>
        </table>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
