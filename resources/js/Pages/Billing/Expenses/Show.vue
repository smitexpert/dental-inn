<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import Stat from '@/Components/ui/Stat.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ expense: Object })

const expense = props.expense

function approve() {
  router.post(`/billing/expenses/${props.expense.id}/approve`)
}
function reject() {
  router.post(`/billing/expenses/${props.expense.id}/reject`)
}

const breadcrumbs = [
  { label: 'Expenses', href: '/billing/expenses' },
  { label: `Expense #${expense.id}` },
]
</script>

<template>
  <Head title="Expense detail" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-4xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Expense #${expense.id}`"
        :subtitle="`${expense.category.name} · ${expense.vendor ?? '—'}`"
        icon="wallet"
        eyebrow="Expense"
      >
        <template #actions>
          <Badge :variant="expense.status_color" dot>{{ expense.status_label }}</Badge>
          <Link v-if="expense.status === 'pending'" :href="`/billing/expenses/${expense.id}/edit`">
            <Button variant="outline" icon="edit">Edit</Button>
          </Link>
          <Link href="/billing/expenses">
            <Button variant="ghost">Back</Button>
          </Link>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Stat
          label="Amount"
          :value="`${expense.amount} ${expense.currency_code}`"
          icon="banknote"
          tone="primary"
        />
        <Stat
          label="Date"
          :value="expense.expense_date"
          icon="calendar"
          tone="neutral"
        />
        <Stat
          label="Payment method"
          :value="expense.payment_method_label"
          icon="credit-card"
          tone="info"
        />
        <Stat
          label="Status"
          :value="expense.status_label"
          icon="check-circle"
          :tone="expense.status_color === 'success' ? 'success' : expense.status_color === 'danger' ? 'danger' : expense.status_color === 'warning' ? 'warning' : 'neutral'"
        />
      </div>

      <Card title="Expense details">
        <dl class="grid grid-cols-2 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
          <div>
            <dt class="text-muted">Date</dt>
            <dd class="font-medium mt-0.5">{{ expense.expense_date }}</dd>
          </div>
          <div>
            <dt class="text-muted">Category</dt>
            <dd class="font-medium mt-0.5">{{ expense.category.name }}</dd>
          </div>
          <div>
            <dt class="text-muted">Vendor</dt>
            <dd class="font-medium mt-0.5">{{ expense.vendor ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-muted">Submitted by</dt>
            <dd class="font-medium mt-0.5">{{ expense.creator ?? '—' }}</dd>
          </div>
          <div v-if="expense.approver" class="col-span-2">
            <dt class="text-muted">Approved by</dt>
            <dd class="font-medium mt-0.5">{{ expense.approver }} on {{ expense.approved_at }}</dd>
          </div>
          <div v-if="expense.description" class="col-span-2">
            <dt class="text-muted">Description</dt>
            <dd class="font-medium mt-0.5 whitespace-pre-wrap">{{ expense.description }}</dd>
          </div>
        </dl>

        <div v-if="expense.receipt_url" class="mt-5 pt-4 border-t border-(--color-line)">
          <a
            :href="expense.receipt_url"
            target="_blank"
            class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
          >
            <Icon name="download" size="sm" />
            View receipt
          </a>
        </div>

        <div v-if="expense.status === 'pending'" class="mt-5 pt-4 border-t border-(--color-line) flex flex-wrap items-center gap-2">
          <Button icon="check" @click="approve">Approve</Button>
          <Button variant="danger" icon="x" @click="reject">Reject</Button>
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
