<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
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
  accounts: Object,
  filters: Object,
  currency: String,
  kpis: { type: Array, default: () => [] },
})

const q = ref(props.filters?.q ?? '')
const type = ref(props.filters?.type ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/bank/accounts',
      { q: q.value, type: type.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, type], applyFilters)

const typeOptions = [
  { label: 'All types', value: '' },
  { label: 'Cash', value: 'cash' },
  { label: 'Bank', value: 'bank' },
  { label: 'External', value: 'external' },
]

const statusOptions = [
  { label: 'All accounts', value: '' },
  { label: 'Active only', value: '1' },
  { label: 'Inactive only', value: '0' },
]
const isActive = ref(
  props.filters?.is_active === '1' || props.filters?.is_active === 1
    ? '1'
    : props.filters?.is_active === '0' || props.filters?.is_active === 0
      ? '0'
      : '',
)
watch(isActive, applyFilters)

function goPage(n) {
  router.get(
    `/bank/accounts?page=${n}`,
    { q: q.value, type: type.value, is_active: isActive.value },
    { preserveState: true },
  )
}

const typeIcon = (t) =>
  ({ cash: 'banknote', bank: 'building', external: 'wallet' })[t] || 'banknote'
const typeTone = (t) =>
  ({ cash: 'success', bank: 'primary', external: 'info' })[t] || 'neutral'

const fmt = (n) => Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'type', label: 'Type' },
  { key: 'bank_name', label: 'Bank' },
  { key: 'account_number', label: 'Account #' },
  { key: 'balance', label: 'Balance' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Bank accounts" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Bank accounts"
        subtitle="Manage bank, cash and external accounts."
        icon="building"
        eyebrow="Finance"
      >
        <template #actions>
          <Link :href="route('bank.accounts.create')">
            <Button icon="plus">New account</Button>
          </Link>
        </template>
      </PageHeader>

      <KpiStrip :tiles="kpis" />

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ accounts.total }} {{ accounts.total === 1 ? 'account' : 'accounts' }}
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
              placeholder="Search by name, bank, account number…"
              class="input-shell h-10 pl-9 pr-9"
            />
          </div>
        </div>
        <select v-model="type" class="input-shell h-10" @change="applyFilters">
          <option v-for="o in typeOptions" :key="o.value" :value="o.value">
            {{ o.label }}
          </option>
        </select>
        <select v-model="isActive" class="input-shell h-10" @change="applyFilters">
          <option v-for="o in statusOptions" :key="o.value" :value="o.value">
            {{ o.label }}
          </option>
        </select>
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="accounts.data.length === 0"
          icon="banknote"
          title="No accounts found"
          description="Try adjusting filters or add your first bank or cash account."
        >
          <template #action>
            <Link :href="route('bank.accounts.create')">
              <Button icon="plus">New account</Button>
            </Link>
          </template>
        </EmptyState>

        <ResponsiveTable v-else :columns="columns" :rows="accounts.data">
          <template #cell:name="{ row }">
            <div class="flex items-center gap-2.5">
              <span
                :class="[
                  'h-8 w-8 rounded-lg inline-flex items-center justify-center shrink-0',
                  row.type === 'cash'
                    ? 'bg-success/10 text-success'
                    : row.type === 'bank'
                      ? 'bg-primary/10 text-primary'
                      : 'bg-info/10 text-info',
                ]"
              >
                <Icon :name="typeIcon(row.type)" size="sm" />
              </span>
              <div class="min-w-0">
                <p class="font-medium text-base-content">{{ row.name }}</p>
                <p class="text-xs text-muted">
                  {{ row.account_name || row.branch || '—' }}
                </p>
              </div>
            </div>
          </template>
          <template #cell:type="{ row }">
            <Badge :variant="typeTone(row.type)">{{ row.type }}</Badge>
          </template>
          <template #cell:bank_name="{ row }">
            <span class="text-muted">{{ row.bank_name || '—' }}</span>
          </template>
          <template #cell:account_number="{ row }">
            <span class="text-muted font-mono text-xs">{{ row.account_number || '—' }}</span>
          </template>
          <template #cell:balance="{ row }">
            <div class="text-right font-semibold tabular-nums">
              {{ fmt(row.balance) }} {{ currency }}
            </div>
          </template>
          <template #cell:is_active="{ row }">
            <Badge :variant="row.is_active ? 'success' : 'warning'" dot>
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </Badge>
          </template>
          <template #cell:actions="{ row }">
            <div class="text-right">
              <Link
                :href="route('bank.accounts.edit', row.id)"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`Edit ${row.name}`"
                :title="`Edit ${row.name}`"
              >
                <Icon name="edit" size="sm" />
              </Link>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="flex items-center gap-2.5">
                <span
                  :class="[
                    'h-8 w-8 rounded-lg inline-flex items-center justify-center shrink-0',
                    row.type === 'cash'
                      ? 'bg-success/10 text-success'
                      : row.type === 'bank'
                        ? 'bg-primary/10 text-primary'
                        : 'bg-info/10 text-info',
                  ]"
                >
                  <Icon :name="typeIcon(row.type)" size="sm" />
                </span>
                <div class="min-w-0">
                  <p class="font-medium">{{ row.name }}</p>
                  <p class="text-xs text-muted">{{ row.bank_name || '—' }}</p>
                </div>
              </div>
              <div class="flex items-center justify-between">
                <Badge :variant="row.is_active ? 'success' : 'warning'" dot>
                  {{ row.is_active ? 'Active' : 'Inactive' }}
                </Badge>
                <span class="font-semibold tabular-nums">{{ fmt(row.balance) }} {{ currency }}</span>
              </div>
            </div>
          </template>
        </ResponsiveTable>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="accounts.current_page"
            :last-page="accounts.last_page"
            :total="accounts.total"
            :per-page="accounts.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
