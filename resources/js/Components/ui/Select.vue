<script setup>
defineProps({
  modelValue: { type: [String, Number, null], default: null },
  options: { type: Array, required: true },
  optionLabel: { type: [String, Function], default: 'label' },
  optionValue: { type: [String, Function], default: 'value' },
  placeholder: { type: String, default: 'Select…' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'change'])

function get(opt, key) {
  if (typeof key === 'function') return key(opt)
  return opt[key]
}
</script>

<template>
  <select
    class="select select-bordered w-full"
    :class="invalid ? 'select-error' : ''"
    :disabled="disabled"
    :value="modelValue ?? ''"
    @change="(e) => {
      const v = e.target.value
      emit('update:modelValue', v === '' ? null : v)
      emit('change', v)
    }"
  >
    <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
    <option v-for="opt in options" :key="String(get(opt, optionValue))" :value="get(opt, optionValue)">
      {{ get(opt, optionLabel) }}
    </option>
  </select>
</template>
