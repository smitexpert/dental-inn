<script setup>
import UiSelect from '@/Components/ui/Select.vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

// Universal numbering system (1-32) with quadrant grouping
const UPPER_RIGHT = [1, 2, 3, 4, 5, 6, 7, 8]
const UPPER_LEFT = [9, 10, 11, 12, 13, 14, 15, 16]
const LOWER_LEFT = [17, 18, 19, 20, 21, 22, 23, 24]
const LOWER_RIGHT = [25, 26, 27, 28, 29, 30, 31, 32]

const STATUS_OPTIONS = [
  { label: 'Healthy', value: 'healthy' },
  { label: 'Caries', value: 'caries' },
  { label: 'Filled', value: 'filled' },
  { label: 'Crown', value: 'crown' },
  { label: 'Missing', value: 'missing' },
  { label: 'Extraction needed', value: 'extraction_needed' },
]

const CYCLE = [null, 'caries', 'filled', 'crown', 'missing', 'extraction_needed', 'healthy']

const STATUS_COLOR = {
  healthy: 'bg-success/20 border-success text-success',
  caries: 'bg-error/20 border-error text-error',
  filled: 'bg-primary/20 border-primary text-primary',
  crown: 'bg-warning/20 border-warning text-warning',
  missing: 'bg-base-200 border-base-300 text-base-content/70 line-through',
  extraction_needed: 'bg-error/30 border-error text-error',
}

function getStatus(num) {
  return props.modelValue[String(num)] ?? null
}

function setStatus(num, status) {
  const next = { ...props.modelValue }
  if (status === null || status === undefined || status === '') {
    delete next[String(num)]
  } else {
    next[String(num)] = status
  }
  emit('update:modelValue', next)
}

function cycleStatus(num) {
  const cur = getStatus(num)
  const idx = CYCLE.indexOf(cur)
  const nextStatus = CYCLE[(idx + 1) % CYCLE.length]
  setStatus(num, nextStatus)
}

function clearAll() {
  emit('update:modelValue', {})
}

function toothClasses(num) {
  const s = getStatus(num)
  return s
    ? STATUS_COLOR[s] || 'bg-base-200 border-base-300 text-base-content'
    : 'bg-base-100 border-base-300 text-base-content hover:bg-base-200'
}
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between text-xs text-base-content/70">
      <span>Click a tooth to set its status. {{ Object.keys(modelValue).length }} marked.</span>
      <button
        type="button"
        class="text-primary hover:text-primary disabled:opacity-50"
        :disabled="Object.keys(modelValue).length === 0"
        @click="clearAll"
      >
        Clear all
      </button>
    </div>

    <div class="rounded-md border border-base-300 bg-base-200 p-3 space-y-2">
      <div class="flex items-center justify-center gap-2">
        <div class="flex-1 grid grid-cols-8 gap-1 justify-items-center" dir="rtl">
          <button
            v-for="n in UPPER_RIGHT"
            :key="`ur-${n}`"
            type="button"
            :title="`Tooth ${n}`"
            :class="['h-9 w-9 rounded-md border text-xs font-mono', toothClasses(n)]"
            @click="cycleStatus(n)"
          >
            {{ n }}
          </button>
        </div>
        <div class="flex-1 grid grid-cols-8 gap-1 justify-items-center">
          <button
            v-for="n in UPPER_LEFT"
            :key="`ul-${n}`"
            type="button"
            :title="`Tooth ${n}`"
            :class="['h-9 w-9 rounded-md border text-xs font-mono', toothClasses(n)]"
            @click="cycleStatus(n)"
          >
            {{ n }}
          </button>
        </div>
      </div>

      <div class="border-t border-base-300"></div>

      <div class="flex items-center justify-center gap-2">
        <div class="flex-1 grid grid-cols-8 gap-1 justify-items-center" dir="rtl">
          <button
            v-for="n in LOWER_RIGHT"
            :key="`lr-${n}`"
            type="button"
            :title="`Tooth ${n}`"
            :class="['h-9 w-9 rounded-md border text-xs font-mono', toothClasses(n)]"
            @click="cycleStatus(n)"
          >
            {{ n }}
          </button>
        </div>
        <div class="flex-1 grid grid-cols-8 gap-1 justify-items-center">
          <button
            v-for="n in LOWER_LEFT"
            :key="`ll-${n}`"
            type="button"
            :title="`Tooth ${n}`"
            :class="['h-9 w-9 rounded-md border text-xs font-mono', toothClasses(n)]"
            @click="cycleStatus(n)"
          >
            {{ n }}
          </button>
        </div>
      </div>
    </div>

    <details v-if="Object.keys(modelValue).length > 0" class="text-xs">
      <summary class="cursor-pointer text-base-content/70">Per-tooth detail</summary>
      <div class="mt-2 grid grid-cols-2 gap-2">
        <div
          v-for="(s, num) in modelValue"
          :key="num"
          class="flex items-center gap-2"
        >
          <span class="font-mono w-6 text-base-content/70">#{{ num }}</span>
          <UiSelect
            :model-value="s"
            :options="[{ label: '—', value: '' }, ...STATUS_OPTIONS]"
            @update:model-value="setStatus(Number(num), $event)"
          />
        </div>
      </div>
    </details>
  </div>
</template>