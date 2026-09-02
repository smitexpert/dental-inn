<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  // "YYYY-MM-DDTHH:mm" — same wire format as <input type="datetime-local">,
  // so the surrounding form keeps working without any change to its
  // submit payload.
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Pick a date & time' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
  min: { type: String, default: null },
  max: { type: String, default: null },
  // When provided, the picker fetches available slots from the
  // /appointments/slots/{doctorId} endpoint for the currently-selected
  // day so the user sees when the doctor is actually free.
  doctorId: { type: [Number, String, null], default: null },
  durationMinutes: { type: Number, default: 30 },
})

const emit = defineEmits(['update:modelValue', 'change'])

// ────────────────────────────────────────────────────────────────────
// State
// ────────────────────────────────────────────────────────────────────
const open = ref(false)
const triggerRef = ref(null)
const panelRef = ref(null)

const today = new Date()
const initialDate = parseValue(props.modelValue) ?? today
const viewYear = ref(initialDate.getFullYear())
const viewMonth = ref(initialDate.getMonth()) // 0-indexed
const selectedDate = ref(parseValue(props.modelValue) ? initialDate : null)
const hours = ref(initialDate.getHours().toString().padStart(2, '0'))
const minutes = ref(roundMinutes(initialDate.getMinutes()))

// Slots fetched from the backend for the currently-selected doctor+date.
const slots = ref([])
const loadingSlots = ref(false)
let slotsAbort = null

// ────────────────────────────────────────────────────────────────────
// Parsing helpers
// ────────────────────────────────────────────────────────────────────
function parseValue(v) {
  if (!v) return null
  // Accept "YYYY-MM-DDTHH:mm" or "YYYY-MM-DDTHH:mm:ss" or ISO with offset.
  const m = /^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/.exec(v)
  if (!m) return null
  const d = new Date(
    Number(m[1]),
    Number(m[2]) - 1,
    Number(m[3]),
    Number(m[4] ?? 0),
    Number(m[5] ?? 0),
    0,
    0,
  )
  return Number.isNaN(d.getTime()) ? null : d
}

