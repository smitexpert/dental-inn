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
  suppliers: Object,
  filters: Object,
  kpis: { type: Array, default: () => [] },
})

const q = ref(props.filters.q ?? '')
const isActive = ref(
  props.filters.is_active === '1' ||
  props.filters.is_active === 1 ||
  props.filters.is_active === true
    ? '1'
    : props.filters.is_active === '0' ||
        props.filters.is_active === 0 ||
        props.filters.is_active === false
      ? '0'
      : '',
)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/inventory/suppliers',
      { q: q.value, is_active: isActive.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, isActive], applyFilters)

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Active only', value: '1' },
  { label: 'Inactive only', value: '0' },
]

function goPage(n) {
  router.get(
    `/inventory/suppliers?page=${n}`,
    { q: q.value, is_active: isActive.value },
    { preserveState: true },
  )
}

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'contact_person', label: 'Contact' },
  { key: 'phone', label: 'Phone' },
  { key: 'email', label: 'Email' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Inventory — Suppliers" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Suppliers"
        subtitle="Manage vendor contacts and ordering information."
        icon="truck"
        eyebrow="Inventory"
      >
        <template #actions>
          <Link href="/inventory/suppliers/create">
            <Button icon="plus">New supplier</Button>
          </Link>
        </template>
      </PageHeader>
      <KpiStrip :tiles="kpis" />

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ suppliers.total }} {{ suppliers.total === 1 ? 'supplier' : 'suppliers' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted pointer-events-none">
              <Icon name="search" size="sm" />
            </span>
            <input
              v-model="q"
              type="search"
              placeholder="Search by name, phone, email"
              class="input-shell h-10 pl-9 pr-9"
            />
          </div>
        </div>
        <select v-model="isActive" class="input-shell h-10" @change="applyFilters">
          <option v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="suppliers.data.length === 0"
          icon="send"
          title="No suppliers yet"
          description="Add your first supplier to start tracking cost, lead time and orders."
        >
          <template #action>
            <Link href="/inventory/suppliers/create">
              <Button icon="plus">New supplier</Button>
            </Link>
          </template>
        </EmptyState>

        <ResponsiveTable v-else :columns="columns" :rows="suppliers.data">
          <template #cell:name="{ row }">
            <Link :href="`/inventory/suppliers/${row.id}`" class="font-medium text-base-content hover:text-primary transition-colors">
              {{ row.name }}
            </Link>
          </template>
          <template #cell:contact_person="{ row }">
            <span class="text-muted">{{ row.contact_person || '—' }}</span>
          </template>
          <template #cell:phone="{ row }">
            <span class="text-muted">{{ row.phone || '—' }}</span>
          </template>
          <template #cell:email="{ row }">
            <span class="text-muted">{{ row.email || '—' }}</span>
          </template>
          <template #cell:is_active="{ row }">
            <Badge :variant="row.is_active ? 'success' : 'warning'" dot>
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </Badge>
          </template>
          <template #cell:actions="{ row }">
            <div class="inline-flex items-center gap-1 justify-end">
              <Link :href="`/inventory/suppliers/${row.id}`" class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors" :aria-label="`View ${row.name}`" :title="`View ${row.name}`">
                <Icon name="eye" size="sm" />
              </Link>
              <Link :href="`/inventory/suppliers/${row.id}/edit`" class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors" :aria-label="`Edit ${row.name}`" :title="`Edit ${row.name}`">
                <Icon name="edit" size="sm" />
              </Link>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="font-medium">{{ row.name }}</div>
              <div class="text-sm text-muted">{{ row.contact_person || '—' }}</div>
              <div class="text-sm text-muted">{{ row.phone || '—' }} · {{ row.email || '—' }}</div>
              <div class="flex items-center gap-1">
                <Badge :variant="row.is_active ? 'success' : 'warning'" dot>
                  {{ row.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </div>
            </div>
          </template>
        </ResponsiveTable>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="suppliers.current_page"
            :last-page="suppliers.last_page"
            :total="suppliers.total"
            :per-page="suppliers.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
