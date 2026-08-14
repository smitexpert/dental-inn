<script setup>
import { onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  position: {
    type: String,
    default: 'right',
    validator: (v) => ['left', 'right', 'top', 'bottom'].includes(v),
  },
  title: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue', 'close'])

const positionClass = {
  right: 'right-0 top-0 h-full w-80 border-l',
  left: 'left-0 top-0 h-full w-80 border-r',
  top: 'top-0 left-0 w-full h-80 border-b',
  bottom: 'bottom-0 left-0 w-full h-80 border-t',
}

function close() {
  emit('update:modelValue', false)
  emit('close')
}

function onKey(e) {
  if (e.key === 'Escape' && props.modelValue) close()
}

onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))

watch(() => props.modelValue, (v) => {
  document.body.style.overflow = v ? 'hidden' : ''
})
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-50">
      <div class="fixed inset-0 bg-black/50" @click="close" />
      <div class="absolute bg-base-100 shadow-xl flex flex-col border border-base-300" :class="positionClass[position]">
        <div class="flex items-center justify-between px-4 py-3 border-b border-base-300">
          <slot name="header">
            <h3 class="text-lg font-semibold">{{ title }}</h3>
          </slot>
          <button type="button" aria-label="Close" class="btn btn-ghost btn-sm btn-circle" @click="close">✕</button>
        </div>
        <div class="p-4 flex-1 overflow-y-auto">
          <slot />
        </div>
      </div>
    </div>
  </Teleport>
</template>
