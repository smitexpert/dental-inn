<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import { useCurrentUser } from '@/Composables/useCurrentUser.js'

const props = defineProps({
  patient: Object,
  prescriptions: Object,
})

const { can } = useCurrentUser()

function statusVariant(s) {
  return {
    draft: 'warning',
    issued: 'success',
    cancelled: 'danger',
  }[s] || 'neutral'
}

function fmt(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString()
}

const breadcrumbs = [
  { label: 'Patients', href: '/patients' },
  { label: props.patient.name, href: `/patients/${props.patient.id}` },
  { label: 'Prescriptions' },
]
</script>

<template>
  <Head :title="`Prescriptions — ${patient.name}`" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-5xl my-4">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="Prescriptions"
        :subtitle="`${patient.name} · ${patient.patient_code}`"
        icon="pill"
        :eyebrow="`${prescriptions.total} ${prescriptions.total === 1 ? 'prescription' : 'prescriptions'}`"
      >
        <template #actions>
          <Link v-if="can('manage-prescriptions')" :href="`/patients/${patient.id}/prescriptions/create`">
            <Button icon="plus">New prescription</Button>
          </Link>
        </template>
      </PageHeader>

      <Card :padded="false">
        <div v-if="prescriptions.data.length === 0" class="p-6">
          <EmptyState
            icon="pill"
            title="No prescriptions yet"
            description="Create the first prescription for this patient."
          />
        </div>
        <div v-else class="divide-y divide-(--color-line)">
          <div
            v-for="p in prescriptions.data"
            :key="p.id"
            class="p-4 flex items-center gap-3"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <Link
                  :href="`/patients/${patient.id}/prescriptions/${p.id}`"
                  class="font-mono font-medium hover:text-primary"
                >
                  {{ p.prescription_number }}
                </Link>
                <Badge :variant="statusVariant(p.status)" dot>{{ p.status_label }}</Badge>
              </div>
              <div class="text-xs text-muted">
                <span v-if="p.doctor_name">Dr. {{ p.doctor_name }}</span>
                <span v-else>Unassigned doctor</span>
                · Issued {{ fmt(p.issued_at) }}
                <span v-if="p.appointment_id">
                  ·
                  <Link :href="`/appointments/${p.appointment_id}`" class="hover:underline">
                    Appointment #{{ p.appointment_id }}
                  </Link>
                </span>
              </div>
            </div>
            <Link :href="`/patients/${patient.id}/prescriptions/${p.id}`">
              <Button variant="outline" size="sm" icon="eye">View</Button>
            </Link>
            <Link :href="`/patients/${patient.id}/prescriptions/${p.id}/print`">
              <Button variant="outline" size="sm" icon="printer">Print</Button>
            </Link>
          </div>
        </div>
      </Card>

      <Pagination :page="prescriptions" />
    </div>
  </AuthenticatedLayout>
</template>