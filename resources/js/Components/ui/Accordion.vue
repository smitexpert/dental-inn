<script setup>
import { ref } from 'vue'

defineProps({
  modelValue: { type: [String, Array], default: null },
  multiple: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])
const openPanels = ref(new Set())

function toggle(key) {
  if (openPanels.value.has(key)) openPanels.value.delete(key)
  else openPanels.value.add(key)
  emit('update:modelValue', Array.from(openPanels.value))
}
</script>

<template>
  <div class="border border-base-300 rounded-md overflow-hidden divide-y divide-base-300">
    <slot :open-panels="openPanels" :toggle="toggle" />
  </div>
</template>
