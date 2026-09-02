<script setup>
/**
 * ToothChartPicker
 * ----------------
 * Two-way-bound tooth chart that emits the structured shape the backend
 * expects:
 *
 *   {
 *     teeth: [
 *       { fdi: <int>, procedures: [{ code, name, surface? }] }
 *     ]
 *   }
 *
 * Click a tooth to open the procedure dialog; click a marked tooth to edit
 * or remove its procedures. Surfaces are only relevant when the chosen
 * procedure has them in its surfaces list.
 */
import { computed, ref } from 'vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiDialog from '@/Components/ui/Dialog.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import { DENTAL_PROCEDURES, TOOTH_SURFACES, findProcedure } from '@/lib/dentalProcedures.js'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ teeth: [] }),
  },
})
const emit = defineEmits(['update:modelValue'])

// FDI Universal — 4 quadrants of 8 teeth each. Right (patient's right) on
// the viewer's left per dental convention.
const UPPER_RIGHT = [18, 17, 16, 15, 14, 13, 12, 11]
const UPPER_LEFT = [21, 22, 23, 24, 25, 26, 27, 28]
const LOWER_LEFT = [38, 37, 36, 35, 34, 33, 32, 31]
const LOWER_RIGHT = [48, 47, 46, 45, 44, 43, 42, 41]

const PROCEDURE_OPTIONS = DENTAL_PROCEDURES.map((p) => ({
  label: `${p.code} — ${p.label}`,
  value: p.code,
}))
const SURFACE_OPTIONS = TOOTH_SURFACES

function readTeeth() {
  const t = props.modelValue?.teeth
  return Array.isArray(t) ? t : []
}

const teeth = computed(() => readTeeth())

function findEntry(fdi) {
  return teeth.value.find((t) => t.fdi === fdi) ?? null
}

function isMarked(fdi) {
  const entry = findEntry(fdi)
  return !!entry && (entry.procedures ?? []).length > 0
}

function writeTeeth(next) {
  emit('update:modelValue', { teeth: next })
}

const dialogOpen = ref(false)
const editingFdi = ref(null)
const draftProcedures = ref([])

function openFor(fdi) {
  editingFdi.value = fdi
  const existing = findEntry(fdi)
  draftProcedures.value = existing
    ? (existing.procedures ?? []).map((p) => ({ ...p }))
    : [defaultProcedure()]
  dialogOpen.value = true
}

function defaultProcedure() {
  return { code: 'D0140', name: 'Limited oral evaluation', surface: null }
}

function addProcedure() {
  draftProcedures.value.push(defaultProcedure())
}

function removeProcedure(idx) {
  draftProcedures.value.splice(idx, 1)
  if (draftProcedures.value.length === 0) {
    draftProcedures.value.push(defaultProcedure())
  }
}

function onCodeChange(idx, code) {
  const def = findProcedure(code)
  const next = { ...draftProcedures.value[idx], code }
  if (def) {
    next.name = def.label
    // Drop surface if the new procedure doesn't take one.
    if (!def.surfaces || def.surfaces.length === 0) {
      next.surface = null
    } else if (next.surface && !def.surfaces.includes(next.surface)) {
      next.surface = def.surfaces[0]
    }
  }
  draftProcedures.value[idx] = next
}

function surfaceOptionsFor(procedureCode) {
  const def = findProcedure(procedureCode)
  if (!def || !def.surfaces || def.surfaces.length === 0) return []
  return SURFACE_OPTIONS.filter((s) => def.surfaces.includes(s.value))
}

function saveDialog() {
  const fdi = editingFdi.value
  if (fdi == null) return
  const procedures = draftProcedures.value
    .filter((p) => p && p.code)
    .map((p) => {
      const def = findProcedure(p.code)
      const out = { code: p.code, name: def ? def.label : (p.name || '') }
      if (p.surface) out.surface = p.surface
      return out
    })
  const next = teeth.value.filter((t) => t.fdi !== fdi)
  if (procedures.length > 0) {
    next.push({ fdi, procedures })
  }
  next.sort((a, b) => a.fdi - b.fdi)
  writeTeeth(next)
  dialogOpen.value = false
}

function removeTooth(fdi) {
  writeTeeth(teeth.value.filter((t) => t.fdi !== fdi))
}

function clearAll() {
  writeTeeth([])
  dialogOpen.value = false
}

function procedureSummary(fdi) {
  const entry = findEntry(fdi)
  if (!entry) return ''
  const procs = entry.procedures ?? []
  if (procs.length === 0) return ''
  return procs
    .map((p) => (p.surface ? `${p.code} (${p.surface})` : p.code))
    .join(', ')
}

