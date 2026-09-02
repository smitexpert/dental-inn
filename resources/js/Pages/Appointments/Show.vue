<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import Icon from '@/Components/ui/Icon.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import { useConfirm } from '@/Composables/useConfirm.js'

const props = defineProps({ appointment: Object })
const confirm = useConfirm()

function fmt(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString()
}

function statusVariant(s) {
  return {
    scheduled: 'info',
    confirmed: 'primary',
    checked_in: 'warning',
    completed: 'success',
    cancelled: 'danger',
    no_show: 'danger',
    draft: 'neutral',
    issued: 'primary',
    partially_paid: 'warning',
    paid: 'success',
    overdue: 'danger',
    refunded: 'neutral',
  }[s] || 'neutral'
}

function money(n) {
  const num = Number(n)
  if (Number.isNaN(num)) return '0.00'
  return num.toFixed(2)
}

const transitions = computed(() => {
  const t = []
  if (props.appointment.status === 'scheduled') t.push('confirmed', 'cancelled', 'no_show')
  if (props.appointment.status === 'confirmed') t.push('checked_in', 'cancelled', 'no_show')
  if (props.appointment.status === 'checked_in') t.push('completed', 'no_show')
  return t
})

const labels = {
  scheduled: 'Mark scheduled',
  confirmed: 'Confirm',
  checked_in: 'Check in',
  completed: 'Complete',
  cancelled: 'Cancel',
  no_show: 'No show',
}

function confirmTransition(target) {
  const needsReason = ['cancelled', 'no_show'].includes(target)
  const doIt = (reason) =>
    router.patch(`/appointments/${props.appointment.id}/status`, { status: target, reason })

  if (!needsReason) {
    doIt(null)
    return
  }
  const reason = window.prompt(`Reason for ${labels[target].toLowerCase()}?`)
  if (reason === null) return
  doIt(reason || null)
}

const breadcrumbs = [
  { label: 'Appointments', href: '/appointments' },
  { label: props.appointment.patient?.name || 'Appointment' },
]
</script>

