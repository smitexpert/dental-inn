<script setup>
import Menu from 'primevue/menu'

defineProps({
  modelValue: { type: Array, required: true },
  trigger: { type: Object, default: null },
})
const emit = defineEmits(['select'])

const pt = {
  root: { class: 'min-w-[12rem] bg-surface border border-border rounded-md shadow-md py-1' },
  menu: { class: 'flex flex-col gap-0 list-none m-0 p-0' },
  item: { class: 'px-3 py-2 text-sm text-foreground hover:bg-surface-muted cursor-pointer flex items-center gap-2' },
  separator: { class: 'my-1 border-t border-border' },
}
</script>

<template>
  <Menu
    :model="modelValue"
    :popup="true"
    :pt="pt"
    @item-select="(e) => emit('select', e.item)"
  >
    <template #itemicon="{ item }">
      <span v-if="item.icon" :class="item.icon" />
    </template>
  </Menu>
</template>
