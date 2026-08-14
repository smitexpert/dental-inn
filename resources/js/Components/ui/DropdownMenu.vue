<script setup>
defineProps({
  modelValue: { type: Array, required: true },
  trigger: { type: Object, default: null },
})

const emit = defineEmits(['select'])
</script>

<template>
  <div class="dropdown dropdown-end">
    <div tabindex="0" role="button">
      <slot name="trigger">
        <button type="button" class="btn btn-ghost btn-sm">⋯</button>
      </slot>
    </div>
    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-10 w-52 p-2 shadow border border-base-300">
      <li v-for="item in modelValue" :key="item.label">
        <button type="button" class="flex items-center gap-2" @click="emit('select', item)">
          <span v-if="item.icon" :class="item.icon" />
          {{ item.label }}
        </button>
      </li>
    </ul>
  </div>
</template>
