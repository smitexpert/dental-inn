<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiDialog from '@/Components/ui/Dialog.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiField from '@/Components/ui/Form/Field.vue'

const props = defineProps({ invoice: Object })

const paymentOpen = ref(false)

const paymentMethods = [
  { label: 'Cash', value: 'cash' },
  { label: 'Card', value: 'card' },
  { label: 'Bank transfer', value: 'bank_transfer' },
  { label: 'Mobile wallet', value: 'mobile_wallet' },
  { label: 'Other', value: 'other' },
]

const paymentForm = useForm({
  invoice_id: props.invoice.id,
  payment_date: new Date().toISOString().slice(0, 10),
  amount: '',
  currency_code: 'BDT',
  exchange_rate: '1.0',
  method: 'cash',
  reference: '',
  notes: '',
})

function submitPayment() {
  paymentForm.post('/billing/payments', { onSuccess: () => (paymentOpen.value = false) })
}

function issue() {
  router.post(`/billing/invoices/${props.invoice.id}/issue`)
}
function cancel() {
  router.post(`/billing/invoices/${props.invoice.id}/cancel`)
}

function statusBadge(s) {
  const m = { draft: 'neutral', issued: 'info', partially_paid: 'warning', paid: 'success', overdue: 'danger', cancelled: 'neutral', refunded: 'neutral' }
  return m[s] ?? 'neutral'
}
</script>

<template>
  <Head :title="`Invoice ${invoice.invoice_number}`" />
  <AuthenticatedLayout>
    <div class="space-y-4">
      <UiCard>
        <template #header>
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-base font-semibold text-base-content">Invoice {{ invoice.invoice_number }}</h3>
              <p class="text-xs text-base-content/70 mt-1">
                {{ invoice.patient?.name ?? 'No patient' }}
                <span v-if="invoice.patient">· {{ invoice.patient.patient_code }}</span>
              </p>
            </div>
            <div class="flex items-center gap-2">
              <UiBadge :variant="statusBadge(invoice.status)">{{ invoice.status_label }}</UiBadge>
              <Link href="/billing/invoices"><UiButton variant="ghost">Back</UiButton></Link>
            </div>
          </div>
        </template>

        <div class="grid grid-cols-4 gap-4 text-sm">
          <div>
            <div class="text-base-content/70 text-xs">Invoice date</div>
            <div class="text-base-content">{{ invoice.invoice_date }}</div>
          </div>
          <div>
            <div class="text-base-content/70 text-xs">Due date</div>
            <div class="text-base-content">{{ invoice.due_date }}</div>
          </div>
          <div>
            <div class="text-base-content/70 text-xs">Created by</div>
            <div class="text-base-content">{{ invoice.creator?.name ?? '—' }}</div>
          </div>
          <div>
            <div class="text-base-content/70 text-xs">Issued at</div>
            <div class="text-base-content">{{ invoice.issued_at ?? '—' }}</div>
          </div>
        </div>

        <div class="mt-6 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-base-200 text-base-content/70">
              <tr>
                <th class="text-left px-4 py-2">Description</th>
                <th class="text-right px-4 py-2">Qty</th>
                <th class="text-right px-4 py-2">Unit price</th>
                <th class="text-right px-4 py-2">Line total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in invoice.items" :key="item.id" class="border-b border-base-300 last:border-b-0">
                <td class="px-4 py-2 text-base-content">{{ item.description }}</td>
                <td class="px-4 py-2 text-right text-base-content">{{ item.quantity }}</td>
                <td class="px-4 py-2 text-right text-base-content">{{ item.unit_price }}</td>
                <td class="px-4 py-2 text-right text-base-content font-medium">{{ item.line_total }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-end">
          <div class="w-64 space-y-1 text-sm">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ invoice.subtotal }}</span></div>
            <div class="flex justify-between"><span>Discount</span><span>-{{ invoice.discount_amount }}</span></div>
            <div class="flex justify-between"><span>Tax</span><span>{{ invoice.tax_amount }}</span></div>
            <div class="flex justify-between font-semibold border-t border-base-300 pt-1 mt-1">
              <span>Total</span><span>{{ invoice.total }}</span>
            </div>
            <div class="flex justify-between text-success"><span>Paid</span><span>{{ invoice.paid_amount }}</span></div>
            <div class="flex justify-between font-semibold"><span>Balance due</span><span>{{ invoice.balance_due }}</span></div>
          </div>
        </div>

        <div class="mt-6 flex items-center gap-2">
          <UiButton v-if="invoice.status === 'draft'" @click="issue">Issue invoice</UiButton>
          <UiButton v-if="['issued', 'partially_paid', 'overdue'].includes(invoice.status)" @click="paymentOpen = true">Record payment</UiButton>
          <UiButton v-if="invoice.status === 'draft'" variant="ghost" @click="cancel">Cancel</UiButton>
        </div>
      </UiCard>

      <UiCard title="Payments">
        <div v-if="invoice.payments.length === 0" class="text-sm text-base-content/70">No payments recorded yet.</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-base-200 text-base-content/70">
              <tr>
                <th class="text-left px-4 py-2">Date</th>
                <th class="text-right px-4 py-2">Amount</th>
                <th class="text-left px-4 py-2">Method</th>
                <th class="text-left px-4 py-2">Reference</th>
                <th class="text-left px-4 py-2">Received by</th>
                <th class="text-right px-4 py-2">Receipt</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in invoice.payments" :key="p.id" class="border-b border-base-300 last:border-b-0">
                <td class="px-4 py-2">{{ p.payment_date }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ p.amount }}</td>
                <td class="px-4 py-2">{{ p.method_label }}</td>
                <td class="px-4 py-2 text-base-content/70">{{ p.reference ?? '—' }}</td>
                <td class="px-4 py-2">{{ p.receiver ?? '—' }}</td>
                <td class="px-4 py-2 text-right">
                  <Link :href="`/billing/payments/${p.id}/receipt`" class="text-primary hover:underline">Receipt</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>
    </div>

    <UiDialog v-model:visible="paymentOpen" header="Record payment">
      <form @submit.prevent="submitPayment" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Date" required>
            <UiInput v-model="paymentForm.payment_date" type="date" />
          </UiField>
          <UiField label="Amount" required>
            <UiInput v-model="paymentForm.amount" type="number" min="0.01" step="0.01" />
          </UiField>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Method" required>
            <UiSelect v-model="paymentForm.method" :options="paymentMethods" />
          </UiField>
          <UiField label="Currency">
            <UiInput v-model="paymentForm.currency_code" />
          </UiField>
        </div>
        <UiField label="Reference">
          <UiInput v-model="paymentForm.reference" />
        </UiField>
        <div class="flex justify-end gap-2">
          <UiButton type="button" variant="ghost" @click="paymentOpen = false">Cancel</UiButton>
          <UiButton type="submit" :processing="paymentForm.processing">Save</UiButton>
        </div>
      </form>
    </UiDialog>
  </AuthenticatedLayout>
</template>
