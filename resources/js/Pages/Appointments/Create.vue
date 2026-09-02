<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import Button from '@/Components/ui/Button.vue'
import Textarea from '@/Components/ui/Textarea.vue'
import Icon from '@/Components/ui/Icon.vue'
import PatientQuickAddDialog from '@/Components/PatientQuickAddDialog.vue'
import AppointmentDateTimePicker from '@/Components/AppointmentDateTimePicker.vue'

const props = defineProps({ doctors: Array })

const form = useForm({
  doctor_id: props.doctors[0]?.id ?? null,
  patient_id: null,
  scheduled_at: '',
  duration_minutes: 30,
  notes: '',
})

const selectedPatient = ref(null)
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
  form.post('/appointments')
}

const breadcrumbs = [
  { label: 'Appointments', href: '/appointments' },
  { label: 'New appointment' },
]
</script>

<template>
  <Head title="New appointment" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="New appointment"
        subtitle="Pick a patient, doctor, and time — notes are optional."
        icon="calendar-clock"
        eyebrow="Schedule"
      />

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Visit details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Doctor" :error="form.errors.doctor_id" required>
              <Select
                v-model="form.doctor_id"
                :options="doctors.map((d) => ({ label: d.name, value: d.id }))"
                placeholder="Select doctor"
              />
            </Field>
            <Field label="Duration" :error="form.errors.duration_minutes" required>
              <Input
                v-model="form.duration_minutes"
                type="number"
                min="5"
                max="480"
                required
                placeholder="30"
              />
              <p class="mt-1.5 text-xs text-muted">Minutes (5–480).</p>
            </Field>
          </div>
        </Card>

        <Card title="Patient">
          <Field label="Patient" :error="form.errors.patient_id" required>
            <div
              v-if="selectedPatient"
              class="flex items-center justify-between gap-3 rounded-lg border border-(--color-line) bg-base-200 px-3 py-2.5"
            >
              <div class="flex items-center gap-3 min-w-0">
                <span
                  class="h-9 w-9 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center text-sm font-semibold shrink-0"
                >
                  {{ selectedPatient.name?.split(' ').map((s) => s[0]).slice(0, 2).join('').toUpperCase() }}
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-medium text-base-content truncate">
                    {{ selectedPatient.name }}
                  </div>
                  <div class="text-xs text-muted font-mono">
                    {{ selectedPatient.patient_code }} · {{ selectedPatient.phone || 'no phone' }}
                  </div>
                </div>
              </div>
              <Button type="button" variant="ghost" size="sm" icon="edit" @click="pickerOpen = true">
                Change
              </Button>
            </div>
            <button
              v-else
              type="button"
              class="h-20 w-full rounded-lg border border-dashed border-(--color-line-strong) bg-base-100 px-3 text-sm text-muted hover:bg-base-200 hover:border-primary/40 hover:text-base-content transition-colors inline-flex flex-col items-center justify-center gap-1.5"
              @click="pickerOpen = true"
            >
              <Icon name="user" size="md" class="text-muted" />
              <span>Select or create a patient</span>
            </button>
          </Field>
        </Card>

        <Card title="When">
          <Field label="Date & time" :error="form.errors.scheduled_at" required>
            <AppointmentDateTimePicker
              v-model="form.scheduled_at"
              :doctor-id="form.doctor_id"
              :duration-minutes="form.duration_minutes"
              placeholder="Pick a date and time"
            />
            <p class="mt-1.5 text-xs text-muted">
              Click an available slot to book the doctor in one step, or pick any date and time manually.
            </p>
          </Field>
        </Card>

        <Card title="Notes">
          <Field label="Notes" :error="form.errors.notes">
            <Textarea
              v-model="form.notes"
              :rows="3"
              placeholder="Anything staff should know before the visit…"
            />
          </Field>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link href="/appointments">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button
            type="submit"
            :loading="form.processing"
            :disabled="!form.patient_id"
            icon-right="check"
          >
            Book appointment
          </Button>
        </div>
      </Form>
    </div>

    <PatientQuickAddDialog
      v-model="pickerOpen"
      @selected="onPatientSelected"
      @created="onPatientCreated"
    />
  </AuthenticatedLayout>
</template>
