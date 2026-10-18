<script setup>
/**
 * PrescriptionCreateDialog
 * -----------------------
 * Inline modal for writing an Rx from the appointment Show page.
 *
 * - The doctor is locked to the appointment's doctor — read-only display
 *   so the Rx always reflects who actually saw the patient.
 * - Each item row is a reactive `ref()` so the `<Select>` re-renders when
 *   frequency / meal-relation change (Vue's reactivity needs a proxy to
 *   track nested property updates on form items).
 * - On save it POSTs to the standard prescription store endpoint with
 *   `appointment_id` so the new Rx links back to the visit.
 * - Emits `created` with the patient id so the parent can refresh.
 */
import { computed, reactive, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import UiDialog from '@/Components/ui/Dialog.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'
import Icon from '@/Components/ui/Icon.vue'
import MedicinePickerDialog from '@/Components/MedicinePickerDialog.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  patient: { type: Object, required: true },
  appointmentId: { type: [Number, null], default: null },
  doctorId: { type: [Number, null], default: null },
  doctors: { type: Array, default: () => [] },
  frequencyOptions: { type: Array, default: () => [] },
  mealRelationOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'created'])

function blankRow() {
  return {
    medicine_id: null,
    medicine_name: '',
    strength: '',
    dosage: '',
    frequency: 'twice_daily',
    frequency_label_bn: 'দিনে ২ বার',
    duration: '',
    meal_relation: 'after_meal',
    meal_relation_label_bn: 'খাবারের পরে',
    instructions: '',
    quantity: 1,
    sort_order: 0,
  }
}

// useForm wraps everything in a reactive proxy, so .items is already reactive.
// We initialise once and reset on close.
const form = useForm({
  doctor_id: props.doctorId ?? null,
  appointment_id: props.appointmentId ?? null,
  general_advice: '',
  diagnosis_text: '',
  items: [blankRow()],
})

const doctorLabel = computed(() => {
  const d = props.doctors.find((doc) => Number(doc.id) === Number(props.doctorId))
  return d ? d.name : 'Unknown doctor'
})

const frequencySelectOptions = computed(() =>
  props.frequencyOptions.map((f) => ({ label: `${f.labelBn} · ${f.labelEn}`, value: f.value })),
)
const mealSelectOptions = computed(() =>
  props.mealRelationOptions.map((m) => ({ label: `${m.labelBn} · ${m.labelEn}`, value: m.value })),
)

// The doctor/lock requirement says the doctor field is not changeable from
// inside the modal — we just show the assigned doctor's name + a small lock
// chip so it's obvious the value is intentional.

function onFrequencyChange(row, value) {
  row.frequency = value
  const opt = props.frequencyOptions.find((f) => f.value === value)
  row.frequency_label_bn = opt?.labelBn ?? ''
}
function onMealChange(row, value) {
  row.meal_relation = value
  const opt = props.mealRelationOptions.find((m) => m.value === value)
  row.meal_relation_label_bn = opt?.labelBn ?? ''
}

const pickerOpen = ref(false)
const pickerForIndex = ref(null)
function openMedicinePicker(index) {
  pickerForIndex.value = index
  pickerOpen.value = true
}
function onMedicineSelected(med) {
  const idx = pickerForIndex.value
  if (idx === null || idx === undefined) return
  const row = form.items[idx]
  if (!row) return
  row.medicine_id = med.id
  row.medicine_name = med.name
  row.strength = med.strength ?? ''
  pickerOpen.value = false
  pickerForIndex.value = null
}

function addRow() {
  form.items.push(blankRow())
}
function removeRow(index) {
  if (form.items.length <= 1) return
  form.items.splice(index, 1)
}
function moveUp(index) {
  if (index === 0) return
  const tmp = form.items[index]
  form.items[index] = form.items[index - 1]
  form.items[index - 1] = tmp
  reorder()
}
function moveDown(index) {
  if (index === form.items.length - 1) return
  const tmp = form.items[index]
  form.items[index] = form.items[index + 1]
  form.items[index + 1] = tmp
  reorder()
}
function reorder() {
  form.items.forEach((it, idx) => (it.sort_order = idx))
}

