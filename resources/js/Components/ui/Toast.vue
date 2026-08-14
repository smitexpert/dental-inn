<script setup>
// PrimeVue's toast service is wired to the useToast() composable globally
// by the PrimeVue plugin (resources/js/plugins/primevue.js). This component
// just renders the global Toast container.
import Toast from 'primevue/toast'
</script>

<template>
  <Toast
    position="top-right"
    :pt="{
      root: { class: 'fixed top-4 right-4 z-[100] flex flex-col gap-2' },
      message: {
        class:
          'bg-surface border border-border rounded-md shadow-md px-4 py-3 text-sm text-foreground flex items-start gap-2',
      },
      messageContent: { class: 'flex flex-col gap-0.5' },
      summary: { class: 'font-semibold' },
      detail: { class: 'text-muted-foreground' },
    }"
  />
</template>
