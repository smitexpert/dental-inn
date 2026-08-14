<script setup>
defineProps({
  title: { type: String, default: null },
  padded: { type: Boolean, default: true },
})
</script>

<template>
  <div class="bg-base-100 border border-base-300 rounded-lg shadow-sm">
    <div
      v-if="title || $slots.header"
      class="px-4 py-3 border-b border-base-300 flex items-center justify-between"
    >
      <slot name="header">
        <h3 class="text-base font-semibold text-base-content">{{ title }}</h3>
      </slot>
    </div>
    <div :class="padded ? 'p-4' : ''">
      <slot />
    </div>
    <div v-if="$slots.footer" class="px-4 py-3 border-t border-base-300 bg-base-200">
      <slot name="footer" />
    </div>
  </div>
</template>
