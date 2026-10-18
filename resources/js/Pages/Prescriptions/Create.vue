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
  doctors: Array,
  frequencyOptions: Array,
  mealRelationOptions: Array,
  contextAppointmentId: { type: [Number, null], default: null },
})

const breadcrumbs = [
  { label: 'Patients', href: '/patients' },
  { label: props.patient.name, href: `/patients/${props.patient.id}` },
  { label: 'Prescriptions', href: `/patients/${props.patient.id}/prescriptions` },
  { label: 'New' },
]

const form = useForm({
  doctor_id: null,
  appointment_id: props.contextAppointmentId,
  general_advice: '',
  diagnosis_text: '',
  items: [blankRow()],
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

const doctorOptions = computed(() =>
  props.doctors.map((d) => ({ label: d.name, value: d.id })),
)

const frequencySelectOptions = computed(() =>
  props.frequencyOptions.map((f) => ({ label: `${f.labelBn} · ${f.labelEn}`, value: f.value })),
)
const mealSelectOptions = computed(() =>
  props.mealRelationOptions.map((m) => ({ label: `${m.labelBn} · ${m.labelEn}`, value: m.value })),
)

// When the user changes a frequency/meal relation dropdown we mirror the Bangla
// label back into the item so the stored prescription keeps the same wording the
// doctor saw on screen.
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
  form.post(`/patients/${props.patient.id}/prescriptions`)
}
</script>

<template>
  <Head :title="`New prescription — ${patient.name}`" />
  <AuthenticatedLayout>
    <div class="max-w-5xl space-y-5 my-4">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="New prescription"
        :subtitle="`${patient.name} · ${patient.patient_code}`"
        icon="pill"
        eyebrow="Add prescription"
      />

      <Card title="Prescription details">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-medium text-base-content/70">ডাক্তার (Doctor)</label>
            <Select
              v-model="form.doctor_id"
              :options="doctorOptions"
              placeholder="ডাক্তার নির্বাচন করুন"
            />
            <p v-if="form.errors.doctor_id" class="text-xs text-error mt-1">{{ form.errors.doctor_id }}</p>
          </div>
          <div>
            <label class="text-xs font-medium text-base-content/70">রোগ নির্ণয় (Diagnosis)</label>
            <Input
              v-model="form.diagnosis_text"
              placeholder="যেমন: Acute pulpitis — tooth #26"
            />
          </div>
          <div class="sm:col-span-2">
            <label class="text-xs font-medium text-base-content/70">সাধারণ পরামর্শ (General advice)</label>
            <Textarea
              v-model="form.general_advice"
              :rows="2"
              placeholder="যেমন: গরম/ঠান্ডা এড়িয়ে চলুন, ২ দিন পর ফলো-আপ।"
            />
          </div>
        </div>
      </Card>

      <Card title="ওষুধের তালিকা (Medicines)">
        <div class="space-y-3">
          <div
            v-for="(item, idx) in form.items"
            :key="idx"
            class="rounded-lg border border-base-300 bg-base-100 p-3"
          >
            <div class="flex items-start gap-3 flex-wrap">
              <!-- Medicine picker trigger -->
              <div class="flex-1 min-w-[180px]">
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

              <!-- কতবার — frequency -->
              <div class="min-w-[150px]">
                <label class="text-xs font-medium text-base-content/70">কতবার (Frequency)</label>
                <Select
                  :model-value="item.frequency"
                  :options="frequencySelectOptions"
                  @update:model-value="(v) => onFrequencyChange(item, v)"
                />
              </div>

              <!-- কতটুকু — dosage -->
              <div class="min-w-[140px]">
                <label class="text-xs font-medium text-base-content/70">কতটুকু (Dosage)</label>
                <Input
                  v-model="item.dosage"
                  placeholder="যেমন: ১ টি, ১০ মিলি"
                />
              </div>

              <!-- মাত্রা — duration -->
              <div class="min-w-[120px]">
                <label class="text-xs font-medium text-base-content/70">মাত্রা (Duration)</label>
                <Input
                  v-model="item.duration"
                  placeholder="যেমন: ৫ দিন"
                />
              </div>

              <!-- খাবারের সাথে সম্পর্ক — meal relation -->
              <div class="min-w-[160px]">
                <label class="text-xs font-medium text-base-content/70">খাবারের সাথে (Meal relation)</label>
                <Select
                  :model-value="item.meal_relation"
                  :options="mealSelectOptions"
                  @update:model-value="(v) => onMealChange(item, v)"
                />
              </div>

              <!-- পরিমাণ -->
              <div class="w-20">
                <label class="text-xs font-medium text-base-content/70">পরিমাণ (Qty)</label>
                <Input v-model.number="item.quantity" type="number" min="1" />
              </div>

              <!-- Reorder + remove -->
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

            <!-- Optional Bangla instructions row -->
            <div class="mt-3">
              <label class="text-xs font-medium text-base-content/70">
                বিশেষ নির্দেশনা (Special instructions)
              </label>
              <Input
                v-model="item.instructions"
                placeholder="যেমন: ঘুমের আগে খাবেন, পানি দিয়ে গিলবেন"
              />
            </div>
          </div>

          <Button variant="outline" icon="plus" type="button" @click="addRow">
            আর ও ওষুধ যোগ করুন
          </Button>
        </div>
      </Card>

      <div class="flex items-center justify-end gap-2 pt-2">
        <Link :href="`/patients/${patient.id}/prescriptions`">
          <Button variant="outline">Cancel</Button>
        </Link>
        <Button :loading="form.processing" icon-right="check" @click="submit">
          Create prescription
        </Button>
      </div>

      <MedicinePickerDialog v-model="pickerOpen" @selected="onMedicineSelected" />
    </div>
  </AuthenticatedLayout>
</template>