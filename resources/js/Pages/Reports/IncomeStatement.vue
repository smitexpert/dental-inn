<script setup>
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiInput from '@/Components/ui/Input.vue'
import { useCurrency } from '@/Composables/useCurrency.js'
import { ref, watch } from 'vue'

const props = defineProps({
  period: { type: Object, required: true },
  revenue: { type: Number, required: true },
  expense: { type: Number, required: true },
  net: { type: Number, required: true },
  revenue_accounts: { type: Array, required: true },
  expense_accounts: { type: Array, required: true },
})

const { format } = useCurrency()
const dateFrom = ref(props.period.from ?? '')
const dateTo = ref(props.period.to ?? '')

let timer = null
watch([dateFrom, dateTo], () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get('/reports/income-statement', {
      date_from: dateFrom.value || undefined,
      date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true })
  }, 300)
})
</script>

<template>
  <Head title="Income statement" />
  <AuthenticatedLayout>
    <div class="max-w-5xl space-y-6">
      <h1 class="text-2xl font-semibold text-foreground">Income statement</h1>

      <UiCard title="Period">
        <div class="flex flex-wrap items-end gap-4">
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">From</label>
            <UiInput v-model="dateFrom" type="date" />
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">To</label>
            <UiInput v-model="dateTo" type="date" />
          </div>
        </div>
      </UiCard>

      <UiCard title="Revenue">
        <table class="w-full text-sm">
          <tbody>
            <tr v-for="acc in props.revenue_accounts" :key="acc.id" class="border-b border-border/40">
              <td class="py-2">
                <span class="font-mono text-xs mr-2">{{ acc.code }}</span>
                {{ acc.name }}
              </td>
              <td class="py-2 text-right tabular-nums">{{ format(acc.balance) }}</td>
            </tr>
            <tr class="font-semibold border-t border-border">
              <td class="py-2">Total revenue</td>
              <td class="py-2 text-right tabular-nums">{{ format(props.revenue) }}</td>
            </tr>
          </tbody>
        </table>
      </UiCard>

      <UiCard title="Expenses">
        <table class="w-full text-sm">
          <tbody>
            <tr v-for="acc in props.expense_accounts" :key="acc.id" class="border-b border-border/40">
              <td class="py-2">
                <span class="font-mono text-xs mr-2">{{ acc.code }}</span>
                {{ acc.name }}
              </td>
              <td class="py-2 text-right tabular-nums">{{ format(acc.balance) }}</td>
            </tr>
            <tr class="font-semibold border-t border-border">
              <td class="py-2">Total expenses</td>
              <td class="py-2 text-right tabular-nums">{{ format(props.expense) }}</td>
            </tr>
          </tbody>
        </table>
      </UiCard>

      <UiCard title="Net">
        <div class="flex items-baseline justify-between">
          <span class="text-sm text-muted-foreground">Net income</span>
          <span class="text-2xl font-semibold tabular-nums" :class="props.net >= 0 ? 'text-success' : 'text-danger'">
            {{ format(props.net) }}
          </span>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
