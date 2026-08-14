<script setup>
defineProps({
  modelValue: { type: String, default: null },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div role="tablist" class="tabs tabs-bordered">
    <slot />
  </div>
</template>
