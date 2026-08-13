<script setup>
import { useColorMode } from '@/Composables/useColorMode.js'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

useColorMode()

const settings = computed(() => usePage().props.settings)
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-surface-muted p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-6">
        <Link href="/" class="inline-block text-2xl font-semibold text-foreground">
          {{ settings.clinic_name }}
        </Link>
        <p v-if="settings.clinic_tagline" class="text-sm text-muted-foreground mt-1">
          {{ settings.clinic_tagline }}
        </p>
      </div>

      <div class="bg-surface border border-border rounded-lg shadow-sm p-6">
        <slot />
      </div>
    </div>
  </div>
</template>
