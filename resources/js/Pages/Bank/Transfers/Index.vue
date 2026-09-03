<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeaderSticky from '@/Components/ui/PageHeaderSticky.vue'
import KpiStrip from '@/Components/ui/KpiStrip.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import ResponsiveTable from '@/Components/ui/ResponsiveTable.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({
  transfers: Object,
  accounts: Array,
  filters: Object,
  currency: String,
  kpis: { type: Array, default: () => [] },
})

const dateFrom = ref(props.filters?.date_from ?? '')
const dateTo = ref(props.filters?.date_to ?? '')
const q = ref(props.filters?.q ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/bank/transfers',
      { date_from: dateFrom.value, date_to: dateTo.value, q: q.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([dateFrom, dateTo, q], applyFilters)

function goPage(n) {
  router.get(
    `/bank/transfers?page=${n}`,
    { date_from: dateFrom.value, date_to: dateTo.value, q: q.value },
    { preserveState: true },
  )
}

function clearFilters() {
  dateFrom.value = ''
  dateTo.value = ''
  q.value = ''
  router.get('/bank/transfers', {}, { preserveState: true })
}

const fmt = (n) => Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })

const columns = [
  { key: 'occurred_at', label: 'Date' },
  { key: 'from_account', label: 'From' },
  { key: 'to_account', label: 'To' },
  { key: 'amount', label: 'Amount' },
  { key: 'reference', label: 'Reference' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Transfers" />
  <AuthenticatedLayout>
    <template #pageHeader>
      <PageHeaderSticky
        title="Transfers"
        :breadcrumb="[{ label: 'Finance' }, { label: 'Transfers' }]"
        meta="Move funds between bank, cash and external accounts."
      >
        <template #actions>
          <Link :href="route('bank.transfers.create')">
            <Button icon="plus">New transfer</Button>
          </Link>
        </template>
      </PageHeaderSticky>
    </template>

    <div class="space-y-5 pt-5">
      <KpiStrip :tiles="kpis" />

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ transfers.total }} {{ transfers.total === 1 ? 'transfer' : 'transfers' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <div class="relative">
            <span
              class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted pointer-events-none"
            >
              <Icon name="search" size="sm" />
            </span>
            <input
              v-model="q"
              type="search"
              placeholder="Search reference or note…"
              class="input-shell h-10 pl-9 pr-9"
            />
          </div>
        </div>
        <input v-model="dateFrom" type="date" class="input-shell h-10" />
        <input v-model="dateTo" type="date" class="input-shell h-10" />
        <div class="md:col-span-4 flex justify-end">
          <Button variant="ghost" icon="x" @click="clearFilters">Clear</Button>
        </div>
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="transfers.data.length === 0"
          icon="arrow-right"
          title="No transfers yet"
          description="Record your first inter-account transfer to track fund movements."
        >
          <template #action>
            <Link :href="route('bank.transfers.create')">
              <Button icon="plus">New transfer</Button>
            </Link>
          </template>
        </EmptyState>

        <ResponsiveTable v-else :columns="columns" :rows="transfers.data">
          <template #cell:occurred_at="{ row }">
            <span class="text-muted">{{ row.occurred_at }}</span>
          </template>
          <template #cell:from_account="{ row }">
            <div class="flex items-center gap-2.5">
              <span
                class="h-8 w-8 rounded-lg bg-error/10 text-error inline-flex items-center justify-center shrink-0"
              >
                <Icon name="arrow-up" size="sm" />
              </span>
              <div class="min-w-0">
                <p class="font-medium text-base-content">
                  {{ row.from_account?.name || '—' }}
                </p>
                <p v-if="row.from_account?.bank_name" class="text-xs text-muted">
                  {{ row.from_account.bank_name }}
                </p>
              </div>
            </div>
          </template>
          <template #cell:to_account="{ row }">
            <div class="flex items-center gap-2.5">
              <span
                class="h-8 w-8 rounded-lg bg-success/10 text-success inline-flex items-center justify-center shrink-0"
              >
                <Icon name="arrow-down" size="sm" />
              </span>
              <div class="min-w-0">
                <p class="font-medium text-base-content">
                  {{ row.to_account?.name || '—' }}
                </p>
                <p v-if="row.to_account?.bank_name" class="text-xs text-muted">
                  {{ row.to_account.bank_name }}
                </p>
              </div>
            </div>
          </template>
          <template #cell:amount="{ row }">
            <div class="text-right font-semibold tabular-nums">
              {{ fmt(row.amount) }} {{ currency }}
            </div>
          </template>
          <template #cell:reference="{ row }">
            <span class="text-muted">{{ row.reference || '—' }}</span>
          </template>
          <template #cell:actions="{ row }">
            <div class="text-right">
              <Link
                :href="route('bank.transfers.show', row.id)"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`View transfer ${row.id}`"
                :title="`View transfer ${row.id}`"
              >
                <Icon name="eye" size="sm" />
              </Link>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="text-sm text-muted">{{ row.occurred_at }}</div>
              <div class="flex items-center gap-2">
                <span class="text-sm">{{ row.from_account?.name || '—' }}</span>
                <Icon name="arrow-right" size="sm" class="text-muted" />
                <span class="text-sm">{{ row.to_account?.name || '—' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-muted">{{ row.reference || '—' }}</span>
                <span class="font-semibold tabular-nums">{{ fmt(row.amount) }} {{ currency }}</span>
              </div>
            </div>
          </template>
        </ResponsiveTable>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="transfers.current_page"
            :last-page="transfers.last_page"
            :total="transfers.total"
            :per-page="transfers.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
