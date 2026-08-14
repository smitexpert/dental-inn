<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/Auth.vue'

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
    <div v-if="status" class="mb-4 rounded-md bg-success/10 text-success px-3 py-2 text-sm">
      {{ status }}
    </div>

    <h1 class="text-xl font-semibold text-base-content mb-1">Sign in</h1>
    <p class="text-sm text-base-content/70 mb-6">Use your clinic account to continue.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-base-content mb-1">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autofocus
          autocomplete="email"
          class="w-full h-10 px-3 rounded-md border border-base-300 bg-base-100 text-base-content focus:outline-none focus:ring-2 focus:ring-primary"
          :class="form.errors.email && 'border-error'"
        />
        <p v-if="form.errors.email" class="mt-1 text-sm text-error">{{ form.errors.email }}</p>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label for="password" class="block text-sm font-medium text-base-content">Password</label>
          <Link href="/forgot-password" class="text-sm text-primary hover:text-primary">Forgot?</Link>
        </div>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          class="w-full h-10 px-3 rounded-md border border-base-300 bg-base-100 text-base-content focus:outline-none focus:ring-2 focus:ring-primary"
          :class="form.errors.password && 'border-error'"
        />
        <p v-if="form.errors.password" class="mt-1 text-sm text-error">{{ form.errors.password }}</p>
      </div>

      <label class="flex items-center gap-2 text-sm text-base-content">
        <input v-model="form.remember" type="checkbox" class="rounded border-base-300" />
        Remember me
      </label>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full h-10 rounded-md bg-primary text-white font-medium hover:bg-primary disabled:opacity-50"
      >
        {{ form.processing ? 'Signing in…' : 'Sign in' }}
      </button>
    </form>
  </AuthLayout>
</template>
