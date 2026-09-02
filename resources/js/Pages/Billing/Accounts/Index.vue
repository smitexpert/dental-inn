<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Stat from '@/Components/ui/Stat.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ accounts: Object, filters: Object, summary: Object })

const q = ref(props.filters.q ?? '')
const type = ref(props.filters.type ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get('/billing/accounts', { q: q.value, type: type.value }, { preserveState: true, replace: true })
  }, 300)
}
watch([q, type], applyFilters)

function goPage(n) {
  router.get(
    `/billing/accounts?page=${n}`,
    { q: q.value, type: type.value },
    { preserveState: true },
  )
}

function typeVariant(t) {
  const m = { asset: 'success', liability: 'danger', equity: 'info', revenue: 'success', expense: 'warning' }
  return m[t] ?? 'neutral'
}
</script>

<template>
  <Head title="Chart of accounts" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Chart of accounts"
        subtitle="Manage the clinic's accounting ledger accounts, types, and parent hierarchy."
        icon="building"
        eyebrow="Billing"
      >
        <template #actions>
          <Link href="/billing/accounts/create">
            <Button icon="plus">New account</Button>
          </Link>
        </template>
      </PageHeader>

      <div v-if="summary" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Stat
          label="Total accounts"
          :value="summary.total ?? accounts.total"
          icon="building"
          tone="primary"
          hint="Active and inactive"
        />
        <Stat
          label="Assets"
          :value="summary.assets ?? '—'"
          icon="banknote"
          tone="success"
        />
        <Stat
          label="Liabilities"
          :value="summary.liabilities ?? '—'"
          icon="credit-card"
          tone="danger"
        />
        <Stat
          label="Equity"
          :value="summary.equity ?? '—'"
          icon="wallet"
          tone="info"
        />
      </div>

      <FilterBar title="Filters">
        <template #aside>
          <Badge variant="primary">
            {{ accounts.total }} {{ accounts.total === 1 ? 'account' : 'accounts' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-(--color-muted) pointer-events-none">
              <Icon name="search" size="sm" />
            </span>
            <input
              v-model="q"
              type="search"
              placeholder="Search by code or name"
              class="input-shell h-10 pl-9"
            />
          </div>
        </div>
        <select v-model="type" class="input-shell h-10" @change="applyFilters">
          <option value="">All types</option>
          <option value="asset">Asset</option>
          <option value="liability">Liability</option>
          <option value="equity">Equity</option>
          <option value="revenue">Revenue</option>
          <option value="expense">Expense</option>
        </select>
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="accounts.data.length === 0"
          icon="building"
          title="No accounts found"
          description="Create your first account to start building the chart of accounts."
        >
          <template #action>
            <Link href="/billing/accounts/create">
              <Button icon="plus">New account</Button>
            </Link>
          </template>
        </EmptyState>

        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th class="w-32">Status</th>
                <th class="w-20 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in accounts.data" :key="a.id">
                <td>
                  <div class="font-mono text-xs font-medium">{{ a.code }}</div>
                </td>
                <td class="font-medium">{{ a.name }}</td>
                <td>
                  <Badge :variant="typeVariant(a.type)">
                    {{ a.type_label ?? a.type }}
                  </Badge>
                </td>
                <td>
                  <Badge :variant="a.is_active ? 'success' : 'neutral'" dot>
                    {{ a.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>
                <td class="text-right">
                  <div class="inline-flex items-center gap-1">
                    <Link
                      :href="`/billing/accounts/${a.id}`"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`View account ${a.code}`"
                      :title="`View account`"
                    >
                      <Icon name="eye" size="sm" />
                    </Link>
                    <Link
                      :href="`/billing/accounts/${a.id}/edit`"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`Edit account ${a.code}`"
                      :title="`Edit account`"
                    >
                      <Icon name="edit" size="sm" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

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
