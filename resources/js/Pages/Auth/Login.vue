<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/Auth.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import Checkbox from '@/Components/ui/Checkbox.vue'
import Alert from '@/Components/ui/Alert.vue'

defineProps({
  status: { type: String, default: null },
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post('/login', { onFinish: () => form.reset('password') })
}
</script>

<template>
  <Head title="Log in" />
  <AuthLayout>
    <Alert v-if="status" variant="success" class="mb-4">
      {{ status }}
    </Alert>

    <div class="mb-6">
      <h1 class="text-xl font-semibold text-base-content">Sign in</h1>
      <p class="text-sm text-base-content/70 mt-1">
        Use your clinic account to continue.
      </p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <Field label="Email" :error="form.errors.email" html-for="email" required>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          required
          autofocus
          autocomplete="email"
          placeholder="you@clinic.com"
          :invalid="!!form.errors.email"
        />
      </Field>

      <div class="space-y-1">
        <div class="flex items-center justify-between">
          <label for="password" class="block text-sm font-medium text-base-content">
            Password <span class="text-error">*</span>
          </label>
          <Link href="/forgot-password" class="text-sm text-primary hover:underline">
            Forgot?
          </Link>
        </div>
        <Input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          placeholder="••••••••"
          :invalid="!!form.errors.password"
        />
        <p v-if="form.errors.password" class="text-sm text-error">{{ form.errors.password }}</p>
      </div>

      <Checkbox v-model="form.remember" label="Remember me on this device" />

      <Button type="submit" :loading="form.processing" class="w-full">
        {{ form.processing ? 'Signing in…' : 'Sign in' }}
      </Button>
    </form>
  </AuthLayout>
</template>
