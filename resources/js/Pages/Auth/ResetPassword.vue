<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/Auth.vue'

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
  form.post('/reset-password', { onSuccess: () => form.reset('password', 'password_confirmation') })
}
</script>

<template>
  <Head title="Reset password" />
  <AuthLayout>
    <h1 class="text-xl font-semibold text-foreground mb-1">Set a new password</h1>
    <p class="text-sm text-muted-foreground mb-6">Enter a new password for your account.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-foreground mb-1">Email</label>
        <input id="email" v-model="form.email" type="email" required class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500" />
        <p v-if="form.errors.email" class="mt-1 text-sm text-danger">{{ form.errors.email }}</p>
      </div>
      <div>
        <label for="password" class="block text-sm font-medium text-foreground mb-1">New password</label>
        <input id="password" v-model="form.password" type="password" required class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500" />
        <p v-if="form.errors.password" class="mt-1 text-sm text-danger">{{ form.errors.password }}</p>
      </div>
      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-1">Confirm password</label>
        <input id="password_confirmation" v-model="form.password_confirmation" type="password" required class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500" />
      </div>
      <button type="submit" :disabled="form.processing" class="w-full h-10 rounded-md bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50">
        {{ form.processing ? 'Saving…' : 'Reset password' }}
      </button>
      <p class="text-sm text-muted-foreground text-center">
        <Link href="/login" class="text-primary-600 hover:text-primary-700">Back to sign in</Link>
      </p>
    </form>
  </AuthLayout>
</template>
