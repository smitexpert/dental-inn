<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import Select from '@/Components/ui/Select.vue'
import Textarea from '@/Components/ui/Textarea.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import ToothChartPicker from '@/Components/ToothChartPicker.vue'

const props = defineProps({
  patient: Object,
  record: Object,
  doctors: Array,
  statuses: Array,
})

const record = props.record

const patient = props.patient

const form = useForm({
  visited_at: props.record.visited_at ? props.record.visited_at.slice(0, 10) : '',
  doctor_id: props.record.doctor_id ?? null,
  appointment_id: props.record.appointment_id ?? null,
  chief_complaint: props.record.chief_complaint ?? '',
  diagnosis: props.record.diagnosis ?? '',
  treatment_notes: props.record.treatment_notes ?? '',
  tooth_chart: props.record.tooth_chart ?? {},
  follow_up_date: props.record.follow_up_date ?? '',
  status: props.record.status ?? 'in_progress',
})

function submit() {
  form.patch(`/patients/${props.patient.id}/treatment-records/${props.record.id}`)
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString()
}

const breadcrumbs = [
  { label: 'Patients', href: '/patients' },
  { label: patient.name, href: `/patients/${patient.id}` },
  { label: 'Treatment records', href: `/patients/${patient.id}/treatment-records` },
  { label: fmtDate(record.visited_at) || 'Record', href: `/patients/${patient.id}/treatment-records/${record.id}` },
  { label: 'Edit' },
]
</script>

<template>
  <Head :title="`Edit record — ${patient.name}`" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="Edit treatment record"
        :subtitle="`${patient.name} · ${patient.patient_code}`"
        icon="stethoscope"
        eyebrow="Edit visit"
      />

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Visit details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field
              label="Visit date"
              :error="form.errors.visited_at"
              required
              html-for="rec-date"
            >
              <input
                id="rec-date"
                v-model="form.visited_at"
                type="date"
                required
                class="input-shell h-10"
              />
            </Field>
            <Field label="Doctor" :error="form.errors.doctor_id" html-for="rec-doctor">
              <Select
                id="rec-doctor"
                v-model="form.doctor_id"
                :options="doctors.map((d) => ({ label: d.name, value: d.id }))"
                placeholder="Select doctor"
              />
            </Field>
            <Field label="Status" :error="form.errors.status" html-for="rec-status">
              <Select
                id="rec-status"
                v-model="form.status"
                :options="statuses"
              />
            </Field>
            <Field
              label="Follow-up date"
              :error="form.errors.follow_up_date"
              html-for="rec-followup"
            >
              <input
                id="rec-followup"
                v-model="form.follow_up_date"
                type="date"
                class="input-shell h-10"
              />
            </Field>
          </div>
        </Card>

        <Card title="Clinical notes">
          <div class="space-y-4">
            <Field
              label="Chief complaint"
              :error="form.errors.chief_complaint"
              html-for="rec-complaint"
            >
              <Textarea
                id="rec-complaint"
                v-model="form.chief_complaint"
                :rows="2"
                placeholder="What brings the patient in today?"
              />
            </Field>

            <Field
              label="Diagnosis"
              :error="form.errors.diagnosis"
              html-for="rec-diagnosis"
            >
              <Textarea
                id="rec-diagnosis"
                v-model="form.diagnosis"
                :rows="3"
                placeholder="Findings and diagnosis…"
              />
            </Field>

            <Field
              label="Treatment notes"
              :error="form.errors.treatment_notes"
              html-for="rec-notes"
            >
              <Textarea
                id="rec-notes"
                v-model="form.treatment_notes"
                :rows="4"
                placeholder="Procedures performed, materials used, recommendations…"
              />
            </Field>
          </div>
        </Card>

        <Card title="Tooth chart" subtitle="Click a tooth to cycle through conditions.">
          <Field :error="form.errors.tooth_chart">
            <ToothChartPicker v-model="form.tooth_chart" />
          </Field>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="`/patients/${patient.id}/treatment-records/${record.id}`">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :loading="form.processing" icon-right="check">
            Save changes
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
