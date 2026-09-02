<script setup>
import { useColorMode } from '@/Composables/useColorMode.js'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Icon from '@/Components/ui/Icon.vue'

useColorMode()

const settings = computed(() => usePage().props.settings ?? {})
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center p-4 bg-base-200 relative overflow-hidden"
  >
    <!-- Decorative gradient blobs -->
    <div
      class="pointer-events-none absolute -top-32 -right-32 h-96 w-96 rounded-full bg-primary/20 blur-3xl"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-secondary/15 blur-3xl"
      aria-hidden="true"
    />

    <div class="relative w-full max-w-md">
      <div class="text-center mb-7">
        <Link href="/" class="inline-flex items-center gap-2.5">
          <span
            class="h-11 w-11 rounded-xl cta-gradient inline-flex items-center justify-center text-primary-content shadow-(--shadow-pop)"
          >
            <Icon name="tooth" size="lg" />
          </span>
          <span class="brand-gradient text-2xl font-bold tracking-tight">
            Caremint
          </span>
        </Link>
        <p v-if="settings.clinic_tagline" class="text-sm text-(--color-muted) mt-2">
          {{ settings.clinic_tagline }}
        </p>
      </div>

      <div class="app-card p-6 sm:p-7">
        <slot />
      </div>

      <p class="text-center text-xs text-(--color-subtle) mt-5">
        © {{ new Date().getFullYear() }} {{ settings.clinic_name }}
      </p>
    </div>
  </div>
</template>
