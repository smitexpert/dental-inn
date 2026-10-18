<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import { useCurrentUser } from '@/Composables/useCurrentUser.js'
import { useConfirm } from '@/Composables/useConfirm.js'
import { usePrescriptionFormat } from '@/Composables/usePrescriptionFormat.js'

const props = defineProps({
  patient: Object,
  prescription: Object,
})

const { can } = useCurrentUser()
const confirm = useConfirm()
const { formatMedicine, formatDuration, toBanglaDigits } = usePrescriptionFormat()

function statusVariant(s) {
  return { draft: 'warning', issued: 'success', cancelled: 'danger' }[s] || 'neutral'
}
function fmt(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString()
}

function issue() {
  confirm.require({
    header: 'Issue this prescription?',
    message: 'Once issued, it cannot be edited. The patient can take a printed copy.',
    acceptLabel: 'Issue prescription',
    acceptClass: 'btn-primary',
    accept: () =>
      router.post(`/patients/${props.patient.id}/prescriptions/${props.prescription.id}/issue`),
  })
}
function cancelRx() {
  confirm.require({
    header: 'Cancel this prescription?',
    message: 'This will mark the prescription as cancelled. The record will stay for audit.',
    acceptLabel: 'Cancel prescription',
    acceptClass: 'btn-error',
    accept: () =>
      router.post(`/patients/${props.patient.id}/prescriptions/${props.prescription.id}/cancel`),
  })
}

const breadcrumbs = [
  { label: 'Patients', href: '/patients' },
  { label: props.patient.name, href: `/patients/${props.patient.id}` },
  { label: 'Prescriptions', href: `/patients/${props.patient.id}/prescriptions` },
  { label: props.prescription.prescription_number },
]
</script>

<template>
  <Head :title="`Prescription — ${prescription.prescription_number}`" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-5xl my-4">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        :title="prescription.prescription_number"
        :subtitle="`${patient.name} · ${patient.patient_code}`"
        icon="pill"
        :eyebrow="`Prescription · ${prescription.status_label}`"
      >
        <template #actions>
          <Badge :variant="statusVariant(prescription.status)" dot>
            {{ prescription.status_label }}
          </Badge>
          <Link :href="`/patients/${patient.id}/prescriptions/${prescription.id}/print`">
            <Button variant="outline" icon="printer">Print</Button>
          </Link>
          <Link
            v-if="can('manage-prescriptions') && prescription.is_editable"
            :href="`/patients/${patient.id}/prescriptions/${prescription.id}/edit`"
          >
            <Button variant="outline" icon="edit">Edit</Button>
          </Link>
          <Button
            v-if="can('manage-prescriptions') && prescription.status === 'draft'"
            icon-right="check"
            @click="issue"
          >
            Issue
          </Button>
          <Button
            v-if="can('manage-prescriptions') && prescription.status !== 'cancelled'"
            variant="danger"
            icon="x"
            @click="cancelRx"
          >
            Cancel
          </Button>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <Card title="Visit">
          <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div>
              <dt class="app-section-title mb-1">Doctor</dt>
              <dd class="font-medium">
                <span v-if="prescription.doctor_name" class="inline-flex items-center gap-2">
                  <span class="h-2.5 w-2.5 rounded-full ring-2 ring-base-100" :style="{ backgroundColor: prescription.doctor_color }" />
                  {{ prescription.doctor_name }}
                </span>
                <span v-else>—</span>
              </dd>
            </div>
            <div>
              <dt class="app-section-title mb-1">Status</dt>
              <dd class="mt-0.5">
                <Badge :variant="statusVariant(prescription.status)" dot>
                  {{ prescription.status_label }}
                </Badge>
              </dd>
            </div>
            <div>
              <dt class="app-section-title mb-1">Issued at</dt>
              <dd class="font-medium">{{ fmt(prescription.issued_at) }}</dd>
            </div>
            <div v-if="prescription.appointment_id">
              <dt class="app-section-title mb-1">Appointment</dt>
              <dd class="font-medium">
                <Link :href="`/appointments/${prescription.appointment_id}`" class="hover:text-primary">
                  #{{ prescription.appointment_id }}
                </Link>
              </dd>
            </div>
          </dl>
        </Card>

        <Card title="Diagnosis" class="md:col-span-2">
          <p class="whitespace-pre-wrap text-sm text-base-content">
            {{ prescription.diagnosis_text || '—' }}
          </p>
        </Card>
      </div>

      <Card title="ওষুধের তালিকা (Medicines)" :padded="false">
        <div v-if="!prescription.items || prescription.items.length === 0" class="p-6">
          <EmptyState icon="pill" title="No items" description="This prescription has no medicines." />
        </div>
        <div v-else class="divide-y divide-(--color-line)">
          <div
            v-for="(it, idx) in prescription.items"
            :key="it.id"
            class="p-4 grid grid-cols-12 gap-3 items-start"
          >
            <div class="col-span-1 text-center">
              <div class="text-xs text-muted">#</div>
              <div class="font-mono font-semibold">{{ idx + 1 }}</div>
            </div>
            <div class="col-span-11">
              <div class="grid grid-cols-1 sm:grid-cols-[1fr_1.4fr] gap-x-4 gap-y-0.5">
                <div class="font-medium text-base-content">
                  {{ formatMedicine(it.medicine_name, it.strength) }}
                </div>
                <div class="text-sm text-base-content/80 space-y-1">
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
            </div>
          </div>
        </div>
      </Card>

      <Card v-if="prescription.general_advice" title="সাধারণ পরামর্শ (General advice)">
        <p class="whitespace-pre-wrap text-sm text-base-content">{{ prescription.general_advice }}</p>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>