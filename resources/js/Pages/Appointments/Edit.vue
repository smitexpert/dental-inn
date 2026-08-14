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

const props = defineProps({ appointment: Object, doctors: Array })

const form = useForm({
  doctor_id: props.appointment.doctor_id,
  patient_name: props.appointment.patient_name ?? '',
  patient_phone: props.appointment.patient_phone ?? '',
  patient_id: props.appointment.patient_id,
  scheduled_at: props.appointment.scheduled_at ? props.appointment.scheduled_at.slice(0, 16) : '',
  duration_minutes: props.appointment.duration_minutes ?? 30,
  notes: props.appointment.notes ?? '',
})

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
            <UiField label="Patient name" :error="form.errors.patient_name" required>
              <UiInput v-model="form.patient_name" required />
            </UiField>
            <UiField label="Patient phone" :error="form.errors.patient_phone" required>
              <UiInput v-model="form.patient_phone" type="tel" required />
            </UiField>
            <UiField label="Scheduled at" :error="form.errors.scheduled_at" required>
              <input
                v-model="form.scheduled_at"
                type="datetime-local"
                class="h-10 w-full rounded-md border border-border bg-surface px-3 text-sm"
              />
            </UiField>
          </div>
          <UiField label="Notes" :error="form.errors.notes">
            <UiTextarea v-model="form.notes" rows="3" />
          </UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
            <Link
              :href="`/appointments/${appointment.id}`"
              class="h-10 px-4 inline-flex items-center rounded-md bg-surface-muted text-foreground border border-border text-sm"
              >Cancel</Link
            >
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
