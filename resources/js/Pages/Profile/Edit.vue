<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiButton from '@/Components/ui/Button.vue'

const props = defineProps({ user: { type: Object, required: true } })

const profile = useForm({
  name:  props.user.name,
  email: props.user.email,
  phone: props.user.phone,
})
function saveProfile() { profile.patch('/profile') }

const password = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})
function savePassword() {
  password.patch('/profile/password', { onSuccess: () => password.reset() })
}

function deactivate() {
  if (! confirm('This will deactivate your account. Continue?')) return
  router.delete('/profile')
}
</script>

<template>
  <Head title="Profile" />
  <AuthenticatedLayout>
    <div class="max-w-2xl space-y-6">
      <UiCard title="Profile">
        <UiForm @submit.prevent="saveProfile">
          <div v-if="profile.errors._all" class="text-sm text-danger">{{ profile.errors._all }}</div>
          <UiField label="Name" :error="profile.errors.name">
            <UiInput v-model="profile.name" type="text" required />
          </UiField>
          <UiField label="Email" :error="profile.errors.email">
            <UiInput v-model="profile.email" type="email" required />
          </UiField>
          <UiField label="Phone" :error="profile.errors.phone">
            <UiInput v-model="profile.phone" type="tel" />
          </UiField>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="profile.processing">Save profile</UiButton>
          </div>
        </UiForm>
      </UiCard>

      <UiCard title="Change password">
        <UiForm @submit.prevent="savePassword">
          <UiField label="Current password" :error="password.errors.current_password">
            <UiInput v-model="password.current_password" type="password" required />
          </UiField>
          <UiField label="New password" :error="password.errors.password">
            <UiInput v-model="password.password" type="password" required />
          </UiField>
          <UiField label="Confirm new password" :error="password.errors.password_confirmation">
            <UiInput v-model="password.password_confirmation" type="password" required />
          </UiField>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="password.processing">Update password</UiButton>
          </div>
        </UiForm>
      </UiCard>

      <UiCard title="Danger zone">
        <p class="text-sm text-muted-foreground mb-3">Deactivating your account will sign you out and prevent future logins.</p>
        <UiButton variant="danger" @click="deactivate">Deactivate my account</UiButton>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
