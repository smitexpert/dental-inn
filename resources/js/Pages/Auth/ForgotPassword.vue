<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/Auth.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'

const form = useForm({ email: '' })

function submit() {
  form.post('/forgot-password', {
    onFinish: () => form.reset('email'),
  })
}
</script>

<template>
  <Head title="Forgot password" />
  <AuthLayout>
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-base-content">Forgot password</h1>
      <p class="text-sm text-muted mt-1">
        Enter your email and we'll send a reset link.
      </p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <Field label="Email" html-for="email" required :error="form.errors.email">
        <Input
          id="email"
          v-model="form.email"
          type="email"
          required
          autofocus
          autocomplete="email"
          placeholder="you@clinic.com"
          prefix-icon="mail"
          :invalid="!!form.errors.email"
        />
      </Field>

      <Button type="submit" block :loading="form.processing">
        {{ form.processing ? 'Sending…' : 'Email reset link' }}
      </Button>

      <p class="text-sm text-muted text-center">
        Remembered it?
        <Link href="/login" class="text-primary hover:underline font-medium">
          Back to sign in
        </Link>
      </p>
    </form>
  </AuthLayout>
</template>
