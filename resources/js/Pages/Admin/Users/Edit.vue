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

const props = defineProps({ user: Object, roles: Array })

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone,
  password: '',
  password_confirmation: '',
  role: props.user.role ?? 'receptionist',
  is_active: props.user.is_active,
})

function submit() {
  form.patch(`/admin/users/${props.user.id}`)
}
</script>

<template>
  <Head title="Edit user" />
  <AuthenticatedLayout>
    <div class="max-w-2xl">
      <UiCard :title="`Edit ${user.name}`">
        <UiForm @submit.prevent="submit">
          <UiField label="Name" :error="form.errors.name" required
            ><UiInput v-model="form.name" required
          /></UiField>
          <UiField label="Email" :error="form.errors.email" required
            ><UiInput v-model="form.email" type="email" required
          /></UiField>
          <UiField label="Phone"><UiInput v-model="form.phone" type="tel" /></UiField>
          <UiField
            label="New password"
            :error="form.errors.password"
            hint="Leave blank to keep current password"
          >
            <UiInput v-model="form.password" type="password" />
          </UiField>
          <UiField label="Confirm new password" :error="form.errors.password_confirmation">
            <UiInput v-model="form.password_confirmation" type="password" />
          </UiField>
          <UiField label="Role" required>
            <UiSelect v-model="form.role" :options="roles.map((r) => ({ label: r, value: r }))" />
          </UiField>
          <UiField label="Active"><UiSwitch v-model="form.is_active" /></UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-300">
            <Link
              href="/admin/users"
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
