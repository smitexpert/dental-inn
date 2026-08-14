<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'
import { useCurrentUser } from '@/Composables/useCurrentUser.js'

const props = defineProps({
  patient: Object,
  record: Object,
})

const { can } = useCurrentUser()

function fmtDate(s) {
  if (!s) return '—'
  return new Date(s).toLocaleDateString()
}

function complete() {
  if (confirm('Mark this record as completed?')) {
    router.post(`/patients/${props.patient.id}/treatment-records/${props.record.id}/complete`)
  }
}

function cancel() {
  if (confirm('Cancel this treatment record?')) {
    router.post(`/patients/${props.patient.id}/treatment-records/${props.record.id}/cancel`)
  }
}

const toothEntries = () => {
  const tc = props.record.tooth_chart
  if (!tc || typeof tc !== 'object') return []
  return Object.entries(tc)
}
</script>

<template>
  <Head :title="`Record — ${patient.name}`" />
  <AuthenticatedLayout>
    <div class="space-y-4 max-w-4xl">
      <div class="flex items-center justify-between">
        <div>
          <Link :href="`/patients/${patient.id}`" class="text-sm text-base-content/70 hover:text-primary">
            {{ patient.name }}
          </Link>
          <h2 class="text-xl font-semibold text-base-content mt-1">
            Treatment record — {{ fmtDate(record.visited_at) }}
          </h2>
          <p class="text-xs text-base-content/70 font-mono">{{ patient.patient_code }}</p>
        </div>
        <div class="flex gap-2">
          <Link v-if="can('view-treatment-records')" :href="`/patients/${patient.id}/treatment-records`">
            <UiButton variant="secondary">All records</UiButton>
          </Link>
          <Link v-if="can('manage-treatment-records') && record.is_editable" :href="`/patients/${patient.id}/treatment-records/${record.id}/edit`">
            <UiButton>Edit</UiButton>
          </Link>
          <UiButton
            v-if="can('manage-treatment-records') && record.status === 'in_progress'"
            variant="secondary"
            @click="complete"
          >
            Mark completed
          </UiButton>
          <UiButton
            v-if="can('manage-treatment-records') && record.status !== 'cancelled'"
            variant="secondary"
            @click="cancel"
          >
            Cancel
          </UiButton>
        </div>
      </div>

      <UiCard title="Visit details">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div><span class="text-base-content/70">Visit date:</span> {{ fmtDate(record.visited_at) }}</div>
          <div><span class="text-base-content/70">Doctor:</span>
            <span v-if="record.doctor_name" class="inline-flex items-center gap-2">
              <span class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: record.doctor_color }" />
              {{ record.doctor_name }}
            </span>
            <span v-else>—</span>
          </div>
          <div><span class="text-base-content/70">Status:</span>
            <UiBadge :variant="record.status === 'completed' ? 'success' : record.status === 'cancelled' ? 'danger' : 'warning'">
              {{ record.status_label }}
            </UiBadge>
          </div>
          <div><span class="text-base-content/70">Follow-up:</span> {{ fmtDate(record.follow_up_date) }}</div>
          <div v-if="record.appointment_id" class="col-span-2">
            <span class="text-base-content/70">Linked appointment:</span>
            <Link :href="`/appointments/${record.appointment_id}`" class="text-primary hover:underline ml-1">#{{ record.appointment_id }}</Link>
          </div>
        </div>
      </UiCard>

      <UiCard title="Chief complaint">
        <p class="whitespace-pre-wrap text-sm">{{ record.chief_complaint || '—' }}</p>
      </UiCard>

      <UiCard title="Diagnosis">
        <p class="whitespace-pre-wrap text-sm">{{ record.diagnosis || '—' }}</p>
      </UiCard>

      <UiCard title="Treatment notes">
        <p class="whitespace-pre-wrap text-sm">{{ record.treatment_notes || '—' }}</p>
      </UiCard>

      <UiCard title="Tooth chart">
        <div v-if="toothEntries().length === 0" class="text-sm text-base-content/70">No teeth marked.</div>
        <div v-else>
          <div class="flex flex-wrap gap-2">
            <div
              v-for="[num, status] in toothEntries()"
              :key="num"
              class="rounded-md border border-base-300 bg-base-200 px-3 py-2 text-xs"
            >
              <div class="font-mono font-semibold">Tooth {{ num }}</div>
              <div class="text-base-content/70">{{ status }}</div>
            </div>
          </div>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>