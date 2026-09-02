<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import { useCurrentUser } from '@/Composables/useCurrentUser.js'
import { useConfirm } from '@/Composables/useConfirm.js'

const props = defineProps({
  patient: Object,
  record: Object,
})

const record = props.record

const patient = props.patient

const { can } = useCurrentUser()
const confirm = useConfirm()

function fmtDate(s) {
  if (!s) return '—'
  return new Date(s).toLocaleDateString()
}

function recordClass(s) {
  if (s === 'completed') return 'success'
  if (s === 'cancelled') return 'danger'
  return 'warning'
}

function complete() {
  confirm.require({
    header: 'Mark record as completed?',
    message: 'This will close the record and stop the in-progress status.',
    acceptLabel: 'Mark completed',
    acceptClass: 'btn-primary',
    accept: () =>
      router.post(`/patients/${props.patient.id}/treatment-records/${props.record.id}/complete`),
  })
}

function cancel() {
  confirm.require({
    header: 'Cancel this record?',
    message: 'The treatment record will be marked as cancelled.',
    acceptLabel: 'Cancel record',
    acceptClass: 'btn-error',
    accept: () =>
      router.post(`/patients/${props.patient.id}/treatment-records/${props.record.id}/cancel`),
  })
}

/**
 * Normalised teeth list from the stored tooth_chart JSON.
 *   { teeth: [{ fdi, procedures: [{ code, name, surface? }] }] }
 */
const chartTeeth = () => {
  const tc = props.record.tooth_chart
  if (!tc || typeof tc !== 'object') return []
  const list = Array.isArray(tc.teeth) ? tc.teeth : []
  return list
    .filter((t) => t && typeof t.fdi === 'number')
    .slice()
    .sort((a, b) => a.fdi - b.fdi)
}

function procedureLabel(p) {
  if (!p) return ''
  return p.surface ? `${p.code} · ${p.surface}` : p.code
}

const breadcrumbs = [
  { label: 'Patients', href: '/patients' },
  { label: patient.name, href: `/patients/${patient.id}` },
  { label: 'Treatment records', href: `/patients/${patient.id}/treatment-records` },
  { label: fmtDate(record.visited_at) },
]
</script>

<template>
  <Head :title="`Record — ${patient.name}`" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-5xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Record — ${fmtDate(record.visited_at)}`"
        :subtitle="`${patient.name} · ${patient.patient_code}`"
        icon="stethoscope"
        :eyebrow="`Treatment record · ${record.status_label || ''}`"
      >
        <template #actions>
          <Badge :variant="recordClass(record.status)" dot>
            {{ record.status_label }}
          </Badge>
          <Link v-if="can('view-treatment-records')" :href="`/patients/${patient.id}/treatment-records`">
            <Button variant="outline" icon="list">All records</Button>
          </Link>
          <Link
            v-if="can('manage-treatment-records') && record.is_editable"
            :href="`/patients/${patient.id}/treatment-records/${record.id}/edit`"
          >
            <Button variant="outline" icon="edit">Edit</Button>
          </Link>
          <Button
            v-if="can('manage-treatment-records') && record.is_editable"
            icon-right="check"
            @click="complete"
          >
            Mark completed
          </Button>
          <Button
            v-if="can('manage-treatment-records') && record.is_editable"
            variant="danger"
            icon="x"
            @click="cancel"
          >
            Cancel
          </Button>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <Card title="Visit details">
          <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div>
              <dt class="text-muted">Visit date</dt>
              <dd class="font-medium mt-0.5">{{ fmtDate(record.visited_at) }}</dd>
            </div>
            <div>
              <dt class="text-muted">Doctor</dt>
              <dd class="font-medium mt-0.5">
                <span v-if="record.doctor_name" class="inline-flex items-center gap-2">
                  <span
                    class="h-2.5 w-2.5 rounded-full ring-2 ring-base-100"
                    :style="{ backgroundColor: record.doctor_color }"
                  />
                  {{ record.doctor_name }}
                </span>
                <span v-else>—</span>
              </dd>
            </div>
            <div>
              <dt class="text-muted">Status</dt>
              <dd class="mt-0.5">
                <Badge :variant="recordClass(record.status)" dot>
                  {{ record.status_label }}
                </Badge>
              </dd>
            </div>
            <div>
              <dt class="text-muted">Follow-up</dt>
              <dd class="font-medium mt-0.5">{{ fmtDate(record.follow_up_date) }}</dd>
            </div>
            <div v-if="record.appointment_id" class="col-span-2">
              <dt class="text-muted">Linked appointment</dt>
              <dd class="mt-0.5">
                <Link
                  :href="`/appointments/${record.appointment_id}`"
                  class="app-link"
                >
                  #{{ record.appointment_id }}
                </Link>
              </dd>
            </div>
          </dl>
        </Card>

        <Card title="Tooth chart">
          <div
            v-if="chartTeeth().length === 0"
            class="text-sm text-muted py-4 text-center"
          >
            No teeth marked.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="t in chartTeeth()"
              :key="t.fdi"
              class="rounded-lg border border-line bg-base-100 px-3 py-2 text-xs"
            >
              <div class="flex items-center gap-2 mb-1">
                <span class="font-mono font-semibold text-base-content">
                  Tooth {{ t.fdi }}
                </span>
                <Badge variant="primary" size="sm">
                  {{ (t.procedures || []).length }}
                  {{ (t.procedures || []).length === 1 ? 'procedure' : 'procedures' }}
                </Badge>
              </div>
              <ul v-if="(t.procedures || []).length > 0" class="space-y-0.5">
                <li
                  v-for="(p, idx) in t.procedures"
                  :key="idx"
                  class="flex items-center gap-2 text-base-content/80"
                >
                  <span class="font-mono text-base-content/70">{{ p.code }}</span>
                  <span>{{ p.name }}</span>
                  <Badge v-if="p.surface" variant="neutral" size="sm">{{ p.surface }}</Badge>
                </li>
              </ul>
            </div>
          </div>
        </Card>
      </div>

      <Card title="Chief complaint">
        <p class="whitespace-pre-wrap text-sm text-base-content">
          {{ record.chief_complaint || '—' }}
        </p>
      </Card>

      <Card title="Diagnosis">
        <p class="whitespace-pre-wrap text-sm text-base-content">
          {{ record.diagnosis || '—' }}
        </p>
      </Card>

      <Card title="Treatment notes">
        <p class="whitespace-pre-wrap text-sm text-base-content">
          {{ record.treatment_notes || '—' }}
        </p>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
