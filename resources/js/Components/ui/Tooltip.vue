<script setup>
import { ref } from 'vue'

const visible = ref(false)
function show() {
  visible.value = true
}
function hide() {
  visible.value = false
}
</script>

<template>
  <span class="relative inline-block" @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
    <slot />
    <Transition
      enter-active-class="transition-opacity"
      leave-active-class="transition-opacity"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <span
        v-if="visible"
        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs text-white bg-foreground rounded shadow whitespace-nowrap z-50"
        role="tooltip"
      >
        <slot name="content" />
      </span>
    </Transition>
  </span>
</template>
