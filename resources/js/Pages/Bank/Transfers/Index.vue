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
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ transfers: Object, accounts: Array, filters: Object, currency: String })

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
</script>

<template>
  <Head title="Transfers" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Transfers"
        subtitle="Move funds between bank, cash and external accounts."
        icon="arrow-right"
        eyebrow="Finance"
      >
        <template #actions>
          <Link :href="route('bank.transfers.create')">
            <Button icon="plus">New transfer</Button>
          </Link>
        </template>
      </PageHeader>

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ transfers.total }} {{ transfers.total === 1 ? 'transfer' : 'transfers' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <div class="relative">
            <span
              class="absolute left-3 top-1/2 -translate-y-1/2 text-(--color-muted) pointer-events-none"
            >
              <Icon name="search" size="sm" />
            </span>
            <input
              v-model="q"
              type="search"
              placeholder="Search reference or note…"
              class="input-shell h-10 pl-9"
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
        <div class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>From</th>
                <th></th>
                <th>To</th>
                <th class="text-right">Amount</th>
                <th>Reference</th>
                <th class="w-20 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in transfers.data" :key="t.id">
                <td class="text-(--color-muted)">{{ t.occurred_at }}</td>
                <td>
                  <div class="flex items-center gap-2.5">
                    <span
                      class="h-8 w-8 rounded-lg bg-error/10 text-error inline-flex items-center justify-center shrink-0"
                    >
                      <Icon name="arrow-up" size="sm" />
                    </span>
                    <div class="min-w-0">
                      <p class="font-medium text-base-content">
                        {{ t.from_account?.name || '—' }}
                      </p>
                      <p v-if="t.from_account?.bank_name" class="text-xs text-(--color-muted)">
                        {{ t.from_account.bank_name }}
                      </p>
                    </div>
                  </div>
                </td>
                <td>
                  <Icon name="arrow-right" size="sm" class="text-(--color-muted)" />
                </td>
                <td>
                  <div class="flex items-center gap-2.5">
                    <span
                      class="h-8 w-8 rounded-lg bg-success/10 text-success inline-flex items-center justify-center shrink-0"
                    >
                      <Icon name="arrow-down" size="sm" />
                    </span>
                    <div class="min-w-0">
                      <p class="font-medium text-base-content">
                        {{ t.to_account?.name || '—' }}
                      </p>
                      <p v-if="t.to_account?.bank_name" class="text-xs text-(--color-muted)">
                        {{ t.to_account.bank_name }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="text-right font-semibold tabular-nums">
                  {{ fmt(t.amount) }} {{ currency }}
                </td>
                <td class="text-(--color-muted)">{{ t.reference || '—' }}</td>
                <td class="text-right">
                  <Link
                    :href="route('bank.transfers.show', t.id)"
                    class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors"
                    :aria-label="`View transfer ${t.id}`"
                    :title="`View transfer ${t.id}`"
                  >
                    <Icon name="eye" size="sm" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>

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
        </div>

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
