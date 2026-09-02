<script setup>
/**
 * Drawer
 * ------
 * Side panel (left/right/top/bottom) with smooth slide-in transition and a
 * backdrop. Closes on Escape and on backdrop click. Scroll-locks the body
 * while open.
 */
import { onMounted, onUnmounted, watch } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  position: {
    type: String,
    default: 'right',
    validator: (v) => ['left', 'right', 'top', 'bottom'].includes(v),
  },
  title: { type: String, default: null },
  width: { type: String, default: 'w-96' },
})

const emit = defineEmits(['update:modelValue', 'close'])

const positionClass = {
  right: `right-0 top-0 h-full ${props.width} max-w-full border-l`,
  left: `left-0 top-0 h-full ${props.width} max-w-full border-r`,
  top: 'top-0 left-0 w-full max-h-screen border-b',
  bottom: 'bottom-0 left-0 w-full max-h-screen border-t',
}

const transitionFrom = {
  right: 'translate-x-full',
  left: '-translate-x-full',
  top: '-translate-y-full',
  bottom: 'translate-y-full',
}

function close() {
  emit('update:modelValue', false)
  emit('close')
}

function onKey(e) {
  if (e.key === 'Escape' && props.modelValue) close()
}

onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => {
  window.removeEventListener('keydown', onKey)
  document.body.style.overflow = ''
})

watch(
  () => props.modelValue,
  (v) => {
    document.body.style.overflow = v ? 'hidden' : ''
  },
)
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-150 ease-(--ease-out-soft)"
      leave-active-class="transition-opacity duration-100 ease-(--ease-out-soft)"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div v-if="modelValue" class="fixed inset-0 z-50 bg-black/50" @click="close" />
    </Transition>
    <Transition
      :enter-active-class="`transition-transform duration-200 ease-(--ease-out-soft)`"
      :leave-active-class="`transition-transform duration-150 ease-(--ease-out-soft)`"
      :enter-from-class="transitionFrom[position]"
      :leave-to-class="transitionFrom[position]"
    >
      <div
        v-if="modelValue"
        :class="['fixed z-50 bg-base-100 shadow-xl flex flex-col border-(--color-line)', positionClass[position]]"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b border-(--color-line)">
          <slot name="header">
            <h3 class="text-base font-semibold text-base-content">{{ title }}</h3>
          </slot>
          <button
            type="button"
            aria-label="Close"
            class="btn btn-ghost btn-sm btn-circle"
            @click="close"
          >
            <Icon name="x" size="sm" />
          </button>
        </div>
        <div class="p-4 flex-1 overflow-y-auto">
          <slot />
        </div>
        <div
          v-if="$slots.footer"
          class="px-4 py-3 border-t border-(--color-line) bg-base-200/40 flex items-center justify-end gap-2"
        >
          <slot name="footer" />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
