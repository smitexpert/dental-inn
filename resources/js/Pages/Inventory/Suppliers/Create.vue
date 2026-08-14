<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'
import UiSwitch from '@/Components/ui/Switch.vue'
import UiFormError from '@/Components/ui/Form/FormError.vue'
import UiFormActions from '@/Components/ui/Form/FormActions.vue'

const form = useForm({
  name: '',
  contact_person: '',
  phone: '',
  email: '',
  address: '',
  notes: '',
  is_active: true,
})

function submit() {
  form.post('/inventory/suppliers')
}
</script>

<template>
  <Head title="New supplier" />
  <AuthenticatedLayout>
    <UiCard title="New supplier">
      <UiForm @submit="submit">
        <UiFormError :errors="form.errors" />
        <UiField label="Name" required :error="form.errors.name">
          <UiInput v-model="form.name" />
        </UiField>
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Contact person"><UiInput v-model="form.contact_person" /></UiField>
          <UiField label="Phone"><UiInput v-model="form.phone" /></UiField>
        </div>
        <UiField label="Email"><UiInput v-model="form.email" type="email" /></UiField>
        <UiField label="Address"><UiTextarea v-model="form.address" :rows="2" /></UiField>
        <UiField label="Notes"><UiTextarea v-model="form.notes" :rows="2" /></UiField>
        <UiField label="Active"><UiSwitch v-model="form.is_active" /></UiField>
        <UiFormActions
          :processing="form.processing"
          submit-label="Create supplier"
          :show-cancel="true"
          cancel-href="/inventory/suppliers"
        />
      </UiForm>
    </UiCard>
  </AuthenticatedLayout>
</template>