<template>
  <Head title="Appointment" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-5xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="appointment.patient?.name || 'Appointment'"
        :subtitle="appointment.patient?.patient_code || 'Visit details'"
        icon="calendar-clock"
        eyebrow="Appointment"
      >
        <template #actions>
          <Badge :variant="statusVariant(appointment.status)" dot size="md">
            {{ appointment.status_label }}
          </Badge>
          <Link :href="`/appointments/${appointment.id}/edit`">
            <Button variant="outline" icon="edit">Edit</Button>
          </Link>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <Card title="Visit">
          <dl class="grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
            <div>
              <dt class="app-section-title mb-1">Doctor</dt>
              <dd class="flex items-center gap-2 font-medium">
                <span
                  class="h-2.5 w-2.5 rounded-full ring-2 ring-base-100"
                  :style="{ backgroundColor: appointment.doctor_color }"
                />
                {{ appointment.doctor_name }}
              </dd>
            </div>
            <div>
              <dt class="app-section-title mb-1">Duration</dt>
              <dd class="font-medium">{{ appointment.duration_minutes }} minutes</dd>
            </div>
            <div>
              <dt class="app-section-title mb-1">Scheduled at</dt>
              <dd class="font-medium">{{ fmt(appointment.scheduled_at) }}</dd>
            </div>
            <div>
              <dt class="app-section-title mb-1">Ends at</dt>
              <dd class="font-medium">{{ fmt(appointment.end_at) }}</dd>
            </div>
          </dl>
        </Card>

        <Card title="Patient">
          <div v-if="appointment.patient" class="space-y-3">
            <div class="flex items-center gap-3">
              <span
                class="h-10 w-10 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center text-sm font-semibold"
              >
                {{ appointment.patient.name?.split(' ').map((s) => s[0]).slice(0, 2).join('').toUpperCase() }}
              </span>
              <div class="min-w-0">
                <Link
                  :href="`/patients/${appointment.patient.id}`"
                  class="font-medium text-base-content hover:text-primary transition-colors"
                >
                  {{ appointment.patient.name }}
                </Link>
                <div class="text-xs text-(--color-muted) font-mono">
                  {{ appointment.patient.patient_code }}
                </div>
              </div>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm pt-2 border-t border-(--color-line)">
              <div>
                <dt class="app-section-title mb-1">Phone</dt>
                <dd class="font-medium inline-flex items-center gap-1.5">
                  <Icon name="phone" size="xs" class="text-(--color-muted)" />
                  {{ appointment.patient.phone || '—' }}
                </dd>
              </div>
              <div v-if="appointment.patient.email">
                <dt class="app-section-title mb-1">Email</dt>
                <dd class="font-medium inline-flex items-center gap-1.5 break-all">
                  <Icon name="mail" size="xs" class="text-(--color-muted)" />
                  {{ appointment.patient.email }}
                </dd>
              </div>
            </dl>
          </div>
          <p v-else class="text-sm text-(--color-muted)">Patient not linked.</p>
        </Card>
      </div>

      <Card v-if="appointment.notes || appointment.cancelled_at" title="Notes & history">
        <div class="space-y-4 text-sm">
          <div v-if="appointment.notes">
            <h4 class="app-section-title mb-1.5">Notes</h4>
            <p class="whitespace-pre-wrap text-base-content">{{ appointment.notes }}</p>
          </div>
          <div v-if="appointment.cancelled_at" class="rounded-lg border border-error/20 bg-error/5 p-3">
            <h4 class="app-section-title mb-1.5 text-error">Cancellation</h4>
            <p class="text-base-content">
              Cancelled at {{ fmt(appointment.cancelled_at) }}
              <span v-if="appointment.cancelled_reason">
                · <span class="font-medium">{{ appointment.cancelled_reason }}</span>
              </span>
            </p>
          </div>
        </div>
      </Card>

      <Card v-if="transitions.length > 0" title="Status actions">
        <p class="text-sm text-(--color-muted) mb-3">
          Update the visit state. Cancellation or no-show will prompt for a reason.
        </p>
        <div class="flex flex-wrap gap-2">
          <Button
            v-for="t in transitions"
            :key="t"
            :variant="['cancelled', 'no_show'].includes(t) ? 'danger' : 'primary'"
            @click="confirmTransition(t)"
          >
            {{ labels[t] }}
          </Button>
        </div>
      </Card>

      <!--
        Invoices linked to this appointment via the appointment_id FK.
        Auto-drafted the first time the appointment reaches Completed;
        additional invoices can be linked here too if a visit spans
        multiple billing events. Each row shows the linked payments
        inline so a single click from "the visit" tells you whether
        "the sale" has been collected.
      -->
      <Card title="Invoices & payments" :padded="false">
        <div v-if="!appointment.invoices || appointment.invoices.length === 0" class="px-5 pb-5">
          <EmptyState
            icon="receipt-text"
            title="No invoices yet"
            description="An invoice will be auto-drafted when this appointment is marked Completed."
          />
        </div>
        <div v-else class="divide-y divide-(--color-line)">
          <div
            v-for="inv in appointment.invoices"
            :key="inv.id"
            class="p-5"
          >
            <div class="flex items-start justify-between gap-3 flex-wrap">
              <div>
                <Link :href="inv.show_url" class="font-mono text-base-content hover:text-primary hover:underline font-medium">
                  {{ inv.invoice_number }}
                </Link>
                <div class="text-xs text-(--color-muted) mt-1">
                  Issued {{ inv.invoice_date }} · due {{ inv.due_date }}
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Badge :variant="statusVariant(inv.status)" dot>{{ inv.status_label }}</Badge>
                <Link v-if="inv.is_editable" :href="inv.edit_url">
                  <Button variant="outline" size="sm" icon="edit">Edit</Button>
                </Link>
                <span v-else class="text-xs text-(--color-muted) italic">Read-only</span>
              </div>
            </div>
            <dl class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
              <div>
                <dt class="app-section-title mb-1">Total</dt>
                <dd class="text-base-content font-semibold">{{ money(inv.total) }}</dd>
              </div>
              <div>
                <dt class="app-section-title mb-1">Paid</dt>
                <dd class="text-base-content">{{ money(inv.paid_amount) }}</dd>
              </div>
              <div>
                <dt class="app-section-title mb-1">Balance</dt>
                <dd class="text-base-content font-semibold">{{ money(inv.balance_due) }}</dd>
              </div>
              <div>
                <dt class="app-section-title mb-1">Payments</dt>
                <dd class="text-base-content">{{ inv.payments.length }}</dd>
              </div>
            </dl>
            <div v-if="inv.payments.length > 0" class="mt-3 border-t border-(--color-line) pt-3">
              <h5 class="app-section-title mb-2">Payment history</h5>
              <ul class="space-y-1 text-xs">
                <li
                  v-for="p in inv.payments"
                  :key="p.id"
                  class="flex items-center justify-between"
                >
                  <span class="text-(--color-muted)">
                    {{ p.payment_date }} · {{ p.method_label }}
                    <span v-if="p.reference"> · {{ p.reference }}</span>
                  </span>
                  <span class="font-mono text-base-content font-medium">{{ money(p.amount) }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
