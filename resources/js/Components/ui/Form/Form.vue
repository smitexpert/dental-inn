<script setup>
/**
 * Form
 * ----
 * Thin wrapper around a native `<form>` that flips `aria-busy` while a
 * submission is in flight, prevents default submit, and forwards the event
 * to consumers via `submit`.
 */
defineProps({
  method: { type: String, default: 'post' },
  action: { type: String, default: null },
  processing: { type: Boolean, default: false },
})

const emit = defineEmits(['submit'])

function onSubmit(e) {
  e.preventDefault()
  emit('submit', e)
}
</script>

<template>
  <form
    :method="method"
    :action="action"
    :aria-busy="processing || undefined"
    class="space-y-4"
    @submit="onSubmit"
  >
    <slot />
  </form>
</template>
