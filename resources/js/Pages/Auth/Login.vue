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

    <h1 class="text-xl font-semibold text-foreground mb-1">Sign in</h1>
    <p class="text-sm text-muted-foreground mb-6">Use your clinic account to continue.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-foreground mb-1">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autofocus
          autocomplete="email"
          class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500"
          :class="form.errors.email && 'border-danger'"
        />
        <p v-if="form.errors.email" class="mt-1 text-sm text-danger">{{ form.errors.email }}</p>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label for="password" class="block text-sm font-medium text-foreground">Password</label>
          <Link href="/forgot-password" class="text-sm text-primary-600 hover:text-primary-700">Forgot?</Link>
        </div>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500"
          :class="form.errors.password && 'border-danger'"
        />
        <p v-if="form.errors.password" class="mt-1 text-sm text-danger">{{ form.errors.password }}</p>
      </div>

      <label class="flex items-center gap-2 text-sm text-foreground">
        <input v-model="form.remember" type="checkbox" class="rounded border-border" />
        Remember me
      </label>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full h-10 rounded-md bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50"
      >
        {{ form.processing ? 'Signing in…' : 'Sign in' }}
      </button>
    </form>
  </AuthLayout>
</template>
