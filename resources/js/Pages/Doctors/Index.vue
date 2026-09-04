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
  doctors: Object,
  specializations: Array,
  filters: Object,
  kpis: { type: Array, default: () => [] },
})

const q = ref(props.filters.q ?? '')
const specialization = ref(props.filters.specialization ?? null)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/doctors',
      { q: q.value, specialization: specialization.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, specialization], applyFilters)

function toggleAvailable(doctor) {
  router.post(`/doctors/${doctor.id}/toggle-availability`)
}

function goPage(n) {
  router.get(
    `/doctors?page=${n}`,
    { q: q.value, specialization: specialization.value },
    { preserveState: true },
  )
}

const columns = [
  { key: 'doctor', label: 'Doctor' },
  { key: 'specialization', label: 'Specialization' },
  { key: 'license_number', label: 'License' },
  { key: 'consultation_fee', label: 'Fee' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Doctors" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Doctors"
        subtitle="Manage the clinical team, schedules and availability."
        icon="stethoscope"
        eyebrow="Team"
      >
        <template #actions>
          <Link href="/doctors/create"><Button icon="plus">New doctor</Button></Link>
        </template>
      </PageHeader>
      <KpiStrip :tiles="kpis" />

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ doctors.total }} {{ doctors.total === 1 ? 'doctor' : 'doctors' }}
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
              placeholder="Search by name, email, or specialization"
              class="input-shell h-10 pl-9 pr-9"
            />
          </div>
        </div>
        <select
          v-model="specialization"
          class="input-shell h-10"
          @change="applyFilters"
        >
          <option :value="null">All specializations</option>
          <option v-for="s in specializations" :key="s" :value="s">{{ s }}</option>
        </select>
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="doctors.data.length === 0"
          icon="user-round"
          title="No doctors found"
          description="Add your first doctor to start managing schedules and appointments."
        >
          <template #action>
            <Link href="/doctors/create"><Button icon="plus">New doctor</Button></Link>
          </template>
        </EmptyState>

        <ResponsiveTable v-else :columns="columns" :rows="doctors.data">
          <template #cell:doctor="{ row }">
            <div class="flex items-center gap-2.5">
              <span
                class="h-8 w-8 rounded-full inline-flex items-center justify-center text-xs font-semibold shrink-0 ring-2 ring-base-100"
                :style="{ backgroundColor: row.color, color: '#fff' }"
              >
                {{ row.name?.split(' ').map((s) => s[0]).slice(0, 2).join('').toUpperCase() }}
              </span>
              <div class="min-w-0">
                <div class="font-medium">{{ row.name }}</div>
                <div class="text-xs text-muted">{{ row.email }}</div>
              </div>
            </div>
          </template>
          <template #cell:specialization="{ row }">
            <span class="text-muted">{{ row.specialization || '—' }}</span>
          </template>
          <template #cell:license_number="{ row }">
            <span class="text-muted font-mono text-xs">{{ row.license_number || '—' }}</span>
          </template>
          <template #cell:consultation_fee="{ row }">
            <span class="text-muted">
              {{ row.consultation_fee != null ? '$' + Number(row.consultation_fee).toFixed(2) : '—' }}
            </span>
          </template>
          <template #cell:is_active="{ row }">
            <Badge :variant="row.is_active ? 'success' : 'warning'" dot>
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </Badge>
          </template>
          <template #cell:actions="{ row }">
            <div class="inline-flex items-center gap-1 justify-end">
              <Link
                :href="`/doctors/${row.id}`"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`View ${row.name}`"
                :title="`View ${row.name}`"
              >
                <Icon name="eye" size="sm" />
              </Link>
              <Link
                :href="`/doctors/${row.id}/edit`"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`Edit ${row.name}`"
                :title="`Edit ${row.name}`"
              >
                <Icon name="edit" size="sm" />
              </Link>
              <button
                type="button"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`Toggle availability for ${row.name}`"
                :title="`Toggle availability for ${row.name}`"
                @click="toggleAvailable(row)"
              >
                <Icon :name="row.is_available ? 'user-x' : 'user-check'" size="sm" />
              </button>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="flex items-center gap-2.5">
                <span
                  class="h-8 w-8 rounded-full inline-flex items-center justify-center text-xs font-semibold shrink-0 ring-2 ring-base-100"
                  :style="{ backgroundColor: row.color, color: '#fff' }"
                >
                  {{ row.name?.split(' ').map((s) => s[0]).slice(0, 2).join('').toUpperCase() }}
                </span>
                <div class="min-w-0">
                  <div class="font-medium">{{ row.name }}</div>
                  <div class="text-xs text-muted">{{ row.email }}</div>
                </div>
              </div>
              <div class="text-sm text-muted">{{ row.specialization || '—' }}</div>
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
            :current-page="doctors.current_page"
            :last-page="doctors.last_page"
            :total="doctors.total"
            :per-page="doctors.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
