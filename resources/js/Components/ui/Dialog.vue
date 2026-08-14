<script setup>
import Dialog from 'primevue/dialog'

defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: null },
  modal: { type: Boolean, default: true },
  closable: { type: Boolean, default: true },
  size: { type: String, default: 'md', validator: (v) => ['sm','md','lg','xl'].includes(v) },
})
defineEmits(['update:modelValue', 'close'])

const sizeClass = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-2xl' }
</script>

<template>
  <Dialog
    :visible="modelValue"
    :modal="modal"
    :closable="closable"
    :pt="{
      root: { class: 'fixed inset-0 z-50 flex items-center justify-center p-4' },
      mask: { class: 'fixed inset-0 bg-black/50' },
      container: { class: 'relative bg-surface border border-border rounded-lg shadow-xl w-full ' + sizeClass[size] },
      header: { class: 'flex items-center justify-between px-4 py-3 border-b border-border' },
      title: { class: 'text-lg font-semibold text-foreground' },
      content: { class: 'p-4 text-foreground' },
      footer: { class: 'px-4 py-3 border-t border-border flex justify-end gap-2' },
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
    <template #footer>
      <slot name="footer" />
    </template>
  </Dialog>
</template>
