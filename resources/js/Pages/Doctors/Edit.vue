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

const props = defineProps({ doctor: Object })

const form = useForm({
  name: props.doctor.name ?? '',
  email: props.doctor.email ?? '',
  phone: props.doctor.phone ?? '',
  password: '',
  password_confirmation: '',
  is_active: !!props.doctor.is_active,
  specialization: props.doctor.specialization ?? '',
  license_number: props.doctor.license_number ?? '',
  bio: props.doctor.bio ?? '',
  years_of_experience: props.doctor.years_of_experience,
  consultation_fee: props.doctor.consultation_fee,
  color: props.doctor.color ?? '#0EA5E9',
  is_available: !!props.doctor.is_available,
})

function submit() {
  form.patch(`/doctors/${props.doctor.id}`)
}
</script>

<template>
  <Head :title="`Edit ${doctor.name}`" />
  <AuthenticatedLayout>
    <div class="max-w-2xl">
      <UiCard :title="`Edit ${doctor.name}`">
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
            <UiField label="New password" :error="form.errors.password" hint="Leave blank to keep current password"
              ><UiInput v-model="form.password" type="password"
            /></UiField>
            <UiField label="Confirm new password" :error="form.errors.password_confirmation"
              ><UiInput v-model="form.password_confirmation" type="password"
            /></UiField>
          </div>
          <div class="flex items-center gap-6">
            <UiField label="Active"><UiSwitch v-model="form.is_active" /></UiField>
            <UiField label="Available for appointments"><UiSwitch v-model="form.is_available" /></UiField>
          </div>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
            <Link
              :href="`/doctors/${doctor.id}`"
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
