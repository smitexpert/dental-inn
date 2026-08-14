<script setup>
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'

defineProps({
  modelValue: { type: [String, Array], default: null },
  multiple: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])

const pt = {
  root: { class: 'border border-border rounded-md overflow-hidden divide-y divide-border' },
  header: { class: 'bg-surface hover:bg-surface-muted' },
  headerContent: { class: 'px-4 py-3 font-medium text-foreground' },
  content: { class: 'px-4 py-3 text-foreground bg-surface' },
}
</script>

<template>
  <Accordion
    :value="modelValue"
    :multiple="multiple"
    :pt="pt"
    @update:value="$emit('update:modelValue', $event)"
  >
    <slot />
  </Accordion>
</template>
