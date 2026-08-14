<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'

const props = defineProps({ payment: Object })
</script>

<template>
  <Head title="Payment receipt" />
  <AuthenticatedLayout>
    <UiCard title="Payment receipt">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Receipt #{{ payment.id }}</h3>
          <Link href="/billing/payments"><UiButton variant="ghost">Back</UiButton></Link>
        </div>
      </template>

      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-base-content/70 text-xs">Payment date</div>
          <div class="text-base-content">{{ payment.payment_date }}</div>
        </div>
        <div>
          <div class="text-base-content/70 text-xs">Method</div>
          <div class="text-base-content">{{ payment.method_label }}</div>
        </div>
        <div>
          <div class="text-base-content/70 text-xs">Amount</div>
          <div class="text-base-content text-2xl font-semibold">{{ payment.amount }} {{ payment.currency_code }}</div>
        </div>
        <div>
          <div class="text-base-content/70 text-xs">Received by</div>
          <div class="text-base-content">{{ payment.receiver ?? '—' }}</div>
        </div>
        <div v-if="payment.reference" class="col-span-2">
          <div class="text-base-content/70 text-xs">Reference</div>
          <div class="text-base-content">{{ payment.reference }}</div>
        </div>
      </div>

      <div class="mt-6 border-t border-base-300 pt-4">
        <h4 class="text-sm font-medium text-base-content mb-2">Linked invoice</h4>
        <div v-if="payment.invoice" class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <div class="text-base-content/70 text-xs">Invoice number</div>
            <div class="text-base-content font-mono">{{ payment.invoice.invoice_number }}</div>
          </div>
          <div>
            <div class="text-base-content/70 text-xs">Patient</div>
            <div class="text-base-content">{{ payment.invoice.patient?.name ?? '—' }}</div>
          </div>
          <div>
            <div class="text-base-content/70 text-xs">Invoice total</div>
            <div class="text-base-content">{{ payment.invoice.total }}</div>
          </div>
        </div>
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
