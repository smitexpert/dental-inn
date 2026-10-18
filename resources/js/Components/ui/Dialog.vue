<script setup>
/**
 * Dialog
 * ------
 * Modal dialog built on the native `<dialog>` element with a backdrop and
 * optional close affordance. Supports both `v-model` and `v-model:visible`
 * for compatibility with Inertia/Vue component patterns. Size variants:
 * sm / md / lg / xl. Closes on Escape automatically and on backdrop click.
 */
import { ref, computed, watch, onUnmounted } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  visible: { type: Boolean, default: null },
  title: { type: String, default: null },
  icon: { type: String, default: null },
  closable: { type: Boolean, default: true },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
  },
  closeOnBackdrop: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'update:visible', 'close'])

const isOpen = computed({
  get() {
    return props.visible ?? props.modelValue
  },
  set(v) {
    emit('update:modelValue', v)
    emit('update:visible', v)
  },
})

const dialogRef = ref(null)

const sizeClass = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-2xl',
}

watch(
  isOpen,
  (val) => {
    const el = dialogRef.value
    if (!el) return
    if (val && !el.open) el.showModal()
    if (!val && el.open) el.close()
  },
  { immediate: true },
)

function close() {
  if (!props.closable) return
  isOpen.value = false
  emit('close')
}

function onBackdropClick(e) {
  if (!props.closeOnBackdrop) return
  if (e.target === dialogRef.value) close()
}

onUnmounted(() => {
  if (dialogRef.value?.open) dialogRef.value.close()
})
</script>

<template>
  <Transition
    enter-active-class="transition duration-150 ease-(--ease-out-soft)"
    leave-active-class="transition duration-100 ease-(--ease-out-soft)"
    enter-from-class="opacity-0"
    leave-to-class="opacity-0"
  >
    <dialog
      v-if="isOpen"
      ref="dialogRef"
      class="modal modal-open backdrop:bg-black/50"
      @close="close"
      @click="onBackdropClick"
    >
      <div :class="['modal-box app-card p-0 overflow-hidden flex flex-col max-h-[90vh]', sizeClass[size]]">
        <div class="px-5 py-4 border-b border-(--color-line) flex items-center gap-3 shrink-0">
          <div
            v-if="icon"
            class="h-9 w-9 rounded-xl bg-primary/10 text-primary inline-flex items-center justify-center shrink-0"
          >
            <Icon :name="icon" size="md" />
          </div>
          <div class="min-w-0 flex-1">
            <slot name="header">
              <h3 class="font-semibold text-base text-base-content">{{ title }}</h3>
            </slot>
          </div>
          <button
            v-if="closable"
            type="button"
            aria-label="Close"
            class="btn btn-ghost btn-sm btn-circle"
            @click="close"
          >
            <Icon name="x" size="sm" />
          </button>
        </div>
        <div class="px-5 py-4 overflow-y-auto flex-1 min-h-0">
          <slot />
        </div>
        <div
          v-if="$slots.footer"
          class="px-5 py-3 border-t border-(--color-line) bg-base-200/40 flex items-center justify-end gap-2 shrink-0"
        >
          <slot name="footer" />
        </div>
      </div>
    </dialog>
  </Transition>
</template>
