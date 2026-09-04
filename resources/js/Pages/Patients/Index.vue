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
  patients: Object,
  filters: Object,
  kpis: { type: Array, default: () => [] },
})

const q = ref(props.filters.q ?? '')
const isActive = ref(
  props.filters.is_active === '1' || props.filters.is_active === 1 || props.filters.is_active === true
    ? '1'
    : props.filters.is_active === '0' || props.filters.is_active === 0 || props.filters.is_active === false
      ? '0'
      : '',
)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/patients',
      { q: q.value, is_active: isActive.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, isActive], applyFilters)

function goPage(n) {
  router.get(
    `/patients?page=${n}`,
    { q: q.value, is_active: isActive.value },
    { preserveState: true },
  )
}

const columns = [
  { key: 'patient_code', label: 'Code' },
  { key: 'name', label: 'Name' },
  { key: 'phone', label: 'Phone' },
  { key: 'email', label: 'Email' },
  { key: 'age', label: 'Age' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Patients" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Patients"
        subtitle="Manage patient records, contact details and history."
        icon="users-round"
        eyebrow="Records"
      >
        <template #actions>
          <Link href="/patients/create"><Button icon="plus">New patient</Button></Link>
        </template>
      </PageHeader>
      <KpiStrip :tiles="kpis" />

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ patients.total }} {{ patients.total === 1 ? 'patient' : 'patients' }}
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
              placeholder="Search by name, phone, email, or code"
              class="input-shell h-10 pl-9 pr-9"
            />
          </div>
        </div>
        <select
          v-model="isActive"
          class="input-shell h-10"
          @change="applyFilters"
        >
          <option value="">All patients</option>
          <option value="1">Active only</option>
          <option value="0">Inactive only</option>
        </select>
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="patients.data.length === 0"
          icon="users-round"
          title="No patients found"
          description="Add your first patient to start managing records and appointments."
        >
          <template #action>
            <Link href="/patients/create"><Button icon="plus">New patient</Button></Link>
          </template>
        </EmptyState>

        <ResponsiveTable v-else :columns="columns" :rows="patients.data">
          <template #cell:patient_code="{ row }">
            <span class="text-muted font-mono text-xs">{{ row.patient_code }}</span>
          </template>
          <template #cell:name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
          </template>
          <template #cell:phone="{ row }">
            <span class="text-muted">{{ row.phone || '—' }}</span>
          </template>
          <template #cell:email="{ row }">
            <span class="text-muted">{{ row.email || '—' }}</span>
          </template>
          <template #cell:age="{ row }">
            <span class="text-muted">{{ row.age ?? '—' }}</span>
          </template>
          <template #cell:is_active="{ row }">
            <Badge :variant="row.is_active ? 'success' : 'warning'" dot>
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </Badge>
          </template>
          <template #cell:actions="{ row }">
            <div class="inline-flex items-center gap-1 justify-end">
              <Link
                :href="`/patients/${row.id}`"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`View ${row.name}`"
                :title="`View ${row.name}`"
              >
                <Icon name="eye" size="sm" />
              </Link>
              <Link
                :href="`/patients/${row.id}/edit`"
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
              <div>
                <div class="text-xs font-mono text-muted">{{ row.patient_code }}</div>
                <div class="font-medium">{{ row.name }}</div>
              </div>
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
            :current-page="patients.current_page"
            :last-page="patients.last_page"
            :total="patients.total"
            :per-page="patients.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
