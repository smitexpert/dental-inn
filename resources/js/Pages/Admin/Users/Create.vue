<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiSwitch from '@/Components/ui/Switch.vue'

const props = defineProps({ roles: Array })

const form = useForm({
  name: '', email: '', phone: '',
  password: '', password_confirmation: '',
  role: 'receptionist', is_active: true,
})

function submit() { form.post('/admin/users') }
</script>

<template>
  <Head title="New user" />
  <AuthenticatedLayout>
    <div class="max-w-2xl">
      <UiCard title="New user">
        <UiForm @submit.prevent="submit">
          <UiField label="Name" :error="form.errors.name" required><UiInput v-model="form.name" required /></UiField>
          <UiField label="Email" :error="form.errors.email" required><UiInput v-model="form.email" type="email" required /></UiField>
          <UiField label="Phone"><UiInput v-model="form.phone" type="tel" /></UiField>
          <UiField label="Password" :error="form.errors.password" required><UiInput v-model="form.password" type="password" required /></UiField>
          <UiField label="Confirm password" :error="form.errors.password_confirmation" required><UiInput v-model="form.password_confirmation" type="password" required /></UiField>
          <UiField label="Role" :error="form.errors.role" required>
            <UiSelect v-model="form.role" :options="roles.map(r => ({ label: r, value: r }))" />
          </UiField>
          <UiField label="Active">
            <UiSwitch v-model="form.is_active" />
          </UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
            <Link href="/admin/users" class="h-10 px-4 inline-flex items-center rounded-md bg-surface-muted text-foreground border border-border text-sm">Cancel</Link>
            <UiButton type="submit" :loading="form.processing">Create</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
