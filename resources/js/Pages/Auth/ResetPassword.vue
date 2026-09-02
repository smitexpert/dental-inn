<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/Auth.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'

const props = defineProps({
  email: { type: String, default: '' },
  token: { type: String, required: true },
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post('/reset-password', {
    onSuccess: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <Head title="Reset password" />
  <AuthLayout>
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-base-content">Set a new password</h1>
      <p class="text-sm text-(--color-muted) mt-1">
        Enter a new password for your account.
      </p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <Field label="Email" html-for="email" required :error="form.errors.email">
        <Input
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="email"
          placeholder="you@clinic.com"
          prefix-icon="mail"
          :invalid="!!form.errors.email"
        />
      </Field>

      <Field label="New password" html-for="password" required :error="form.errors.password" description="Use at least 8 characters.">
        <Input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="new-password"
          placeholder="••••••••"
          prefix-icon="lock"
          :invalid="!!form.errors.password"
        />
      </Field>

      <Field label="Confirm password" html-for="password_confirmation" required>
        <Input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          required
          autocomplete="new-password"
          placeholder="••••••••"
          prefix-icon="lock"
        />
      </Field>

      <Button type="submit" block :loading="form.processing">
        {{ form.processing ? 'Saving…' : 'Reset password' }}
      </Button>

      <p class="text-sm text-(--color-muted) text-center">
        <Link href="/login" class="text-primary hover:underline font-medium">
          Back to sign in
        </Link>
      </p>
    </form>
  </AuthLayout>
</template>
