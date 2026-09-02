<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Stat from '@/Components/ui/Stat.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ payment: Object })

const payment = props.payment

const breadcrumbs = [
  { label: 'Payments', href: '/billing/payments' },
  { label: `Receipt #${payment.id}` },
]
</script>

<template>
  <Head title="Payment receipt" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-3xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Receipt #${payment.id}`"
        :subtitle="`Recorded on ${payment.payment_date}`"
        icon="receipt-text"
        eyebrow="Receipt"
      >
        <template #actions>
          <Link href="/billing/payments">
            <Button variant="outline" icon="arrow-right">Back to payments</Button>
          </Link>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Stat
          label="Amount"
          :value="`${payment.amount} ${payment.currency_code}`"
          icon="banknote"
          tone="success"
        />
        <Stat
          label="Method"
          :value="payment.method_label"
          icon="credit-card"
          tone="primary"
        />
        <Stat
          label="Received by"
          :value="payment.receiver ?? '—'"
          icon="user"
          tone="neutral"
        />
      </div>

      <Card title="Payment details">
        <dl class="grid grid-cols-2 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
          <div>
            <dt class="text-(--color-muted)">Payment date</dt>
            <dd class="font-medium mt-0.5">{{ payment.payment_date }}</dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Method</dt>
            <dd class="font-medium mt-0.5">{{ payment.method_label }}</dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Currency</dt>
            <dd class="font-medium mt-0.5">{{ payment.currency_code }}</dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Received by</dt>
            <dd class="font-medium mt-0.5">{{ payment.receiver ?? '—' }}</dd>
          </div>
          <div v-if="payment.reference" class="col-span-2">
            <dt class="text-(--color-muted)">Reference</dt>
            <dd class="font-medium mt-0.5">{{ payment.reference }}</dd>
          </div>
          <div v-if="payment.notes" class="col-span-2">
            <dt class="text-(--color-muted)">Notes</dt>
            <dd class="font-medium mt-0.5 whitespace-pre-wrap">{{ payment.notes }}</dd>
          </div>
        </dl>
      </Card>

      <Card title="Linked invoice">
        <div v-if="payment.invoice">
          <dl class="grid grid-cols-2 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div>
              <dt class="text-(--color-muted)">Invoice number</dt>
              <dd class="font-medium mt-0.5 font-mono">
                <Link :href="`/billing/invoices/${payment.invoice.id}`" class="text-primary hover:underline">
                  {{ payment.invoice.invoice_number }}
                </Link>
              </dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Patient</dt>
              <dd class="font-medium mt-0.5">{{ payment.invoice.patient?.name ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Invoice total</dt>
              <dd class="font-medium mt-0.5 tabular-nums">{{ payment.invoice.total }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Balance due</dt>
              <dd class="font-medium mt-0.5 tabular-nums">{{ payment.invoice.balance_due }}</dd>
            </div>
          </dl>
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
