<script setup>
import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: null },
  modal: { type: Boolean, default: true },
  closable: { type: Boolean, default: true },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v) },
})

const emit = defineEmits(['update:modelValue', 'close'])

const sizeClass = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-2xl' }
const dialogRef = ref(null)

watch(() => props.modelValue, (val) => {
  const el = dialogRef.value
  if (!el) return
  if (val && !el.open) el.showModal()
  if (!val && el.open) el.close()
}, { immediate: true })

function close() {
  emit('update:modelValue', false)
  emit('close')
}

onUnmounted(() => {
  if (dialogRef.value?.open) dialogRef.value.close()
})
</script>

<template>
  <dialog ref="dialogRef" class="modal" @close="close">
    <div class="modal-box" :class="sizeClass[size]">
      <div class="flex items-center justify-between mb-4">
        <slot name="header">
          <h3 class="font-semibold text-lg">{{ title }}</h3>
        </slot>
        <button v-if="closable" type="button" aria-label="Close" class="btn btn-ghost btn-sm btn-circle" @click="close">�</button>
      </div>
      <div>
        <slot />
      </div>
      <div v-if="$slots.footer" class="modal-action">
        <slot name="footer" />
      </div>
    </div>
    <form v-if="modal" method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  </dialog>
</template>
