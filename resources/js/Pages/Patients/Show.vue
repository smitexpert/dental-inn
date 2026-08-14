<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'
import { useCurrentUser } from '@/Composables/useCurrentUser.js'

const props = defineProps({
  patient: Object,
  appointments: Array,
  treatment_records: Array,
  duplicates: Array,
})

const { can } = useCurrentUser()

function archive() {
  if (confirm('Archive this patient?')) {
    router.delete(`/patients/${props.patient.id}`)
  }
}

function fmtDate(s) {
  if (!s) return '—'
  return new Date(s).toLocaleDateString()
}
function fmtDateTime(s) {
  if (!s) return '—'
  return new Date(s).toLocaleString()
}
</script>

<template>
  <Head :title="`${patient.name} (${patient.patient_code})`" />
  <AuthenticatedLayout>
    <div class="space-y-4 max-w-5xl">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-base-content">{{ patient.name }}</h2>
          <p class="text-sm text-base-content/70 font-mono">{{ patient.patient_code }}</p>
        </div>
        <div class="flex gap-2">
          <Link v-if="can('manage-patients')" :href="`/patients/${patient.id}/edit`">
            <UiButton variant="secondary">Edit</UiButton>
          </Link>
          <Link v-if="can('manage-patients')" :href="`/patients/${patient.id}/treatment-records/create`">
            <UiButton>New treatment record</UiButton>
          </Link>
        </div>
      </div>

      <div v-if="duplicates.length > 0" class="rounded-md border border-warning/50 bg-warning/10 p-3 text-sm text-base-content">
        <strong class="font-semibold">Possible duplicates:</strong>
        <ul class="mt-1 list-disc list-inside">
          <li v-for="d in duplicates" :key="d.id">
            <Link :href="`/patients/${d.id}`" class="text-primary hover:underline">
              {{ d.name }} ({{ d.patient_code }})
            </Link>
            — {{ d.phone || 'no phone' }}
          </li>
        </ul>
      </div>

      <UiCard title="Contact">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div><span class="text-base-content/70">Phone:</span> {{ patient.phone || '—' }}</div>
          <div><span class="text-base-content/70">Email:</span> {{ patient.email || '—' }}</div>
          <div><span class="text-base-content/70">Date of birth:</span> {{ fmtDate(patient.date_of_birth) }}</div>
          <div><span class="text-base-content/70">Age:</span> {{ patient.age ?? '—' }}</div>
          <div><span class="text-base-content/70">Gender:</span> {{ patient.gender_label || '—' }}</div>
          <div><span class="text-base-content/70">Status:</span>
            <UiBadge :variant="patient.is_active ? 'success' : 'warning'">
              {{ patient.is_active ? 'Active' : 'Inactive' }}
            </UiBadge>
          </div>
          <div class="col-span-2"><span class="text-base-content/70">Address:</span> {{ patient.full_address || '—' }}</div>
          <div class="col-span-2"><span class="text-base-content/70">Emergency contact:</span>
            {{ patient.emergency_contact_name || '—' }} {{ patient.emergency_contact_phone || '' }}
          </div>
        </div>
      </UiCard>

      <UiCard title="Medical & Dental history">
        <div class="space-y-2 text-sm">
          <div>
            <h4 class="text-xs font-semibold text-base-content/70 uppercase">Medical</h4>
            <p class="whitespace-pre-wrap">{{ patient.medical_history || '—' }}</p>
          </div>
          <div>
            <h4 class="text-xs font-semibold text-base-content/70 uppercase">Dental</h4>
            <p class="whitespace-pre-wrap">{{ patient.dental_history || '—' }}</p>
          </div>
          <div v-if="patient.notes">
            <h4 class="text-xs font-semibold text-base-content/70 uppercase">Notes</h4>
            <p class="whitespace-pre-wrap">{{ patient.notes }}</p>
          </div>
        </div>
      </UiCard>

      <UiCard title="Appointments">
        <div v-if="appointments.length === 0" class="text-sm text-base-content/70">No appointments yet.</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-base-200 text-base-content/70">
              <tr>
                <th class="text-left px-4 py-2">When</th>
                <th class="text-left px-4 py-2">Doctor</th>
                <th class="text-left px-4 py-2">Status</th>
                <th class="text-right px-4 py-2">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in appointments" :key="a.id" class="border-b border-base-300 last:border-b-0">
                <td class="px-4 py-2">{{ fmtDateTime(a.scheduled_at) }}</td>
                <td class="px-4 py-2">
                  <span class="inline-flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: a.doctor_color }" />
                    {{ a.doctor_name || '—' }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <UiBadge :variant="a.status === 'cancelled' || a.status === 'no_show' ? 'danger' : 'success'">
                    {{ a.status_label }}
                  </UiBadge>
                </td>
                <td class="px-4 py-2 text-right">
                  <Link :href="`/appointments/${a.id}`" class="text-primary hover:text-primary text-sm">View</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>

      <UiCard title="Treatment records">
        <div v-if="treatment_records.length === 0" class="text-sm text-base-content/70">No treatment records yet.</div>
        <ol v-else class="relative border-l-2 border-base-300 ml-2 space-y-4 pl-4">
          <li v-for="r in treatment_records" :key="r.id" class="relative">
            <span class="absolute -left-[1.4rem] top-1 h-3 w-3 rounded-full bg-primary ring-2 ring-surface" />
            <div class="flex items-center justify-between">
              <Link :href="`/patients/${patient.id}/treatment-records/${r.id}`" class="font-medium text-base-content hover:underline">
                {{ r.chief_complaint || 'Visit' }}
              </Link>
              <UiBadge :variant="r.status === 'completed' ? 'success' : r.status === 'cancelled' ? 'danger' : 'warning'">
                {{ r.status_label }}
              </UiBadge>
            </div>
            <div class="text-xs text-base-content/70">
              {{ fmtDate(r.visited_at) }} · {{ r.doctor_name || 'No doctor' }}
            </div>
          </li>
        </ol>
      </UiCard>

      <div v-if="can('manage-patients')" class="text-right">
        <button @click="archive" class="text-sm text-error-600 hover:text-error-700">Archive patient</button>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
