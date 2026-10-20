<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiSwitch from '@/Components/ui/Switch.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'

const props = defineProps({ patient: Object })

const form = useForm({
  name: props.patient.name ?? '',
  phone: props.patient.phone ?? '',
  email: props.patient.email ?? '',
  age: props.patient.age ?? '',
  gender: props.patient.gender ?? null,
  address_line_1: props.patient.address_line_1 ?? '',
  address_line_2: props.patient.address_line_2 ?? '',
  city: props.patient.city ?? '',
  state: props.patient.state ?? '',
  postal_code: props.patient.postal_code ?? '',
  country: props.patient.country ?? '',
  emergency_contact_name: props.patient.emergency_contact_name ?? '',
  emergency_contact_phone: props.patient.emergency_contact_phone ?? '',
  medical_history: props.patient.medical_history ?? '',
  dental_history: props.patient.dental_history ?? '',
  notes: props.patient.notes ?? '',
  is_active: props.patient.is_active ?? true,
})

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
]

function submit() {
  form.patch(`/patients/${props.patient.id}`)
}
</script>

<template>
  <Head :title="`Edit ${patient.name}`" />
  <AuthenticatedLayout>
    <div class="max-w-3xl my-4">
      <UiCard :title="`Edit ${patient.name}`">
        <UiForm @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-3">
            <UiField label="Name" :error="form.errors.name" required
              ><UiInput v-model="form.name" required
            /></UiField>
            <UiField label="Phone" :error="form.errors.phone"
              ><UiInput v-model="form.phone" type="tel"
            /></UiField>
            <UiField label="Email" :error="form.errors.email"
              ><UiInput v-model="form.email" type="email"
            /></UiField>
            <UiField label="Age" :error="form.errors.age" hint="Used to derive the date of birth"
              ><UiInput
                v-model="form.age"
                type="number"
                min="0"
                max="130"
                suffix="Years"
                placeholder="e.g. 20"
            /></UiField>
            <UiField label="Gender" :error="form.errors.gender"
              ><UiSelect v-model="form.gender" :options="genderOptions" placeholder="Select gender" />
            </UiField>
            <UiField label="Country"
              ><UiInput v-model="form.country"
            /></UiField>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <UiField label="Address line 1" :error="form.errors.address_line_1"
              ><UiInput v-model="form.address_line_1"
            /></UiField>
            <UiField label="Address line 2" :error="form.errors.address_line_2"
              ><UiInput v-model="form.address_line_2"
            /></UiField>
            <UiField label="City" :error="form.errors.city"
              ><UiInput v-model="form.city"
            /></UiField>
            <UiField label="State" :error="form.errors.state"
              ><UiInput v-model="form.state"
            /></UiField>
            <UiField label="Postal code" :error="form.errors.postal_code"
              ><UiInput v-model="form.postal_code"
            /></UiField>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <UiField label="Emergency contact name" :error="form.errors.emergency_contact_name"
              ><UiInput v-model="form.emergency_contact_name"
            /></UiField>
            <UiField label="Emergency contact phone" :error="form.errors.emergency_contact_phone"
              ><UiInput v-model="form.emergency_contact_phone" type="tel"
            /></UiField>
          </div>
          <UiField label="Medical history" :error="form.errors.medical_history"
            ><UiTextarea v-model="form.medical_history" rows="3"
          /></UiField>
          <UiField label="Dental history" :error="form.errors.dental_history"
            ><UiTextarea v-model="form.dental_history" rows="3"
          /></UiField>
          <UiField label="Notes" :error="form.errors.notes"
            ><UiTextarea v-model="form.notes" rows="2"
          /></UiField>
          <UiField label="Active patient"><UiSwitch v-model="form.is_active" /></UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-300">
            <Link
              :href="`/patients/${patient.id}`"
              class="h-10 px-4 inline-flex items-center rounded-md bg-base-200 text-base-content border border-base-300 text-sm"
              >Cancel</Link
            >
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
