<script setup>
// DaisyUI confirmation dialog. Driven by useConfirm().require() in pages;
// reads shared state from useConfirmDialog() composable.
import { useConfirmDialog } from '@/Composables/useConfirm.js'

const state = useConfirmDialog()

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
  <div v-if="state.open" class="modal modal-open" role="dialog">
    <div class="modal-box">
      <h3 class="font-semibold text-lg">{{ state.header }}</h3>
      <p class="py-4">{{ state.message }}</p>
      <div class="modal-action">
        <button type="button" class="btn" :class="state.rejectClass" @click="reject">
          {{ state.rejectLabel }}
        </button>
        <button type="button" class="btn" :class="state.acceptClass" @click="accept">
          {{ state.acceptLabel }}
        </button>
      </div>
    </div>
    <div class="modal-backdrop" @click="reject" />
  </div>
</template>
