<script setup>
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'

defineProps({ modelValue: { type: String, default: null } })
defineEmits(['update:modelValue'])

const pt = {
  tabList: { class: 'flex border-b border-border gap-1' },
  tab: ({ context }) => [
    'px-4 py-2 text-sm font-medium cursor-pointer transition-colors border-b-2 -mb-px',
    context.active ? 'border-primary-600 text-primary-700' : 'border-transparent text-muted-foreground hover:text-foreground',
  ],
  tabPanels: { class: 'pt-4' },
}
</script>

<template>
  <Tabs :value="modelValue" @update:value="$emit('update:modelValue', $event)">
    <TabList :pt="pt.tabList">
      <Tab v-for="t in $slots.default ? [] : []" :key="t" :value="t" />
    </TabList>
    <slot />
  </Tabs>
</template>
