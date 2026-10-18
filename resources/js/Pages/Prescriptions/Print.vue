<script setup>
/**
 * Print view — paper-friendly. Uses the bare Print layout so the
 * app sidebar / top bar / breadcrumbs never enter the document at
 * all, instead of relying on `@media print` rules to hide them.
 * The on-screen toolbar (back link + print button) is wrapped in
 * `print:hidden` so it disappears from the actual printout.
 */
import { Head } from '@inertiajs/vue3'
import PrintLayout from '@/Layouts/Print.vue'
import { onMounted } from 'vue'
import { usePrescriptionFormat } from '@/Composables/usePrescriptionFormat.js'

const props = defineProps({
  patient: Object,
  prescription: Object,
})

const { formatMedicine, formatDuration, toBanglaDigits } = usePrescriptionFormat()

function fmt(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString()
}

onMounted(() => {
  // When opened in a new tab from the appointment show page, auto-fire the
  // print dialog so the doctor doesn't need a second click. The user can
  // always cancel and print manually.
  if (window.opener || window.location.search.includes('autoprint=1')) {
    setTimeout(() => window.print(), 250)
  }
})

function triggerPrint() {
  window.print()
}

function goBack() {
  if (window.history.length > 1) {
    window.history.back()
  } else {
    window.close()
  }
}
</script>

<template>
  <Head :title="`Print — ${prescription.prescription_number}`" />
  <PrintLayout>
    <div class="max-w-3xl mx-auto my-6 space-y-4 print:my-0 print:max-w-none">
      <div class="print:hidden flex items-center justify-between gap-2">
        <button
          type="button"
          class="text-sm text-primary hover:underline"
          @click="goBack"
        >
          ← ফিরে যান
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-md border border-base-300 bg-base-100 px-3 py-1.5 text-sm font-medium hover:bg-base-200"
          @click="triggerPrint"
        >
          🖨️ Print
        </button>
      </div>

      <article class="bg-base-100 border border-base-300 rounded-lg p-6 print:border-0 print:rounded-none print:p-0">
        <header class="flex items-start justify-between border-b border-base-300 pb-4 mb-4 print:pb-3 print:mb-3">
          <div>
            <div class="text-xs text-muted">রোগীর তথ্য</div>
            <div class="text-lg font-bold">{{ patient.name }}</div>
            <div class="text-xs text-muted font-mono">{{ patient.patient_code }}</div>
            <div class="text-xs mt-1">
              <span v-if="patient.age">বয়স: {{ patient.age }}</span>
              <span v-if="patient.gender"> · {{ patient.gender }}</span>
              <span v-if="patient.phone"> · {{ patient.phone }}</span>
            </div>
          </div>
          <div class="text-right">
            <div class="text-xl font-bold tracking-wide">℞</div>
            <div class="font-mono text-sm">{{ prescription.prescription_number }}</div>
            <div class="text-xs text-muted mt-1">
              {{ fmt(prescription.issued_at) }}
            </div>
            <div v-if="prescription.doctor_name" class="text-sm font-medium mt-1">
              Dr. {{ prescription.doctor_name }}
            </div>
          </div>
        </header>

        <div class="grid grid-cols-[1fr_3fr] gap-x-6 gap-y-4 print:grid-cols-[1fr_3fr] print:gap-x-6 print:gap-y-4">
          <div class="space-y-4 print:space-y-3">
            <section v-if="prescription.diagnosis_text">
              <div class="text-xs text-muted">রোগ নির্ণয়</div>
              <div class="text-sm whitespace-pre-wrap">{{ prescription.diagnosis_text }}</div>
            </section>

            <section v-if="prescription.general_advice">
              <div class="text-xs text-muted">সাধারণ পরামর্শ</div>
              <div class="text-sm whitespace-pre-wrap">{{ prescription.general_advice }}</div>
            </section>
          </div>

          <section>
            <div class="text-xs text-muted mb-2">ওষুধের তালিকা</div>
            <ol class="space-y-3 print:space-y-2">
              <li
                v-for="(it, idx) in prescription.items"
                :key="idx"
                class="border-b border-dashed border-base-300 pb-2 last:border-0"
              >
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_1.4fr] gap-x-4 gap-y-0.5">
                  <div class="font-semibold">
                    <span class="mr-1.5">{{ idx + 1 }}.</span>
                    {{ formatMedicine(it.medicine_name, it.strength) }}
                  </div>
                  <div class="text-sm space-y-1">
                    <div v-if="it.dosage">
                      <span class="font-semibold">কতটুকু:</span> {{ toBanglaDigits(it.dosage) }}
                    </div>
                    <div v-if="it.frequency_label_bn">
                      <span class="font-semibold">কতবার:</span> {{ it.frequency_label_bn }}
                    </div>
                    <div v-if="it.meal_relation_label_bn">
                      <span class="font-semibold">কখন:</span> {{ it.meal_relation_label_bn }}
                    </div>
                    <div v-if="it.duration">
                      <span class="font-semibold">কতদিন:</span> {{ formatDuration(it.duration) }}
                    </div>
                    <p v-if="it.instructions" class="text-xs text-muted pt-1">
                      {{ it.instructions }}
                    </p>
                  </div>
                </div>
              </li>
            </ol>
          </section>
        </div>
      </article>
    </div>
  </PrintLayout>
</template>

<style>
/*
 * The page already uses the bare Print layout, so nothing app-chrome
 * needs hiding. We only reset the body background so the sheet prints
 * on white instead of the app's base-200 surface.
 */
@media print {
  body { background: white !important; }
}
</style>