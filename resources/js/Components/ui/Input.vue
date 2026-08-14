<script setup>
defineProps({
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
</script>

<template>
  <input
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :autofocus="autofocus"
    :autocomplete="autocomplete ?? undefined"
    class="input input-bordered w-full"
    :class="invalid ? 'input-error' : ''"
    @input="$emit('update:modelValue', $event.target.value)"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event)"
  />
</template>
