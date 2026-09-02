<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ appointments: Object, doctors: Array, statuses: Array, filters: Object })

const q = ref(props.filters.q ?? '')
const doctorId = ref(props.filters.doctor_id ?? null)
const status = ref(props.filters.status ?? null)
const from = ref(props.filters.from ?? '')
const to = ref(props.filters.to ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/appointments',
      { q: q.value, doctor_id: doctorId.value, status: status.value, from: from.value, to: to.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, doctorId, status, from, to], applyFilters)

const statusLabels = computed(() => {
  const map = {}
  props.statuses.forEach((s) => (map[s.value] = s.label))
  return map
})

function statusVariant(s) {
  return {
    scheduled: 'info',
    confirmed: 'primary',
    checked_in: 'warning',
    completed: 'success',
    cancelled: 'danger',
    no_show: 'danger',
  }[s] || 'neutral'
}

function fmt(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString()
}

function goPage(n) {
  router.get(
    `/appointments?page=${n}`,
    { q: q.value, doctor_id: doctorId.value, status: status.value, from: from.value, to: to.value },
    { preserveState: true },
  )
}

function clearFilters() {
  q.value = ''
  doctorId.value = null
  status.value = null
  from.value = ''
  to.value = ''
}
</script>

<template>
  <Head title="Appointments" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Appointments"
        subtitle="Schedule, view and manage patient appointments."
        icon="calendar-clock"
        eyebrow="Schedule"
      >
        <template #actions>
          <Link href="/appointments/calendar">
            <Button variant="outline" icon="layout-grid">Calendar</Button>
          </Link>
          <Link href="/appointments/create">
            <Button icon="plus">New appointment</Button>
          </Link>
        </template>
      </PageHeader>

      <FilterBar title="Search & filter">
        <template #aside>
          <Badge variant="primary">
            {{ appointments.total }} {{ appointments.total === 1 ? 'appointment' : 'appointments' }}
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
              placeholder="Search patient name, phone or code"
              class="input-shell h-10 pl-9"
            />
          </div>
        </div>
        <select v-model="doctorId" class="input-shell h-10">
          <option :value="null">All doctors</option>
          <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
        <select v-model="status" class="input-shell h-10">
          <option :value="null">All statuses</option>
          <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
        <input
          v-model="from"
          type="date"
          class="input-shell h-10"
          aria-label="From date"
        />
        <input
          v-model="to"
          type="date"
          class="input-shell h-10"
          aria-label="To date"
        />
        <div class="md:col-span-1 flex items-center justify-end">
          <Button variant="ghost" size="sm" icon="x" @click="clearFilters">Clear</Button>
        </div>
      </FilterBar>

      <Card :padded="false">
        <div v-if="appointments.data.length === 0" class="px-5 pb-2">
          <EmptyState
            icon="calendar-clock"
            title="No appointments found"
            description="Try adjusting the filters, or schedule a new appointment."
          >
            <template #action>
              <Link href="/appointments/create">
                <Button icon="plus">New appointment</Button>
              </Link>
            </template>
          </EmptyState>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>When</th>
                <th>Doctor</th>
                <th>Patient</th>
                <th class="w-28">Duration</th>
                <th class="w-36">Status</th>
                <th class="w-32 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in appointments.data" :key="a.id">
                <td>
                  <div class="font-medium">{{ fmt(a.scheduled_at) }}</div>
                </td>
                <td>
                  <div class="inline-flex items-center gap-2">
                    <span
                      class="h-2.5 w-2.5 rounded-full ring-2 ring-base-100"
                      :style="{ backgroundColor: a.doctor_color }"
                    />
                    <span>{{ a.doctor_name }}</span>
                  </div>
                </td>
                <td>
                  <div class="font-medium">{{ a.patient_name }}</div>
                  <div class="text-xs text-(--color-muted) font-mono">{{ a.patient_code }}</div>
                </td>
                <td class="text-(--color-muted)">{{ a.duration_minutes }} min</td>
                <td>
                  <Badge :variant="statusVariant(a.status)" dot>
                    {{ statusLabels[a.status] || a.status }}
                  </Badge>
                </td>
                <td class="text-right">
                  <div class="inline-flex items-center gap-1">
                    <Link
                      :href="`/appointments/${a.id}`"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`View appointment with ${a.patient_name}`"
                      :title="`View appointment with ${a.patient_name}`"
                    >
                      <Icon name="eye" size="sm" />
                    </Link>
                    <Link
                      :href="`/appointments/${a.id}/edit`"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`Edit appointment with ${a.patient_name}`"
                      :title="`Edit appointment with ${a.patient_name}`"
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
            :current-page="appointments.current_page"
            :last-page="appointments.last_page"
            :total="appointments.total"
            :per-page="appointments.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
