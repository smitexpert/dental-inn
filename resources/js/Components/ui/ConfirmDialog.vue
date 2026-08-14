<script setup>
import ConfirmDialog from 'primevue/confirmdialog'
</script>

<template>
  <ConfirmDialog
    :pt="{
      root: { class: 'fixed inset-0 z-50 flex items-center justify-center p-4' },
      mask: { class: 'fixed inset-0 bg-black/50' },
      container: { class: 'bg-surface border border-border rounded-lg shadow-xl w-full max-w-md' },
      header: { class: 'px-4 py-3 border-b border-border' },
      title: { class: 'text-lg font-semibold text-foreground' },
      content: { class: 'p-4 text-foreground' },
      footer: { class: 'px-4 py-3 border-t border-border flex justify-end gap-2' },
      acceptButton: {
        class: 'h-10 px-4 rounded-md bg-primary-600 text-white font-medium hover:bg-primary-700',
      },
      rejectButton: {
        class:
          'h-10 px-4 rounded-md bg-surface-muted text-foreground border border-border hover:bg-surface-muted/70',
      },
    }"
  />
</template>
