<script setup>
import Button from '@/Components/ui/Button.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  processing: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  cancelHref: { type: String, default: null },
  showCancel: { type: Boolean, default: false },
})
defineEmits(['submit', 'cancel'])
</script>

<template>
  <div class="flex items-center justify-end gap-2 pt-4 border-t border-border">
    <Link
      v-if="showCancel && cancelHref"
      :href="cancelHref"
      class="h-10 px-4 inline-flex items-center rounded-md bg-surface-muted text-foreground border border-border hover:bg-surface-muted/70 text-sm"
    >
      Cancel
    </Link>
    <button
      type="submit"
      :disabled="processing"
      class="h-10 px-4 rounded-md bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
    >
      {{ processing ? 'Saving…' : submitLabel }}
    </button>
  </div>
</template>
