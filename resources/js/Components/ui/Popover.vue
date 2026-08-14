<script setup>
import Popover from 'primevue/popover'

defineProps({})
defineEmits([])

const pt = {
  root: { class: 'absolute z-40' },
  content: { class: 'bg-surface border border-border rounded-md shadow-md p-3' },
}
</script>

<template>
  <Popover :pt="pt">
    <slot />
  </Popover>
</template>
