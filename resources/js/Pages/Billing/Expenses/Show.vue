<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'

const props = defineProps({ expense: Object })

function approve() {
  router.post(`/billing/expenses/${props.expense.id}/approve`)
}
function reject() {
  router.post(`/billing/expenses/${props.expense.id}/reject`)
}
</script>

<template>
  <Head title="Expense detail" />
  <AuthenticatedLayout>
    <UiCard>
      <template #header>
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-foreground">Expense #{{ expense.id }}</h3>
            <p class="text-xs text-muted-foreground mt-1">{{ expense.category.name }} · {{ expense.vendor ?? '—' }}</p>
          </div>
          <div class="flex items-center gap-2">
            <UiBadge :variant="expense.status_color">{{ expense.status_label }}</UiBadge>
            <Link href="/billing/expenses"><UiButton variant="ghost">Back</UiButton></Link>
          </div>
        </div>
      </template>

      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-muted-foreground text-xs">Date</div>
          <div class="text-foreground">{{ expense.expense_date }}</div>
        </div>
        <div>
          <div class="text-muted-foreground text-xs">Amount</div>
          <div class="text-foreground text-2xl font-semibold">{{ expense.amount }} {{ expense.currency_code }}</div>
        </div>
        <div>
          <div class="text-muted-foreground text-xs">Payment method</div>
          <div class="text-foreground">{{ expense.payment_method_label }}</div>
        </div>
        <div>
          <div class="text-muted-foreground text-xs">Submitted by</div>
          <div class="text-foreground">{{ expense.creator ?? '—' }}</div>
        </div>
        <div v-if="expense.approver">
          <div class="text-muted-foreground text-xs">Approved by</div>
          <div class="text-foreground">{{ expense.approver }} on {{ expense.approved_at }}</div>
        </div>
        <div v-if="expense.description">
          <div class="text-muted-foreground text-xs">Description</div>
          <div class="text-foreground">{{ expense.description }}</div>
        </div>
        <div v-if="expense.receipt_url" class="col-span-2">
          <a :href="expense.receipt_url" target="_blank" class="text-primary-600 hover:underline text-sm">View receipt →</a>
        </div>
      </div>

      <div v-if="expense.status === 'pending'" class="mt-6 flex items-center gap-2">
        <UiButton @click="approve">Approve</UiButton>
        <UiButton variant="danger" @click="reject">Reject</UiButton>
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
