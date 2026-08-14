<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'
import PatientQuickAddDialog from '@/Components/PatientQuickAddDialog.vue'
import { ref } from 'vue'

const props = defineProps({ appointment: Object, doctors: Array })

const form = useForm({
  doctor_id: props.appointment.doctor_id,
  patient_id: props.appointment.patient_id,
  scheduled_at: props.appointment.scheduled_at ? props.appointment.scheduled_at.slice(0, 16) : '',
  duration_minutes: props.appointment.duration_minutes ?? 30,
  notes: props.appointment.notes ?? '',
})

const selectedPatient = ref(props.appointment.patient ?? null)
const pickerOpen = ref(false)

function onPatientSelected(p) {
  selectedPatient.value = p
  form.patient_id = p.id
}
function onPatientCreated(p) {
  selectedPatient.value = p
  form.patient_id = p.id
}

function submit() {
  form.patch(`/appointments/${props.appointment.id}`)
}
</script>

<template>
  <Head title="Edit appointment" />
  <AuthenticatedLayout>
    <div class="max-w-3xl">
      <UiCard title="Edit appointment">
        <UiForm @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-3">
            <UiField label="Doctor" :error="form.errors.doctor_id" required>
              <UiSelect
                v-model="form.doctor_id"
                :options="doctors.map((d) => ({ label: d.name, value: d.id }))"
              />
            </UiField>
            <UiField label="Duration (minutes)" :error="form.errors.duration_minutes" required>
              <UiInput v-model="form.duration_minutes" type="number" min="5" max="480" required />
            </UiField>
          </div>

          <UiField label="Patient" :error="form.errors.patient_id" required>
            <div v-if="selectedPatient" class="flex items-center justify-between gap-2 rounded-md border border-base-300 bg-base-200 px-3 py-2">
              <div>
                <div class="text-sm font-medium text-base-content">{{ selectedPatient.name }}</div>
                <div class="text-xs text-base-content/70 font-mono">{{ selectedPatient.patient_code }} · {{ selectedPatient.phone || 'no phone' }}</div>
              </div>
              <button type="button" class="text-xs text-primary hover:underline" @click="pickerOpen = true">
                Change
              </button>
            </div>
            <button
              v-else
              type="button"
              class="h-10 w-full rounded-md border border-dashed border-base-300 bg-base-100 px-3 text-sm text-base-content/70 hover:bg-base-200"
              @click="pickerOpen = true"
            >
              Select or create a patient
            </button>
          </UiField>

          <UiField label="Scheduled at" :error="form.errors.scheduled_at" required>
            <input
              v-model="form.scheduled_at"
              type="datetime-local"
              class="h-10 w-full rounded-md border border-base-300 bg-base-100 px-3 text-sm"
            />
          </UiField>

          <UiField label="Notes" :error="form.errors.notes">
            <UiTextarea v-model="form.notes" rows="3" />
          </UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-300">
            <Link
              :href="`/appointments/${appointment.id}`"
              class="h-10 px-4 inline-flex items-center rounded-md bg-base-200 text-base-content border border-base-300 text-sm"
              >Cancel</Link
            >
            <UiButton type="submit" :loading="form.processing" :disabled="!form.patient_id">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>

    <PatientQuickAddDialog
      v-model="pickerOpen"
      @selected="onPatientSelected"
      @created="onPatientCreated"
    />
  </AuthenticatedLayout>
</template>