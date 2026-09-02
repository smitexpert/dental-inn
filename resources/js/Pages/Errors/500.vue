<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/Auth.vue'
import Button from '@/Components/ui/Button.vue'
import Icon from '@/Components/ui/Icon.vue'
</script>

<template>
  <Head title="Server error" />
  <AuthLayout>
    <div class="text-center py-6">
      <div class="mx-auto h-14 w-14 rounded-2xl cta-gradient inline-flex items-center justify-center text-primary-content shadow-(--shadow-pop)">
        <Icon name="alert-triangle" size="lg" />
      </div>
      <p class="app-section-title mt-5">Error 500</p>
      <h1 class="text-2xl font-semibold text-base-content mt-1">Something went wrong</h1>
      <p class="text-sm text-(--color-muted) mt-2 max-w-sm mx-auto">
        An unexpected error occurred on our end. Please try again in a moment,
        or head back to the dashboard.
      </p>
      <div class="mt-6 flex items-center justify-center gap-2">
        <Link href="/dashboard">
          <Button variant="outline" icon="arrow-right">Back to dashboard</Button>
        </Link>
      </div>
    </div>
  </AuthLayout>
</template>
