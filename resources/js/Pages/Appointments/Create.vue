<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'
import PatientQuickAddDialog from '@/Components/PatientQuickAddDialog.vue'
import { ref, watch } from 'vue'

const props = defineProps({ doctors: Array })

const form = useForm({
  doctor_id: props.doctors[0]?.id ?? null,
  patient_id: null,
  scheduled_at: '',
  duration_minutes: 30,
  notes: '',
})

const selectedPatient = ref(null)
const pickerOpen = ref(false)

function onPatientSelected(p) {
  selectedPatient.value = p
  form.patient_id = p.id
}
function onPatientCreated(p) {
  selectedPatient.value = p
  form.patient_id = p.id
}

const slots = ref([])
const loadingSlots = ref(false)

async function loadSlots() {
  if (!form.doctor_id || !form.scheduled_at) {
    slots.value = []
    return
  }
  const date = form.scheduled_at.slice(0, 10)
  loadingSlots.value = true
  try {
    const res = await fetch(
      `/appointments/slots/${form.doctor_id}?date=${date}&duration_minutes=${form.duration_minutes}`,
      { headers: { Accept: 'application/json' } },
    )
    const data = await res.json()
    slots.value = data.slots || []
  } catch (e) {
    slots.value = []
  }
  loadingSlots.value = false
}

watch(() => [form.doctor_id, form.duration_minutes], loadSlots)

function pickSlot(slot) {
  form.scheduled_at = slot.slice(0, 16)
}

function fmtSlot(slot) {
  return new Date(slot).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
}

function submit() {
  form.post('/appointments')
}
</script>

<template>
  <Head title="New appointment" />
  <AuthenticatedLayout>
    <div class="max-w-3xl">
      <UiCard title="New appointment">
        <UiForm @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-3">
            <UiField label="Doctor" :error="form.errors.doctor_id" required>
              <UiSelect
                v-model="form.doctor_id"
                :options="doctors.map((d) => ({ label: d.name, value: d.id }))"
              />
            </UiField>
            <UiField label="Duration (minutes)" :error="form.errors.duration_minutes" required>
              <UiInput v-model="form.duration_minutes" type="number" min="5" max="480" required />
            </UiField>
          </div>

          <UiField label="Patient" :error="form.errors.patient_id" required>
            <div v-if="selectedPatient" class="flex items-center justify-between gap-2 rounded-md border border-base-300 bg-base-200 px-3 py-2">
              <div>
                <div class="text-sm font-medium text-base-content">{{ selectedPatient.name }}</div>
                <div class="text-xs text-base-content/70 font-mono">{{ selectedPatient.patient_code }} · {{ selectedPatient.phone || 'no phone' }}</div>
              </div>
              <button type="button" class="text-xs text-primary hover:underline" @click="pickerOpen = true">
                Change
              </button>
            </div>
            <button
              v-else
              type="button"
              class="h-10 w-full rounded-md border border-dashed border-base-300 bg-base-100 px-3 text-sm text-base-content/70 hover:bg-base-200"
              @click="pickerOpen = true"
            >
              Select or create a patient
            </button>
          </UiField>

          <div class="grid grid-cols-2 gap-3">
            <UiField label="Date" :error="form.errors.scheduled_at" required>
              <input
                v-model="form.scheduled_at"
                type="date"
                class="h-10 w-full rounded-md border border-base-300 bg-base-100 px-3 text-sm"
                @change="loadSlots"
              />
            </UiField>
            <UiField label="Time" :error="form.errors.scheduled_at" required>
              <input
                v-model="form.scheduled_at"
                type="datetime-local"
                class="h-10 w-full rounded-md border border-base-300 bg-base-100 px-3 text-sm"
              />
            </UiField>
          </div>
          <div v-if="loadingSlots" class="text-sm text-base-content/70">Loading slots…</div>
          <div v-else-if="slots.length > 0" class="grid grid-cols-6 gap-2">
            <button
              v-for="s in slots"
              :key="s"
              type="button"
              @click="pickSlot(s)"
              class="h-9 rounded-md border border-base-300 bg-base-100 px-2 text-xs text-base-content hover:bg-base-200"
              :class="{ 'ring-2 ring-primary': form.scheduled_at === s.slice(0, 16) }"
            >
              {{ fmtSlot(s) }}
            </button>
          </div>
          <div v-else-if="form.doctor_id && form.scheduled_at" class="text-sm text-base-content/70">
            No available slots for this date.
          </div>
          <UiField label="Notes" :error="form.errors.notes">
            <UiTextarea v-model="form.notes" rows="3" />
          </UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-300">
            <Link
              href="/appointments"
              class="h-10 px-4 inline-flex items-center rounded-md bg-base-200 text-base-content border border-base-300 text-sm"
              >Cancel</Link
            >
            <UiButton type="submit" :loading="form.processing" :disabled="!form.patient_id">Book</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>

    <PatientQuickAddDialog
      v-model="pickerOpen"
      @selected="onPatientSelected"
      @created="onPatientCreated"
    />
  </AuthenticatedLayout>
</template>