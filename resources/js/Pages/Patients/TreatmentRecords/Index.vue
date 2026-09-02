<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Icon from '@/Components/ui/Icon.vue'
import { useCurrentUser } from '@/Composables/useCurrentUser.js'

const props = defineProps({
  patient: Object,
  records: Object,
})

const patient = props.patient

const { can } = useCurrentUser()

function fmtDate(s) {
  if (!s) return '—'
  return new Date(s).toLocaleDateString()
}

function recordClass(s) {
  if (s === 'completed') return 'success'
  if (s === 'cancelled') return 'danger'
  return 'warning'
}

function goPage(n) {
  router.get(`/patients/${patient.id}/treatment-records?page=${n}`, {}, { preserveState: true })
}

const breadcrumbs = [
  { label: 'Patients', href: '/patients' },
  { label: patient.name, href: `/patients/${patient.id}` },
  { label: 'Treatment records' },
]
</script>

<template>
  <Head :title="`Treatment records — ${patient.name}`" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-5xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Treatment records`"
        :subtitle="`${patient.name} · ${patient.patient_code}`"
        icon="stethoscope"
        eyebrow="Clinical history"
      >
        <template #actions>
          <Link :href="`/patients/${patient.id}`">
            <Button variant="outline" icon="arrow-left">Back to profile</Button>
          </Link>
          <Link v-if="can('manage-treatment-records')" :href="`/patients/${patient.id}/treatment-records/create`">
            <Button icon="plus">New treatment record</Button>
          </Link>
        </template>
      </PageHeader>

      <Card :padded="false">
        <div class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th class="w-32">Date</th>
                <th>Chief complaint</th>
                <th>Doctor</th>
                <th class="w-36">Status</th>
                <th class="w-24 text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in records.data" :key="r.id">
                <td class="text-muted">{{ fmtDate(r.visited_at) }}</td>
                <td class="font-medium">{{ r.chief_complaint || '—' }}</td>
                <td>
                  <span v-if="r.doctor_name" class="inline-flex items-center gap-2">
                    <span
                      class="h-2.5 w-2.5 rounded-full ring-2 ring-base-100"
                      :style="{ backgroundColor: r.doctor_color }"
                    />
                    {{ r.doctor_name }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <Badge :variant="recordClass(r.status)" dot>
                    {{ r.status_label }}
                  </Badge>
                </td>
                <td class="text-right">
                  <Link
                    :href="`/patients/${patient.id}/treatment-records/${r.id}`"
                    class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors inline-flex"
                    :aria-label="`View record`"
                    title="View"
                  >
                    <Icon name="eye" size="sm" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>

          <EmptyState
            v-if="records.data.length === 0"
            icon="stethoscope"
            title="No treatment records yet"
            description="Record clinical visits, diagnoses, and treatment plans."
          >
            <template #action>
              <Link v-if="can('manage-treatment-records')" :href="`/patients/${patient.id}/treatment-records/create`">
                <Button icon="plus">New record</Button>
              </Link>
            </template>
          </EmptyState>
        </div>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="records.current_page"
            :last-page="records.last_page"
            :total="records.total"
            :per-page="records.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
