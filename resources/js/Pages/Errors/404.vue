<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiButton from '@/Components/ui/Button.vue'
</script>

<template>
  <Head title="Not found" />
  <AuthenticatedLayout>
    <div class="max-w-md mx-auto text-center py-12">
      <h1 class="text-3xl font-semibold text-base-content mb-2">404 — Not found</h1>
      <p class="text-base-content/70 mb-6">The page you're looking for doesn't exist.</p>
      <Link href="/dashboard"><UiButton>Go to dashboard</UiButton></Link>
    </div>
  </AuthenticatedLayout>
</template>
