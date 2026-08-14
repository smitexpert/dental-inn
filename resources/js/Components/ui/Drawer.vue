<script setup>
import Drawer from 'primevue/drawer'

defineProps({
  modelValue: { type: Boolean, default: false },
  position: { type: String, default: 'right', validator: (v) => ['left','right','top','bottom'].includes(v) },
  title: { type: String, default: null },
})
defineEmits(['update:modelValue', 'close'])
</script>

<template>
  <Drawer
    :visible="modelValue"
    :position="position"
    :pt="{
      root: { class: 'fixed inset-0 z-50' },
      mask: { class: 'fixed inset-0 bg-black/50' },
      container: { class: 'absolute bg-surface border-border shadow-xl flex flex-col ' + (
        position === 'right' ? 'right-0 top-0 h-full w-80 border-l' :
        position === 'left'  ? 'left-0 top-0 h-full w-80 border-r' :
        position === 'top'   ? 'top-0 left-0 w-full h-80 border-b' :
                                'bottom-0 left-0 w-full h-80 border-t'
      ) },
      header: { class: 'px-4 py-3 border-b border-border' },
      title: { class: 'text-lg font-semibold text-foreground' },
      content: { class: 'p-4 flex-1 overflow-y-auto' },
    }"
    @update:visible="$emit('update:modelValue', $event)"
    @close="$emit('close')"
  >
    <template #header>
      <slot name="header">
        <h3 class="text-lg font-semibold text-foreground">{{ title }}</h3>
      </slot>
    </template>
    <slot />
  </Drawer>
</template>
