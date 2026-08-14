<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiSwitch from '@/Components/ui/Switch.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  is_active: true,
  specialization: '',
  license_number: '',
  bio: '',
  years_of_experience: null,
  consultation_fee: null,
  color: '#0EA5E9',
  is_available: true,
})

function submit() {
  form.post('/doctors')
}
</script>

<template>
  <Head title="New doctor" />
  <AuthenticatedLayout>
    <div class="max-w-2xl">
      <UiCard title="New doctor">
        <UiForm @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-3">
            <UiField label="Name" :error="form.errors.name" required
              ><UiInput v-model="form.name" required
            /></UiField>
            <UiField label="Email" :error="form.errors.email" required
              ><UiInput v-model="form.email" type="email" required
            /></UiField>
            <UiField label="Phone"><UiInput v-model="form.phone" type="tel" /></UiField>
            <UiField label="Specialization" :error="form.errors.specialization"
              ><UiInput v-model="form.specialization"
            /></UiField>
            <UiField label="License number" :error="form.errors.license_number"
              ><UiInput v-model="form.license_number"
            /></UiField>
            <UiField label="Years of experience" :error="form.errors.years_of_experience"
              ><UiInput v-model="form.years_of_experience" type="number" min="0" max="65"
            /></UiField>
            <UiField label="Consultation fee" :error="form.errors.consultation_fee"
              ><UiInput v-model="form.consultation_fee" type="number" step="0.01" min="0"
            /></UiField>
            <UiField label="Calendar color" :error="form.errors.color" required
              ><UiInput v-model="form.color" type="color" required
            /></UiField>
          </div>
          <UiField label="Bio" :error="form.errors.bio"
            ><UiTextarea v-model="form.bio" rows="3"
          /></UiField>
          <div class="grid grid-cols-2 gap-3">
            <UiField label="Password" :error="form.errors.password" required
              ><UiInput v-model="form.password" type="password" required
            /></UiField>
            <UiField label="Confirm password" :error="form.errors.password_confirmation" required
              ><UiInput v-model="form.password_confirmation" type="password" required
            /></UiField>
          </div>
          <div class="flex items-center gap-6">
            <UiField label="Active"><UiSwitch v-model="form.is_active" /></UiField>
            <UiField label="Available for appointments"><UiSwitch v-model="form.is_available" /></UiField>
          </div>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
            <Link
              href="/doctors"
              class="h-10 px-4 inline-flex items-center rounded-md bg-surface-muted text-foreground border border-border text-sm"
              >Cancel</Link
            >
            <UiButton type="submit" :loading="form.processing">Create</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
