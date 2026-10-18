<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import Textarea from '@/Components/ui/Textarea.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import MedicinePickerDialog from '@/Components/MedicinePickerDialog.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({
  patient: Object,
  prescription: Object,
  doctors: Array,
  frequencyOptions: Array,
  mealRelationOptions: Array,
})

const breadcrumbs = [
  { label: 'Patients', href: '/patients' },
  { label: props.patient.name, href: `/patients/${props.patient.id}` },
  { label: 'Prescriptions', href: `/patients/${props.patient.id}/prescriptions` },
  { label: props.prescription.prescription_number },
]

const form = useForm({
  doctor_id: props.prescription.doctor_id,
  general_advice: props.prescription.general_advice ?? '',
  diagnosis_text: props.prescription.diagnosis_text ?? '',
  items: (props.prescription.items ?? []).map((it) => ({
    medicine_id: it.medicine_id,
    medicine_name: it.medicine_name,
    strength: it.strength ?? '',
    dosage: it.dosage,
    frequency: it.frequency,
    frequency_label_bn: it.frequency_label_bn,
    duration: it.duration ?? '',
    meal_relation: it.meal_relation,
    meal_relation_label_bn: it.meal_relation_label_bn,
    instructions: it.instructions ?? '',
    quantity: it.quantity,
    sort_order: it.sort_order,
  })),
})

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

const doctorOptions = computed(() => props.doctors.map((d) => ({ label: d.name, value: d.id })))
const frequencySelectOptions = computed(() =>
  props.frequencyOptions.map((f) => ({ label: `${f.labelBn} · ${f.labelEn}`, value: f.value })),
)
const mealSelectOptions = computed(() =>
  props.mealRelationOptions.map((m) => ({ label: `${m.labelBn} · ${m.labelEn}`, value: m.value })),
)

function onFrequencyChange(row, value) {
  row.frequency = value
  row.frequency_label_bn = props.frequencyOptions.find((f) => f.value === value)?.labelBn ?? ''
}
function onMealChange(row, value) {
  row.meal_relation = value
  row.meal_relation_label_bn = props.mealRelationOptions.find((m) => m.value === value)?.labelBn ?? ''
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
  ;[form.items[index], form.items[index - 1]] = [form.items[index - 1], form.items[index]]
  reorder()
}
function moveDown(index) {
  if (index === form.items.length - 1) return
  ;[form.items[index], form.items[index + 1]] = [form.items[index + 1], form.items[index]]
  reorder()
}
function reorder() {
  form.items.forEach((it, idx) => (it.sort_order = idx))
}

function submit() {
  reorder()
  form.patch(`/patients/${props.patient.id}/prescriptions/${props.prescription.id}`)
}
</script>

<template>
  <Head :title="`Edit — ${prescription.prescription_number}`" />
  <AuthenticatedLayout>
    <div class="max-w-5xl space-y-5 my-4">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        :title="`Edit ${prescription.prescription_number}`"
        :subtitle="`${patient.name} · ${patient.patient_code}`"
        icon="pill"
        eyebrow="Edit draft"
      />

      <Card title="Prescription details">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-medium text-base-content/70">ডাক্তার</label>
            <Select v-model="form.doctor_id" :options="doctorOptions" placeholder="ডাক্তার নির্বাচন করুন" />
            <p v-if="form.errors.doctor_id" class="text-xs text-error mt-1">{{ form.errors.doctor_id }}</p>
          </div>
          <div>
            <label class="text-xs font-medium text-base-content/70">রোগ নির্ণয়</label>
            <Input v-model="form.diagnosis_text" />
          </div>
          <div class="sm:col-span-2">
            <label class="text-xs font-medium text-base-content/70">সাধারণ পরামর্শ</label>
            <Textarea v-model="form.general_advice" :rows="2" />
          </div>
        </div>
      </Card>

      <Card title="ওষুধের তালিকা">
        <div class="space-y-3">
          <div
            v-for="(item, idx) in form.items"
            :key="idx"
            class="rounded-lg border border-base-300 bg-base-100 p-3"
          >
            <div class="flex items-start gap-3 flex-wrap">
              <div class="flex-1 min-w-[180px]">
                <label class="text-xs font-medium text-base-content/70">ওষুধ</label>
                <button
                  type="button"
                  class="w-full text-left rounded-md border border-base-300 bg-base-100 px-3 py-2 hover:border-primary"
                  @click="openMedicinePicker(idx)"
                >
                  <template v-if="item.medicine_name">
                    <div class="font-medium">{{ item.medicine_name }}<span v-if="item.strength"> {{ item.strength }}</span></div>
                  </template>
                  <template v-else>
                    <span class="text-muted">ওষুধ নির্বাচন করুন…</span>
                  </template>
                </button>
                <p v-if="form.errors[`items.${idx}.medicine_name`]" class="text-xs text-error mt-1">
                  {{ form.errors[`items.${idx}.medicine_name`] }}
                </p>
              </div>
              <div class="min-w-[150px]">
                <label class="text-xs font-medium text-base-content/70">কতবার</label>
                <Select
                  :model-value="item.frequency"
                  :options="frequencySelectOptions"
                  @update:model-value="(v) => onFrequencyChange(item, v)"
                />
              </div>
              <div class="min-w-[140px]">
                <label class="text-xs font-medium text-base-content/70">কতটুকু</label>
                <Input v-model="item.dosage" />
              </div>
              <div class="min-w-[120px]">
                <label class="text-xs font-medium text-base-content/70">মাত্রা</label>
                <Input v-model="item.duration" />
              </div>
              <div class="min-w-[160px]">
                <label class="text-xs font-medium text-base-content/70">খাবারের সাথে</label>
                <Select
                  :model-value="item.meal_relation"
                  :options="mealSelectOptions"
                  @update:model-value="(v) => onMealChange(item, v)"
                />
              </div>
              <div class="w-20">
                <label class="text-xs font-medium text-base-content/70">পরিমাণ</label>
                <Input v-model.number="item.quantity" type="number" min="1" />
              </div>
              <div class="flex flex-col gap-1 pt-5">
                <div class="flex gap-1">
                  <button type="button" class="rounded-md p-1 text-base-content/60 hover:text-primary hover:bg-base-200" :disabled="idx === 0" @click="moveUp(idx)"><Icon name="chevron-up" size="sm" /></button>
                  <button type="button" class="rounded-md p-1 text-base-content/60 hover:text-primary hover:bg-base-200" :disabled="idx === form.items.length - 1" @click="moveDown(idx)"><Icon name="chevron-down" size="sm" /></button>
                  <button type="button" class="rounded-md p-1 text-base-content/60 hover:text-error hover:bg-error/10" :disabled="form.items.length <= 1" @click="removeRow(idx)"><Icon name="trash-2" size="sm" /></button>
                </div>
              </div>
            </div>
            <div class="mt-3">
              <label class="text-xs font-medium text-base-content/70">বিশেষ নির্দেশনা</label>
              <Input v-model="item.instructions" />
            </div>
          </div>

          <Button variant="outline" icon="plus" type="button" @click="addRow">আর ও ওষুধ যোগ করুন</Button>
        </div>
      </Card>

      <div class="flex items-center justify-end gap-2 pt-2">
        <Link :href="`/patients/${patient.id}/prescriptions/${prescription.id}`">
          <Button variant="outline">Cancel</Button>
        </Link>
        <Button :loading="form.processing" icon-right="check" @click="submit">Save changes</Button>
      </div>

      <MedicinePickerDialog v-model="pickerOpen" @selected="onMedicineSelected" />
    </div>
  </AuthenticatedLayout>
</template>