<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: null },
  icon: { type: String, default: null },
})
</script>

<template>
  <div class="flex flex-col items-center justify-center text-center p-8">
    <div v-if="icon" :class="icon" class="text-4xl text-muted-foreground mb-2" />
    <h3 class="text-base font-semibold text-foreground">{{ title }}</h3>
    <p v-if="description" class="text-sm text-muted-foreground mt-1 max-w-sm">{{ description }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>
