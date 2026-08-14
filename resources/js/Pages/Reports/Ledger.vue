<script setup>
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import { useCurrency } from '@/Composables/useCurrency.js'
import { ref, watch } from 'vue'

const props = defineProps({
  entries: { type: Object, required: true },
  filters: { type: Object, required: true },
  totals: { type: Object, required: true },
})

const { format } = useCurrency()
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

let timer = null
watch([dateFrom, dateTo], () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get('/reports/ledger', {
      date_from: dateFrom.value || undefined,
      date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true })
  }, 300)
})
</script>

<template>
  <Head title="Ledger report" />
  <AuthenticatedLayout>
    <div class="max-w-7xl space-y-6">
      <h1 class="text-2xl font-semibold text-foreground">Ledger</h1>

      <UiCard title="Filters">
        <div class="flex flex-wrap items-end gap-4">
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">From</label>
            <UiInput v-model="dateFrom" type="date" />
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">To</label>
            <UiInput v-model="dateTo" type="date" />
          </div>
          <div class="ml-auto text-right">
            <p class="text-xs text-muted-foreground">Total debits</p>
            <p class="text-lg font-semibold text-foreground">{{ format(props.totals.debits || 0) }}</p>
          </div>
        </div>
      </UiCard>

      <UiCard title="Entries">
        <table class="w-full text-sm">
          <thead class="border-b border-border text-left text-muted-foreground">
            <tr>
              <th class="py-2 pr-3">Date</th>
              <th class="py-2 pr-3">Description</th>
              <th class="py-2 pr-3">Debit</th>
              <th class="py-2 pr-3">Credit</th>
              <th class="py-2 pr-3 text-right">Amount</th>
              <th class="py-2 pr-3">Reference</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="entry in props.entries.data" :key="entry.id" class="border-b border-border/50">
              <td class="py-2 pr-3 whitespace-nowrap">{{ entry.entry_date }}</td>
              <td class="py-2 pr-3">{{ entry.description }}</td>
              <td class="py-2 pr-3">
                <span class="font-mono text-xs">{{ entry.debit_account?.code }}</span>
                <span class="text-muted-foreground text-xs ml-1">{{ entry.debit_account?.name }}</span>
              </td>
              <td class="py-2 pr-3">
                <span class="font-mono text-xs">{{ entry.credit_account?.code }}</span>
                <span class="text-muted-foreground text-xs ml-1">{{ entry.credit_account?.name }}</span>
              </td>
              <td class="py-2 pr-3 text-right tabular-nums">{{ format(entry.amount) }}</td>
              <td class="py-2 pr-3">
                <UiBadge v-if="entry.reference_type" variant="secondary">
                  {{ entry.reference_type }}#{{ entry.reference_id }}
                </UiBadge>
              </td>
            </tr>
            <tr v-if="props.entries.data.length === 0">
              <td colspan="6" class="py-6 text-center text-muted-foreground">No entries in this period.</td>
            </tr>
          </tbody>
        </table>

        <div v-if="props.entries.links" class="flex justify-between items-center mt-4">
          <UiButton variant="ghost" :disabled="!props.entries.prev_page_url" @click="router.get(props.entries.prev_page_url, {}, { preserveState: true })">
            Previous
          </UiButton>
          <span class="text-xs text-muted-foreground">
            Page {{ props.entries.current_page }} of {{ props.entries.last_page }}
          </span>
          <UiButton variant="ghost" :disabled="!props.entries.next_page_url" @click="router.get(props.entries.next_page_url, {}, { preserveState: true })">
            Next
          </UiButton>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