function toothColor(fdi) {
  return isMarked(fdi)
    ? 'bg-primary/15 border-primary text-primary'
    : 'bg-base-100 border-base-300 text-base-content hover:bg-base-200'
}
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between text-xs text-base-content/70">
      <span>Click a tooth to set its procedures. {{ teeth.length }} marked.</span>
      <button
        type="button"
        class="text-primary hover:text-primary disabled:opacity-50"
        :disabled="teeth.length === 0"
        @click="clearAll"
      >
        Clear all
      </button>
    </div>

    <div class="rounded-md border border-base-300 bg-base-200 p-3 space-y-2">
      <div class="flex items-center justify-center gap-2">
        <div class="flex-1 grid grid-cols-8 gap-1 justify-items-center" dir="rtl">
          <button
            v-for="fdi in UPPER_RIGHT"
            :key="`ur-${fdi}`"
            type="button"
            :title="`Tooth ${fdi} — ${procedureSummary(fdi) || 'unmarked'}`"
            :class="['h-9 w-9 rounded-md border text-xs font-mono', toothColor(fdi)]"
            @click="openFor(fdi)"
          >
            {{ fdi }}
          </button>
        </div>
        <div class="flex-1 grid grid-cols-8 gap-1 justify-items-center">
          <button
            v-for="fdi in UPPER_LEFT"
            :key="`ul-${fdi}`"
            type="button"
            :title="`Tooth ${fdi} — ${procedureSummary(fdi) || 'unmarked'}`"
            :class="['h-9 w-9 rounded-md border text-xs font-mono', toothColor(fdi)]"
            @click="openFor(fdi)"
          >
            {{ fdi }}
          </button>
        </div>
      </div>

      <div class="border-t border-base-300"></div>

      <div class="flex items-center justify-center gap-2">
        <div class="flex-1 grid grid-cols-8 gap-1 justify-items-center" dir="rtl">
          <button
            v-for="fdi in LOWER_RIGHT"
            :key="`lr-${fdi}`"
            type="button"
            :title="`Tooth ${fdi} — ${procedureSummary(fdi) || 'unmarked'}`"
            :class="['h-9 w-9 rounded-md border text-xs font-mono', toothColor(fdi)]"
            @click="openFor(fdi)"
          >
            {{ fdi }}
          </button>
        </div>
        <div class="flex-1 grid grid-cols-8 gap-1 justify-items-center">
          <button
            v-for="fdi in LOWER_LEFT"
            :key="`ll-${fdi}`"
            type="button"
            :title="`Tooth ${fdi} — ${procedureSummary(fdi) || 'unmarked'}`"
            :class="['h-9 w-9 rounded-md border text-xs font-mono', toothColor(fdi)]"
            @click="openFor(fdi)"
          >
            {{ fdi }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="teeth.length > 0" class="text-xs space-y-1">
      <div class="font-medium text-base-content/70">Marked teeth</div>
      <ul class="space-y-1">
        <li
          v-for="t in teeth"
          :key="t.fdi"
          class="flex items-center gap-2 rounded-md bg-base-200 px-2 py-1"
        >
          <span class="font-mono w-10 text-base-content/70">#{{ t.fdi }}</span>
          <span class="flex-1 text-base-content/80">{{ procedureSummary(t.fdi) }}</span>
          <button
            type="button"
            class="text-primary hover:text-primary"
            @click="openFor(t.fdi)"
          >
            Edit
          </button>
          <button
            type="button"
            class="text-error hover:text-error"
            @click="removeTooth(t.fdi)"
          >
            Remove
          </button>
        </li>
      </ul>
    </div>

    <UiDialog
      v-model="dialogOpen"
      :title="editingFdi ? `Procedures for tooth #${editingFdi}` : ''"
    >
      <div class="space-y-3">
        <div
          v-for="(p, idx) in draftProcedures"
          :key="idx"
          class="rounded-md border border-base-300 p-3 space-y-2"
        >
          <div class="flex items-center justify-between text-xs">
            <UiBadge variant="primary">Procedure {{ idx + 1 }}</UiBadge>
            <button
              type="button"
              class="text-error hover:text-error"
              @click="removeProcedure(idx)"
            >
              Remove
            </button>
          </div>
          <UiSelect
            :model-value="p.code"
            :options="PROCEDURE_OPTIONS"
            label="Procedure"
            @update:model-value="(v) => onCodeChange(idx, v)"
          />
          <UiSelect
            v-if="surfaceOptionsFor(p.code).length > 0"
            :model-value="p.surface ?? ''"
            :options="surfaceOptionsFor(p.code)"
            label="Surface"
            @update:model-value="(v) => (draftProcedures[idx].surface = v || null)"
          />
        </div>

        <UiButton variant="outline" icon="plus" type="button" @click="addProcedure">
          Add another procedure
        </UiButton>
      </div>

      <template #footer>
        <UiButton variant="outline" @click="dialogOpen = false">Cancel</UiButton>
        <UiButton @click="saveDialog">Save</UiButton>
      </template>
    </UiDialog>
  </div>
</template>
