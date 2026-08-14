<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'
import ToothChartPicker from '@/Components/ToothChartPicker.vue'

const props = defineProps({
  patient: Object,
  doctors: Array,
  statuses: Array,
})

const form = useForm({
  visited_at: new Date().toISOString().slice(0, 10),
  doctor_id: null,
  appointment_id: null,
  chief_complaint: '',
  diagnosis: '',
  treatment_notes: '',
  tooth_chart: {},
  follow_up_date: '',
  status: 'in_progress',
})

function submit() {
  form.post(`/patients/${props.patient.id}/treatment-records`)
}
</script>

<template>
  <Head :title="`New treatment record — ${patient.name}`" />
  <AuthenticatedLayout>
    <div class="max-w-3xl">
      <UiCard :title="`New treatment record — ${patient.name}`">
        <p class="text-xs text-muted-foreground font-mono -mt-2 mb-4">{{ patient.patient_code }}</p>
        <UiForm @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-3">
            <UiField label="Visit date" :error="form.errors.visited_at" required
              ><input
                v-model="form.visited_at"
                type="date"
                required
                class="h-10 w-full rounded-md border border-border bg-surface px-3 text-sm"
            /></UiField>
            <UiField label="Doctor" :error="form.errors.doctor_id"
              ><UiSelect
                v-model="form.doctor_id"
                :options="doctors.map((d) => ({ label: d.name, value: d.id }))"
                placeholder="Select doctor"
              />
            </UiField>
            <UiField label="Status" :error="form.errors.status"
              ><UiSelect v-model="form.status" :options="statuses" />
            </UiField>
            <UiField label="Follow-up date" :error="form.errors.follow_up_date"
              ><input
                v-model="form.follow_up_date"
                type="date"
                class="h-10 w-full rounded-md border border-border bg-surface px-3 text-sm"
            /></UiField>
          </div>

          <UiField label="Chief complaint" :error="form.errors.chief_complaint"
            ><UiTextarea v-model="form.chief_complaint" rows="2"
          /></UiField>

          <UiField label="Diagnosis" :error="form.errors.diagnosis"
            ><UiTextarea v-model="form.diagnosis" rows="3"
          /></UiField>

          <UiField label="Treatment notes" :error="form.errors.treatment_notes"
            ><UiTextarea v-model="form.treatment_notes" rows="4"
          /></UiField>

          <UiField label="Tooth chart" :error="form.errors.tooth_chart">
            <ToothChartPicker v-model="form.tooth_chart" />
          </UiField>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
            <Link
              :href="`/patients/${patient.id}`"
              class="h-10 px-4 inline-flex items-center rounded-md bg-surface-muted text-foreground border border-border text-sm"
              >Cancel</Link
            >
            <UiButton type="submit" :loading="form.processing">Create record</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>