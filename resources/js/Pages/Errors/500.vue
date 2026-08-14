<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiButton from '@/Components/ui/Button.vue'
</script>

<template>
  <Head title="Server error" />
  <AuthenticatedLayout>
    <div class="max-w-md mx-auto text-center py-12">
      <h1 class="text-3xl font-semibold text-base-content mb-2">500 — Something went wrong</h1>
      <p class="text-base-content/70 mb-6">An unexpected error occurred. Please try again later.</p>
      <Link href="/dashboard"><UiButton>Go to dashboard</UiButton></Link>
    </div>
  </AuthenticatedLayout>
</template>
