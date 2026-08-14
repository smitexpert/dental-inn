<script setup>
import { computed } from 'vue'
import PrimeInputText from 'primevue/inputtext'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
  autofocus: { type: Boolean, default: false },
  autocomplete: { type: String, default: null },
})

defineEmits(['update:modelValue', 'blur', 'focus'])

const classes = computed(() =>
  [
    'w-full h-10 px-3 rounded-md bg-surface text-foreground placeholder:text-muted-foreground',
    'focus:outline-none focus:ring-2 focus:ring-primary-500',
    'disabled:opacity-50 disabled:cursor-not-allowed',
    props.invalid ? 'border border-danger' : 'border border-border',
  ].join(' '),
)
</script>

<template>
  <PrimeInputText
    :type="type"
    :model-value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :autofocus="autofocus"
    :autocomplete="autocomplete"
    :pt="{ root: { class: classes } }"
    @update:model-value="$emit('update:modelValue', $event)"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event)"
  />
</template>
