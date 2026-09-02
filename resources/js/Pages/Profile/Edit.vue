<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import Avatar from '@/Components/ui/Avatar.vue'
import Badge from '@/Components/ui/Badge.vue'
import Alert from '@/Components/ui/Alert.vue'
import { useConfirm } from '@/Composables/useConfirm.js'

const props = defineProps({ user: { type: Object, required: true } })
const confirm = useConfirm()

const profile = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone,
})
function saveProfile() {
  profile.patch('/profile')
}

const password = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})
function savePassword() {
  password.patch('/profile/password', {
    onSuccess: () => password.reset(),
  })
}

function deactivate() {
  confirm.require({
    header: 'Deactivate account?',
    message:
      'Your account will be deactivated and you will be signed out. This can be reversed by an admin.',
    acceptLabel: 'Deactivate',
    acceptClass: 'btn-error',
    accept: () => router.delete('/profile'),
  })
}
</script>

<template>
  <Head title="Profile" />
  <AuthenticatedLayout>
    <div class="max-w-2xl space-y-5">
      <PageHeader
        title="Your profile"
        subtitle="Manage your account information, password and access."
        icon="user-cog"
        eyebrow="Account"
      />

      <!-- Identity card -->
      <Card>
        <div class="flex items-center gap-4">
          <Avatar :name="user.name" size="xl" />
          <div class="min-w-0 flex-1">
            <p class="text-lg font-semibold text-base-content">{{ user.name }}</p>
            <p class="text-sm text-(--color-muted) mt-0.5">{{ user.email }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
              <Badge v-for="r in user.roles || []" :key="r" variant="primary">{{ r }}</Badge>
              <Badge :variant="user.is_active ? 'success' : 'warning'" dot>
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </Badge>
            </div>
          </div>
        </div>
      </Card>

      <!-- Profile form -->
      <Form :processing="profile.processing" @submit.prevent="saveProfile">
        <Card title="Profile details" subtitle="Update your name, email and phone.">
          <div v-if="profile.errors._all" class="mb-3">
            <Alert variant="danger">{{ profile.errors._all }}</Alert>
          </div>
          <div class="space-y-4">
            <Field label="Name" :error="profile.errors.name" required>
              <Input v-model="profile.name" type="text" required />
            </Field>
            <Field label="Email" :error="profile.errors.email" required>
              <Input v-model="profile.email" type="email" required />
            </Field>
            <Field label="Phone" :error="profile.errors.phone">
              <Input v-model="profile.phone" type="tel" />
            </Field>
          </div>
          <template #footer>
            <Button
              type="submit"
              :loading="profile.processing"
              icon-right="check"
            >
              Save profile
            </Button>
          </template>
        </Card>
      </Form>

      <!-- Password form -->
      <Form :processing="password.processing" @submit.prevent="savePassword">
        <Card title="Change password" subtitle="Use a strong password to keep your account safe.">
          <div class="space-y-4">
            <Field
              label="Current password"
              :error="password.errors.current_password"
              required
            >
              <Input v-model="password.current_password" type="password" required />
            </Field>
            <Field
              label="New password"
              :error="password.errors.password"
              required
            >
              <Input v-model="password.password" type="password" required />
            </Field>
            <Field
              label="Confirm new password"
              :error="password.errors.password_confirmation"
              required
            >
              <Input v-model="password.password_confirmation" type="password" required />
            </Field>
          </div>
          <template #footer>
            <Button
              type="submit"
              :loading="password.processing"
              icon-right="check"
            >
              Update password
            </Button>
          </template>
        </Card>
      </Form>

      <!-- Danger zone -->
      <Card>
        <template #header>
          <div>
            <h3 class="text-base font-semibold text-error">Danger zone</h3>
            <p class="text-sm text-(--color-muted) mt-0.5">
              Irreversible actions. Please be careful.
            </p>
          </div>
        </template>
        <Alert variant="warning" class="mb-4">
          Deactivating your account will sign you out and prevent future logins.
        </Alert>
        <div class="flex justify-end">
          <Button variant="danger" icon="trash" @click="deactivate">
            Deactivate my account
          </Button>
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