function toWire(d) {
  if (!d) return ''
  const pad = (n) => n.toString().padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function roundMinutes(m) {
  // Snap to nearest 5 min so the minute picker stays predictable.
  const snapped = Math.round(m / 5) * 5
  return snapped.toString().padStart(2, '0')
}

function fmtSummary(d) {
  if (!d) return ''
  return d.toLocaleString(undefined, {
    weekday: 'short',
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

// ────────────────────────────────────────────────────────────────────
// Reactive model → external sync
// ────────────────────────────────────────────────────────────────────
watch(
  () => props.modelValue,
  (v) => {
    const d = parseValue(v)
    if (!d) return
    selectedDate.value = d
    viewYear.value = d.getFullYear()
    viewMonth.value = d.getMonth()
    hours.value = d.getHours().toString().padStart(2, '0')
    minutes.value = roundMinutes(d.getMinutes())
  },
)

function commit() {
  if (!selectedDate.value) {
    emit('update:modelValue', '')
    emit('change', '')
    return
  }
  const d = new Date(selectedDate.value)
  const h = clamp(Number(hours.value), 0, 23)
  const m = clamp(Number(minutes.value), 0, 59)
  d.setHours(h, m, 0, 0)
  selectedDate.value = d
  const v = toWire(d)
  emit('update:modelValue', v)
  emit('change', v)
}

function clamp(n, lo, hi) {
  if (Number.isNaN(n)) return lo
  return Math.min(hi, Math.max(lo, n))
}

// ────────────────────────────────────────────────────────────────────
// Calendar grid
// ────────────────────────────────────────────────────────────────────
const monthLabel = computed(() =>
  new Date(viewYear.value, viewMonth.value, 1).toLocaleString(undefined, {
    month: 'long',
    year: 'numeric',
  }),
)

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

const calendarDays = computed(() => {
  const first = new Date(viewYear.value, viewMonth.value, 1)
  // Monday-first: shift Sunday (0) to 6, others down by one.
  const startOffset = (first.getDay() + 6) % 7
  const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate()
  const daysInPrev = new Date(viewYear.value, viewMonth.value, 0).getDate()

  const cells = []
  for (let i = startOffset - 1; i >= 0; i--) {
    cells.push({ day: daysInPrev - i, muted: true, date: new Date(viewYear.value, viewMonth.value - 1, daysInPrev - i) })
  }
  for (let d = 1; d <= daysInMonth; d++) {
    cells.push({ day: d, muted: false, date: new Date(viewYear.value, viewMonth.value, d) })
  }
  while (cells.length % 7 !== 0 || cells.length < 42) {
    const last = cells[cells.length - 1].date
    const next = new Date(last)
    next.setDate(last.getDate() + 1)
    cells.push({ day: next.getDate(), muted: true, date: next })
  }
  return cells
})

function isSameDay(a, b) {
  if (!a || !b) return false
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  )
}

function prevMonth() {
  if (viewMonth.value === 0) {
    viewMonth.value = 11
    viewYear.value -= 1
  } else {
    viewMonth.value -= 1
  }
}

function nextMonth() {
  if (viewMonth.value === 11) {
    viewMonth.value = 0
    viewYear.value += 1
  } else {
    viewMonth.value += 1
  }
}

function pickDay(date) {
  selectedDate.value = new Date(date)
  if (date.getMonth() !== viewMonth.value) {
    viewMonth.value = date.getMonth()
    viewYear.value = date.getFullYear()
  }
  commit()
}

function pickToday() {
  const t = new Date()
  viewYear.value = t.getFullYear()
  viewMonth.value = t.getMonth()
  pickDay(t)
}

function clearSelection() {
  selectedDate.value = null
  emit('update:modelValue', '')
  emit('change', '')
}

// ────────────────────────────────────────────────────────────────────
// Available slots fetch
// ────────────────────────────────────────────────────────────────────
async function loadSlots() {
  if (!props.doctorId || !selectedDate.value) {
    slots.value = []
    return
  }
  const pad = (n) => n.toString().padStart(2, '0')
  const dateStr = `${selectedDate.value.getFullYear()}-${pad(selectedDate.value.getMonth() + 1)}-${pad(selectedDate.value.getDate())}`
  loadingSlots.value = true
  if (slotsAbort) slotsAbort.abort()
  slotsAbort = new AbortController()
  try {
    const res = await fetch(
      `/appointments/slots/${props.doctorId}?date=${dateStr}&duration_minutes=${props.durationMinutes}`,
      { headers: { Accept: 'application/json' }, signal: slotsAbort.signal },
    )
    const data = await res.json()
    slots.value = data.slots || []
  } catch (e) {
    if (e?.name !== 'AbortError') slots.value = []
  }
  loadingSlots.value = false
}

watch(selectedDate, loadSlots)
watch(() => props.doctorId, loadSlots)
watch(() => props.durationMinutes, loadSlots)

function pickSlot(iso) {
  const d = parseValue(iso)
  if (!d) return
  selectedDate.value = d
  hours.value = d.getHours().toString().padStart(2, '0')
  minutes.value = roundMinutes(d.getMinutes())
  commit()
}

function fmtSlot(iso) {
  const d = parseValue(iso)
  if (!d) return ''
  return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
}

function isSlotSelected(iso) {
  if (!selectedDate.value) return false
  const d = parseValue(iso)
  if (!d) return false
  return (
    d.getHours() === selectedDate.value.getHours() &&
    d.getMinutes() === selectedDate.value.getMinutes() &&
    d.getDate() === selectedDate.value.getDate()
  )
}

// ────────────────────────────────────────────────────────────────────
// Open/close behavior
// ────────────────────────────────────────────────────────────────────
async function toggle() {
  if (props.disabled) return
  open.value = !open.value
  if (open.value) {
    await nextTick()
    panelRef.value?.focus()
    loadSlots()
  }
}

function onBackdropClick(e) {
  if (e.target === e.currentTarget) open.value = false
}

function onKeydown(e) {
  if (e.key === 'Escape') open.value = false
  if (!selectedDate.value) return
  const d = new Date(selectedDate.value)
  if (e.key === 'ArrowLeft') d.setDate(d.getDate() - 1)
  else if (e.key === 'ArrowRight') d.setDate(d.getDate() + 1)
  else if (e.key === 'ArrowUp') d.setDate(d.getDate() - 7)
  else if (e.key === 'ArrowDown') d.setDate(d.getDate() + 7)
  else if (e.key === 'Enter') {
    e.preventDefault()
    pickDay(d)
    return
  } else return
  e.preventDefault()
  viewYear.value = d.getFullYear()
  viewMonth.value = d.getMonth()
  selectedDate.value = d
  commit()
}

function onWindowClick(e) {
  if (!open.value) return
  const root = triggerRef.value?.$el ?? triggerRef.value
  if (root && !root.contains(e.target) && !panelRef.value?.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', onWindowClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', onWindowClick))
</script>

<template>
  <div class="relative w-full">
    <!-- Trigger pill -->
    <button
      ref="triggerRef"
      type="button"
      :disabled="disabled"
      class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-base-300 bg-base-100 px-3 text-sm hover:border-base-content/40 focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50"
      :class="invalid ? 'border-error' : ''"
      @click="toggle"
    >
      <span class="flex items-center gap-2 truncate text-left">
        <svg class="h-4 w-4 shrink-0 text-base-content/60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
        </svg>
        <span :class="modelValue ? 'text-base-content' : 'text-base-content/50'">
          {{ modelValue ? fmtSummary(selectedDate) : placeholder }}
        </span>
      </span>
      <span class="flex items-center gap-2">
        <button
          v-if="modelValue"
          type="button"
          class="rounded p-1 text-base-content/50 hover:bg-base-200 hover:text-base-content"
          aria-label="Clear"
          @click.stop="clearSelection"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>
        </button>
        <svg class="h-4 w-4 shrink-0 text-base-content/40" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
      </span>
    </button>

    <!-- Panel -->
    <div
      v-if="open"
      ref="panelRef"
      tabindex="-1"
      class="absolute left-0 z-50 mt-2 w-[20rem] rounded-lg border border-base-300 bg-base-100 p-3 shadow-xl outline-none"
      role="dialog"
      aria-label="Pick date and time"
      @keydown="onKeydown"
    >
      <!-- Header / month nav -->
      <div class="mb-2 flex items-center justify-between">
        <button
          type="button"
          class="rounded-md p-1.5 text-base-content/70 hover:bg-base-200"
          aria-label="Previous month"
          @click="prevMonth"
        >
          <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.06 10l3.73 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" /></svg>
        </button>
        <div class="text-sm font-semibold text-base-content">{{ monthLabel }}</div>
        <button
          type="button"
          class="rounded-md p-1.5 text-base-content/70 hover:bg-base-200"
          aria-label="Next month"
          @click="nextMonth"
        >
          <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
        </button>
      </div>

      <!-- Weekday header -->
      <div class="mb-1 grid grid-cols-7 text-center text-[0.65rem] font-semibold uppercase tracking-wider text-base-content/50">
        <div v-for="w in weekdays" :key="w">{{ w }}</div>
      </div>

      <!-- Day grid -->
      <div class="grid grid-cols-7 gap-0.5">
        <button
          v-for="(cell, idx) in calendarDays"
          :key="idx"
          type="button"
          class="relative h-9 rounded-md text-sm transition-colors"
          :class="[
            cell.muted ? 'text-base-content/30' : 'text-base-content',
            isSameDay(cell.date, selectedDate)
              ? 'bg-primary text-primary-content font-semibold hover:bg-primary'
              : 'hover:bg-base-200',
            isSameDay(cell.date, today) && !isSameDay(cell.date, selectedDate)
              ? 'ring-1 ring-primary/40'
              : '',
          ]"
          @click="pickDay(cell.date)"
        >
          {{ cell.day }}
        </button>
      </div>

      <div class="mt-3 flex items-center justify-between">
        <button
          type="button"
          class="text-xs font-medium text-primary hover:underline"
          @click="pickToday"
        >
          Today
        </button>
        <div class="text-xs text-base-content/60">
          <template v-if="selectedDate">
            {{ selectedDate.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'short' }) }}
          </template>
          <template v-else>No date selected</template>
        </div>
      </div>

      <!-- Time -->
      <div class="mt-3 border-t border-base-200 pt-3">
        <div class="mb-2 flex items-center justify-between">
          <div class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Time</div>
          <div class="flex items-center gap-1">
            <input
              v-model="hours"
              type="number"
              min="0"
              max="23"
              class="h-8 w-14 rounded-md border border-base-300 bg-base-100 px-2 text-center text-sm"
              aria-label="Hour"
              @change="commit"
            />
            <span class="text-base-content/50">:</span>
            <input
              v-model="minutes"
              type="number"
              min="0"
              max="59"
              step="5"
              class="h-8 w-14 rounded-md border border-base-300 bg-base-100 px-2 text-center text-sm"
              aria-label="Minute"
              @change="commit"
            />
          </div>
        </div>

        <!-- Available slots (when a doctor is selected) -->
        <div v-if="doctorId" class="mt-2">
          <div v-if="loadingSlots" class="text-xs text-base-content/60">Loading availability…</div>
          <div v-else-if="slots.length === 0 && selectedDate" class="text-xs text-base-content/60">No available slots.</div>
          <div v-else-if="slots.length > 0" class="flex flex-wrap gap-1.5">
            <button
              v-for="s in slots"
              :key="s"
              type="button"
              class="rounded-md border border-base-300 bg-base-100 px-2.5 py-1 text-xs text-base-content hover:border-primary hover:bg-primary/10"
              :class="{
                'border-primary bg-primary text-primary-content hover:bg-primary': isSlotSelected(s),
              }"
              @click="pickSlot(s)"
            >
              {{ fmtSlot(s) }}
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-3 flex items-center justify-end gap-2 border-t border-base-200 pt-3">
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-xs text-base-content/70 hover:bg-base-200"
          @click="open = false"
        >
          Close
        </button>
        <button
          type="button"
          class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-content hover:bg-primary/90 disabled:opacity-50"
          :disabled="!selectedDate"
          @click="() => { commit(); open = false }"
        >
          Done
        </button>
      </div>
    </div>
  </div>
</template>
