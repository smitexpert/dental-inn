<script setup>
/**
 * Input
 * -----
 * Text input wrapped in `.input-shell` with optional left-side icon and
 * right-side unit label. Falls through HTML attributes via `$attrs` so
 * callers can pass `id`, `name`, `placeholder`, `autocomplete`, `autofocus`,
 * etc. without each one being declared.
 */
import Icon from './Icon.vue'

defineOptions({ inheritAttrs: false })

defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
  autofocus: { type: Boolean, default: false },
  autocomplete: { type: String, default: null },
  prefixIcon: { type: String, default: null },
  /** Unit label shown inside the field, e.g. "Years". */
  suffix: { type: String, default: null },
})

defineEmits(['update:modelValue', 'blur', 'focus'])
</script>

<template>
  <div class="relative">
    <span
      v-if="prefixIcon"
      class="absolute left-3 top-1/2 -translate-y-1/2 text-base-content/50 pointer-events-none"
    >
      <Icon :name="prefixIcon" size="sm" />
    </span>
    <span
      v-if="suffix"
      class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-base-content/50 pointer-events-none select-none"
    >
      {{ suffix }}
    </span>
    <input
      v-bind="$attrs"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :autofocus="autofocus"
      :autocomplete="autocomplete ?? undefined"
      :class="[
        'input-shell w-full',
        prefixIcon ? 'pl-9' : '',
        suffix ? 'pr-16' : '',
        invalid ? 'input-shell-error' : '',
      ]"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />
  </div>
</template>