function submit() {
  reorder()
  form.post(`/patients/${props.patient.id}/prescriptions`, {
    preserveScroll: true,
    onSuccess: () => {
      emit('created', { patientId: props.patient.id })
      reset()
    },
  })
}

function reset() {
  form.reset()
  form.items = [blankRow()]
  form.doctor_id = props.doctorId ?? null
  form.appointment_id = props.appointmentId ?? null
}

// Re-seed the form when the modal re-opens with a different appointment.
// We don't keep a local `open` ref anymore — UiDialog handles v-model
// internally, and we let the dialog own its open/close state via the
// parent's v-model.
watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      form.doctor_id = props.doctorId ?? null
      form.appointment_id = props.appointmentId ?? null
      // Keep any in-progress medicine rows if the user re-opens after a
      // save error, but only clear errors. If the user explicitly closes
      // and re-opens for a brand-new Rx we reset.
      form.clearErrors()
    }
  },
)
</script>

<template>
  <UiDialog :model-value="modelValue" title="নতুন প্রেসক্রিপশন লিখুন" size="xl" @update:model-value="(v) => emit('update:modelValue', v)" @close="reset">
    <div class="space-y-4">
      <div class="rounded-md border border-base-300 bg-base-200/40 px-3 py-2 text-sm">
        <div class="font-medium">
          {{ patient.name }}
          <span class="text-muted font-mono">· {{ patient.patient_code }}</span>
        </div>
        <div v-if="appointmentId" class="text-xs text-muted">
          Appointment #{{ appointmentId }} — এই প্রেসক্রিপশন ভিজিটের সাথে লিংক করা হবে।
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <!-- Doctor field is locked to the appointment's doctor -->
        <div>
          <label class="text-xs font-medium text-base-content/70 flex items-center gap-1.5">
            ডাক্তার (Doctor)
            <span class="inline-flex items-center gap-0.5 rounded bg-base-200 px-1.5 py-0.5 text-[10px] font-medium text-base-content/70">
              <Icon name="lock" size="xs" /> ভিজিট ডাক্তার
            </span>
          </label>
          <div class="mt-1 flex items-center gap-2 rounded-md border border-base-300 bg-base-100 px-3 py-2">
            <span
              class="h-2.5 w-2.5 rounded-full ring-2 ring-base-100"
              :style="{ backgroundColor: doctors.find((d) => Number(d.id) === Number(doctorId))?.color || '#888' }"
            />
            <span class="font-medium">{{ doctorLabel }}</span>
          </div>
          <!-- Hidden input so the form still submits doctor_id -->
          <input type="hidden" v-model="form.doctor_id" />
          <p v-if="form.errors.doctor_id" class="text-xs text-error mt-1">{{ form.errors.doctor_id }}</p>
        </div>

        <div class="sm:col-span-2">
          <label class="text-xs font-medium text-base-content/70">রোগ নির্ণয় (Diagnosis)</label>
          <UiTextarea v-model="form.diagnosis_text" :rows="3" placeholder="যেমন: Acute pulpitis — tooth #26" />
        </div>

        <div class="sm:col-span-2">
          <label class="text-xs font-medium text-base-content/70">সাধারণ পরামর্শ (General advice)</label>
          <UiTextarea v-model="form.general_advice" :rows="2" placeholder="যেমন: গরম/ঠান্ডা এড়িয়ে চলুন, ২ দিন পর ফলো-আপ।" />
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <h4 class="text-sm font-semibold">ওষুধের তালিকা (Medicines)</h4>
        </div>

        <div class="space-y-3">
          <div
            v-for="(item, idx) in form.items"
            :key="idx"
            class="rounded-lg border border-base-300 bg-base-100 p-3"
          >
            <div class="flex items-start gap-3 flex-wrap">
              <div class="flex-1 min-w-[160px]">
                <label class="text-xs font-medium text-base-content/70">ওষুধ (Medicine)</label>
                <button
                  type="button"
                  class="w-full text-left rounded-md border border-base-300 bg-base-100 px-3 py-2 hover:border-primary transition-colors"
                  @click="openMedicinePicker(idx)"
                >
                  <template v-if="item.medicine_name">
                    <div class="font-medium text-base-content">
                      {{ item.medicine_name }}<span v-if="item.strength"> {{ item.strength }}</span>
                    </div>
                  </template>
                  <template v-else>
                    <span class="text-muted">ওষুধ নির্বাচন করুন…</span>
                  </template>
                </button>
                <p v-if="form.errors[`items.${idx}.medicine_name`]" class="text-xs text-error mt-1">
                  {{ form.errors[`items.${idx}.medicine_name`] }}
                </p>
              </div>

              <div class="min-w-[140px]">
                <label class="text-xs font-medium text-base-content/70">কতবার (Frequency)</label>
                <UiSelect
                  :model-value="item.frequency"
                  :options="frequencySelectOptions"
                  @update:model-value="(v) => onFrequencyChange(item, v)"
                />
              </div>

              <div class="min-w-[130px]">
                <label class="text-xs font-medium text-base-content/70">কতটুকু (Dosage)</label>
                <UiInput v-model="item.dosage" placeholder="যেমন: ১ টি, ১০ মিলি" />
              </div>

              <div class="min-w-[110px]">
                <label class="text-xs font-medium text-base-content/70">মাত্রা (Duration)</label>
                <UiInput v-model="item.duration" placeholder="যেমন: ৫ দিন" />
              </div>

              <div class="min-w-[150px]">
                <label class="text-xs font-medium text-base-content/70">খাবারের সাথে (Meal relation)</label>
                <UiSelect
                  :model-value="item.meal_relation"
                  :options="mealSelectOptions"
                  @update:model-value="(v) => onMealChange(item, v)"
                />
              </div>

              <div class="w-20">
                <label class="text-xs font-medium text-base-content/70">পরিমাণ (Qty)</label>
                <UiInput v-model.number="item.quantity" type="number" min="1" />
              </div>

              <div class="flex flex-col gap-1 pt-5">
                <div class="flex gap-1">
                  <button
                    type="button"
                    class="rounded-md p-1 text-base-content/60 hover:text-primary hover:bg-base-200"
                    :disabled="idx === 0"
                    title="উপরে"
                    @click="moveUp(idx)"
                  >
                    <Icon name="chevron-up" size="sm" />
                  </button>
                  <button
                    type="button"
                    class="rounded-md p-1 text-base-content/60 hover:text-primary hover:bg-base-200"
                    :disabled="idx === form.items.length - 1"
                    title="নিচে"
                    @click="moveDown(idx)"
                  >
                    <Icon name="chevron-down" size="sm" />
                  </button>
                  <button
                    type="button"
                    class="rounded-md p-1 text-base-content/60 hover:text-error hover:bg-error/10"
                    :disabled="form.items.length <= 1"
                    title="মুছে ফেলুন"
                    @click="removeRow(idx)"
                  >
                    <Icon name="trash-2" size="sm" />
                  </button>
                </div>
              </div>
            </div>

            <div class="mt-2">
              <UiInput v-model="item.instructions" placeholder="বিশেষ নির্দেশনা (ঐচ্ছিক)" />
            </div>
          </div>

          <UiButton variant="outline" icon="plus" type="button" @click="addRow">
            আর ও ওষুধ যোগ করুন
          </UiButton>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-2">
        <UiButton variant="outline" type="button" @click="emit('update:modelValue', false)">বাতিল</UiButton>
        <UiButton :loading="form.processing" icon-right="check" type="button" @click="submit">
          প্রেসক্রিপশন সংরক্ষণ করুন
        </UiButton>
      </div>
    </template>

    <MedicinePickerDialog v-model="pickerOpen" @selected="onMedicineSelected" />
  </UiDialog>
</template>