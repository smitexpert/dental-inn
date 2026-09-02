<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import Dialog from '@/Components/ui/Dialog.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import Field from '@/Components/ui/Form/Field.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Icon from '@/Components/ui/Icon.vue'
import Stat from '@/Components/ui/Stat.vue'

const props = defineProps({ invoice: Object })

const invoice = props.invoice

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
  const m = {
    draft: 'neutral',
    issued: 'info',
    partially_paid: 'warning',
    paid: 'success',
    overdue: 'danger',
    cancelled: 'neutral',
    refunded: 'neutral',
  }
  return m[s] ?? 'neutral'
}

const breadcrumbs = [
  { label: 'Invoices', href: '/billing/invoices' },
  { label: invoice.invoice_number },
]
</script>

<template>
  <Head :title="`Invoice ${invoice.invoice_number}`" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-5xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Invoice ${invoice.invoice_number}`"
        :subtitle="invoice.patient?.name ?? 'No patient'"
        icon="receipt-text"
        eyebrow="Billing"
      >
        <template #actions>
          <Badge :variant="statusBadge(invoice.status)" dot>{{ invoice.status_label }}</Badge>
          <Link v-if="invoice.status === 'draft'" :href="`/billing/invoices/${invoice.id}/edit`">
            <Button variant="outline" icon="edit">Edit</Button>
          </Link>
          <Link href="/billing/invoices">
            <Button variant="ghost">Back</Button>
          </Link>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Stat
          label="Total"
          :value="invoice.total"
          icon="banknote"
          tone="primary"
        />
        <Stat
          label="Paid"
          :value="invoice.paid_amount"
          icon="check-circle"
          tone="success"
        />
        <Stat
          label="Balance due"
          :value="invoice.balance_due"
          icon="wallet"
          :tone="Number(invoice.balance_due) > 0 ? 'warning' : 'success'"
        />
        <Stat
          label="Issued"
          :value="invoice.invoice_date"
          icon="calendar"
          tone="neutral"
          :hint="invoice.due_date ? `Due ${invoice.due_date}` : 'No due date'"
        />
      </div>

      <Card title="Invoice details">
        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-3 text-sm">
          <div>
            <dt class="text-(--color-muted)">Invoice date</dt>
            <dd class="font-medium mt-0.5">{{ invoice.invoice_date }}</dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Due date</dt>
            <dd class="font-medium mt-0.5">{{ invoice.due_date }}</dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Created by</dt>
            <dd class="font-medium mt-0.5">{{ invoice.creator?.name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Issued at</dt>
            <dd class="font-medium mt-0.5">{{ invoice.issued_at ?? '—' }}</dd>
          </div>
        </dl>

        <div class="mt-6 overflow-x-auto -mx-1">
          <table class="app-table">
            <thead>
              <tr>
                <th>Description</th>
                <th class="text-right w-20">Qty</th>
                <th class="text-right w-28">Unit price</th>
                <th class="text-right w-32">Line total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in invoice.items" :key="item.id">
                <td class="font-medium">{{ item.description }}</td>
                <td class="text-right text-(--color-muted) tabular-nums">{{ item.quantity }}</td>
                <td class="text-right text-(--color-muted) tabular-nums">{{ item.unit_price }}</td>
                <td class="text-right font-medium tabular-nums">{{ item.line_total }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-6 flex justify-end">
          <dl class="w-full max-w-xs space-y-1.5 text-sm">
            <div class="flex justify-between text-(--color-muted)">
              <dt>Subtotal</dt>
              <dd class="tabular-nums text-base-content">{{ invoice.subtotal }}</dd>
            </div>
            <div class="flex justify-between text-(--color-muted)">
              <dt>Discount</dt>
              <dd class="tabular-nums text-base-content">-{{ invoice.discount_amount }}</dd>
            </div>
            <div class="flex justify-between text-(--color-muted)">
              <dt>Tax</dt>
              <dd class="tabular-nums text-base-content">{{ invoice.tax_amount }}</dd>
            </div>
            <div class="flex justify-between font-semibold border-t border-(--color-line) pt-2 mt-1">
              <dt>Total</dt>
              <dd class="tabular-nums">{{ invoice.total }}</dd>
            </div>
            <div class="flex justify-between text-success">
              <dt>Paid</dt>
              <dd class="tabular-nums">{{ invoice.paid_amount }}</dd>
            </div>
            <div class="flex justify-between font-semibold text-base-content">
              <dt>Balance due</dt>
              <dd class="tabular-nums">{{ invoice.balance_due }}</dd>
            </div>
          </dl>
        </div>

        <div v-if="invoice.notes" class="mt-6 pt-4 border-t border-(--color-line)">
          <h4 class="text-sm font-semibold text-base-content mb-1">Notes</h4>
          <p class="text-sm text-(--color-muted) whitespace-pre-wrap">{{ invoice.notes }}</p>
        </div>

        <div class="mt-6 pt-4 border-t border-(--color-line) flex flex-wrap items-center gap-2">
          <Button
            v-if="invoice.status === 'draft'"
            icon-right="send"
            @click="issue"
          >
            Issue invoice
          </Button>
          <Button
            v-if="['issued', 'partially_paid', 'overdue'].includes(invoice.status)"
            icon="credit-card"
            @click="paymentOpen = true"
          >
            Record payment
          </Button>
          <Button
            v-if="invoice.status === 'draft'"
            variant="ghost"
            icon="x"
            @click="cancel"
          >
            Cancel invoice
          </Button>
        </div>
      </Card>

      <Card title="Payments" :padded="false">
        <EmptyState
          v-if="invoice.payments.length === 0"
          icon="credit-card"
          title="No payments recorded yet"
          description="Record a payment to update the invoice balance."
        />
        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Date</th>
                <th class="text-right">Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Received by</th>
                <th class="w-24 text-right">Receipt</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in invoice.payments" :key="p.id">
                <td class="text-(--color-muted)">{{ p.payment_date }}</td>
                <td class="text-right font-medium tabular-nums">{{ p.amount }}</td>
                <td>{{ p.method_label }}</td>
                <td class="text-(--color-muted)">{{ p.reference ?? '—' }}</td>
                <td>{{ p.receiver ?? '—' }}</td>
                <td class="text-right">
                  <Link
                    :href="`/billing/payments/${p.id}/receipt`"
                    class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors inline-flex"
                    aria-label="View receipt"
                    title="View receipt"
                  >
                    <Icon name="receipt-text" size="sm" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>

    <Dialog v-model:visible="paymentOpen" title="Record payment" size="lg">
      <form @submit.prevent="submitPayment" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Field label="Date" required>
            <Input v-model="paymentForm.payment_date" type="date" />
          </Field>
          <Field label="Amount" required>
            <Input v-model="paymentForm.amount" type="number" min="0.01" step="0.01" />
          </Field>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Field label="Method" required>
            <Select v-model="paymentForm.method" :options="paymentMethods" />
          </Field>
          <Field label="Currency">
            <Input v-model="paymentForm.currency_code" />
          </Field>
        </div>
        <Field label="Reference">
          <Input v-model="paymentForm.reference" placeholder="Optional reference / receipt number" />
        </Field>
        <div class="flex justify-end gap-2 pt-2">
          <Button type="button" variant="ghost" @click="paymentOpen = false">Cancel</Button>
          <Button type="submit" :loading="paymentForm.processing" icon-right="check">Save</Button>
        </div>
      </form>
    </Dialog>
  </AuthenticatedLayout>
</template>
