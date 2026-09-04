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

const form = useForm({
  name: '',
  phone: '',
  email: '',
  date_of_birth: '',
  gender: null,
  address_line_1: '',
  address_line_2: '',
  city: '',
  state: '',
  postal_code: '',
  country: 'Bangladesh',
  emergency_contact_name: '',
  emergency_contact_phone: '',
  medical_history: '',
  dental_history: '',
  notes: '',
  is_active: true,
})

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
]

function submit() {
  form.post('/patients')
}
</script>

<template>
  <Head title="New patient" />
  <AuthenticatedLayout>
    <div class="max-w-3xl my-4">
      <UiCard title="New patient">
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
            <UiField label="Date of birth" :error="form.errors.date_of_birth"
              ><input
                v-model="form.date_of_birth"
                type="date"
                class="h-10 w-full rounded-md border border-base-300 bg-base-100 px-3 text-sm"
            /></UiField>
            <UiField label="Gender" :error="form.errors.gender"
              ><UiSelect v-model="form.gender" :options="genderOptions" placeholder="Select gender" />
            </UiField>
            <UiField label="Country"
              ><UiInput v-model="form.country"
            /></UiField>
          </div>
          <h3 class="text-sm font-semibold text-base-content pt-3 border-t border-base-300">Address</h3>
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
          <h3 class="text-sm font-semibold text-base-content pt-3 border-t border-base-300">Emergency contact</h3>
          <div class="grid grid-cols-2 gap-3">
            <UiField label="Name" :error="form.errors.emergency_contact_name"
              ><UiInput v-model="form.emergency_contact_name"
            /></UiField>
            <UiField label="Phone" :error="form.errors.emergency_contact_phone"
              ><UiInput v-model="form.emergency_contact_phone" type="tel"
            /></UiField>
          </div>
          <h3 class="text-sm font-semibold text-base-content pt-3 border-t border-base-300">Medical history</h3>
          <UiField label="Allergies, conditions, medications" :error="form.errors.medical_history"
            ><UiTextarea v-model="form.medical_history" rows="3"
          /></UiField>
          <UiField label="Previous dental treatments, concerns" :error="form.errors.dental_history"
            ><UiTextarea v-model="form.dental_history" rows="3"
          /></UiField>
          <UiField label="Notes" :error="form.errors.notes"
            ><UiTextarea v-model="form.notes" rows="2"
          /></UiField>
          <UiField label="Active patient"><UiSwitch v-model="form.is_active" /></UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-300">
            <Link
              href="/patients"
              class="h-10 px-4 inline-flex items-center rounded-md bg-base-200 text-base-content border border-base-300 text-sm"
              >Cancel</Link
            >
            <UiButton type="submit" :loading="form.processing">Create</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
