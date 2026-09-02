<script setup>
/**
 * Select
 * ------
 * Native `<select>` styled with `.input-shell` and a chevron indicator.
 * Accepts options in the simplified `{ label, value }` shape. The richer
 * `optionLabel` / `optionValue` accessors used elsewhere still work because
 * we read the prop names as strings and look them up.
 */
import Icon from './Icon.vue'

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
  <div class="relative">
    <select
      :value="modelValue ?? ''"
      :disabled="disabled"
      :class="['input-shell w-full pr-9 appearance-none cursor-pointer', invalid ? 'input-shell-error' : '']"
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
    <span
      class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-base-content/50"
      aria-hidden="true"
    >
      <Icon name="chevron-down" size="sm" />
    </span>
  </div>
</template>
