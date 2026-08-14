<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiStat from '@/Components/ui/Stat.vue'
import UiBadge from '@/Components/ui/Badge.vue'

const props = defineProps({
  currencies: { type: Array, default: () => [] },
  base_currency: { type: Object, required: true },
  total_in_base: { type: String, default: '0.00' },
})

function formatAmount(value, decimals = 2) {
  const n = Number(value || 0)
  return n.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

function formatInBase(value) {
  return `${formatAmount(value)} ${props.base_currency.code}`
}

function trendTotal(trend) {
  if (!trend) return 0
  return Object.values(trend).reduce((s, v) => s + Number(v), 0)
}
</script>

<template>
  <Head title="Exchange" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-foreground">Exchange</h2>
        <div class="flex gap-2">
          <Link href="/exchange/movements/create"><UiButton>New movement</UiButton></Link>
          <Link href="/exchange/rates"><UiButton variant="secondary">Manage rates</UiButton></Link>
        </div>
      </div>
    </template>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
      <UiStat
        :value="formatAmount(total_in_base)"
        :label="`Total in ${base_currency.code}`"
        hint="Converted at the latest rate"
      />
      <UiStat
        :value="currencies.length"
        label="Active currencies"
        hint="With an open drawer"
      />
      <UiStat
        :value="currencies.filter((c) => c.last_reconciled_at).length"
        label="Reconciled drawers"
        hint="At least once"
      />
    </div>

    <UiCard title="Cash drawers">
      <div v-if="currencies.length === 0" class="py-12 text-center text-muted-foreground">
        No drawers yet. Currencies must be active to have a drawer.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">Currency</th>
              <th class="text-right px-4 py-2">Balance</th>
              <th class="text-right px-4 py-2">In {{ base_currency.code }}</th>
              <th class="text-right px-4 py-2">7-day net</th>
              <th class="text-left px-4 py-2">Last reconciled</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in currencies"
              :key="row.currency_code"
              class="border-b border-border last:border-b-0"
            >
              <td class="px-4 py-2 text-foreground font-medium">
                {{ row.currency.symbol }} {{ row.currency_code }}
                <span class="text-muted-foreground text-xs ml-1">{{ row.currency.name }}</span>
              </td>
              <td class="px-4 py-2 text-right text-foreground">
                {{ row.currency.symbol }}{{ formatAmount(row.balance, row.currency.decimals) }}
              </td>
              <td class="px-4 py-2 text-right text-foreground">
                {{ formatInBase(row.balance_in_base) }}
              </td>
              <td class="px-4 py-2 text-right">
                <UiBadge :variant="trendTotal(row.trend) >= 0 ? 'success' : 'danger'">
                  {{ trendTotal(row.trend) >= 0 ? '+' : '' }}{{ formatAmount(trendTotal(row.trend), row.currency.decimals) }}
                </UiBadge>
              </td>
              <td class="px-4 py-2 text-muted-foreground text-xs">
                <template v-if="row.last_reconciled_at">{{ row.last_reconciled_at }}</template>
                <span v-else>—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
