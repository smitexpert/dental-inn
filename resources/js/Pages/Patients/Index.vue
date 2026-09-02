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

const props = defineProps({ patients: Object, filters: Object })

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
        <div class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th class="w-32">Code</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th class="w-16">Age</th>
                <th class="w-40">Status</th>
                <th class="w-32 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in patients.data" :key="p.id">
                <td class="text-muted font-mono text-xs">{{ p.patient_code }}</td>
                <td>
                  <div class="font-medium">{{ p.name }}</div>
                </td>
                <td class="text-muted">{{ p.phone || '—' }}</td>
                <td class="text-muted">{{ p.email || '—' }}</td>
                <td class="text-muted">{{ p.age ?? '—' }}</td>
                <td>
                  <Badge :variant="p.is_active ? 'success' : 'warning'" dot>
                    {{ p.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>
                <td class="text-right">
                  <div class="inline-flex items-center gap-1">
                    <Link
                      :href="`/patients/${p.id}`"
                      class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`View ${p.name}`"
                      :title="`View ${p.name}`"
                    >
                      <Icon name="eye" size="sm" />
                    </Link>
                    <Link
                      :href="`/patients/${p.id}/edit`"
                      class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`Edit ${p.name}`"
                      :title="`Edit ${p.name}`"
                    >
                      <Icon name="edit" size="sm" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

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
        </div>

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
