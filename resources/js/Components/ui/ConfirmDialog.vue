<script setup>
/**
 * ConfirmDialog
 * -------------
 * Shared confirmation dialog driven by the `useConfirm()` composable. The
 * state in `useConfirmDialog()` controls which (if any) dialog is open.
 */
import { computed } from 'vue'
import { useConfirmDialog } from '@/Composables/useConfirm.js'
import Icon from './Icon.vue'

const state = useConfirmDialog()

const iconName = computed(() => {
  if (state.value.tone === 'danger') return 'alert-triangle'
  if (state.value.tone === 'warning') return 'alert-triangle'
  if (state.value.tone === 'success') return 'check-circle'
  return 'info'
})

const iconTone = computed(() => {
  if (state.value.tone === 'danger') return 'bg-error/10 text-error'
  if (state.value.tone === 'warning') return 'bg-warning/10 text-warning'
  if (state.value.tone === 'success') return 'bg-success/10 text-success'
  return 'bg-primary/10 text-primary'
})

function accept() {
  const cb = state.value.accept
  state.value.open = false
  if (typeof cb === 'function') cb()
}

function reject() {
  const cb = state.value.reject
  state.value.open = false
  if (typeof cb === 'function') cb()
}
</script>

<template>
  <Transition
    enter-active-class="transition duration-150 ease-(--ease-out-soft)"
    leave-active-class="transition duration-100 ease-(--ease-out-soft)"
    enter-from-class="opacity-0"
    leave-to-class="opacity-0"
  >
    <div v-if="state.open" class="modal modal-open" role="dialog">
      <div class="modal-box app-card p-0 overflow-hidden">
        <div class="p-5 flex items-start gap-3">
          <div :class="['h-10 w-10 rounded-xl inline-flex items-center justify-center shrink-0', iconTone]">
            <Icon :name="iconName" size="md" />
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="font-semibold text-base text-base-content">
              {{ state.header }}
            </h3>
            <p class="text-sm text-(--color-muted) mt-1">{{ state.message }}</p>
          </div>
        </div>
        <div class="px-5 py-3 border-t border-(--color-line) bg-base-200/40 flex items-center justify-end gap-2">
          <button type="button" class="btn btn-sm" :class="state.rejectClass" @click="reject">
            {{ state.rejectLabel }}
          </button>
          <button type="button" class="btn btn-sm" :class="state.acceptClass" @click="accept">
            {{ state.acceptLabel }}
          </button>
        </div>
      </div>
      <div class="modal-backdrop bg-black/50" @click="reject" />
    </div>
  </Transition>
</template>
