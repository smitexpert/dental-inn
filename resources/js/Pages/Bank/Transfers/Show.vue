<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ transfer: Object, movements: Array, currency: String })

const fmt = (n) => Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })

const movementTone = (type) => {
  if (type === 'debit') return 'danger'
  if (type === 'credit') return 'success'
  return 'neutral'
}

const breadcrumbs = [
  { label: 'Bank', href: '/bank' },
  { label: 'Transfers', href: '/bank/transfers' },
  { label: `#${props.transfer.id}` },
]
</script>

<template>
  <Head :title="`Transfer #${transfer.id}`" />
  <AuthenticatedLayout>
    <div class="max-w-4xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        :title="`Transfer #${transfer.id}`"
        :subtitle="`Recorded on ${transfer.occurred_at}`"
        icon="arrow-right"
        eyebrow="Transfer"
      >
        <template #actions>
          <Link :href="route('bank.transfers.index')">
            <Button variant="outline" icon="chevron-left">Back to transfers</Button>
          </Link>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <Card title="From account">
          <div class="flex items-start gap-3">
            <span
              class="h-10 w-10 rounded-xl bg-error/10 text-error inline-flex items-center justify-center shrink-0"
            >
              <Icon name="arrow-up" size="md" />
            </span>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-base-content">
                {{ transfer.from_account?.name || '—' }}
              </p>
              <p v-if="transfer.from_account?.bank_name" class="text-xs text-(--color-muted)">
                {{ transfer.from_account.bank_name }}
              </p>
              <p
                v-if="transfer.from_account?.account_number"
                class="text-xs text-(--color-muted) font-mono mt-1"
              >
                {{ transfer.from_account.account_number }}
              </p>
            </div>
          </div>
        </Card>

        <Card title="To account">
          <div class="flex items-start gap-3">
            <span
              class="h-10 w-10 rounded-xl bg-success/10 text-success inline-flex items-center justify-center shrink-0"
            >
              <Icon name="arrow-down" size="md" />
            </span>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-base-content">
                {{ transfer.to_account?.name || '—' }}
              </p>
              <p v-if="transfer.to_account?.bank_name" class="text-xs text-(--color-muted)">
                {{ transfer.to_account.bank_name }}
              </p>
              <p
                v-if="transfer.to_account?.account_number"
                class="text-xs text-(--color-muted) font-mono mt-1"
              >
                {{ transfer.to_account.account_number }}
              </p>
            </div>
          </div>
        </Card>
      </div>

      <Card title="Details">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
          <div>
            <dt class="text-(--color-muted)">Amount</dt>
            <dd class="font-semibold text-base-content mt-0.5 tabular-nums text-lg">
              {{ fmt(transfer.amount) }} {{ currency }}
            </dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Date</dt>
            <dd class="font-medium text-base-content mt-0.5">
              {{ transfer.occurred_at || '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Reference</dt>
            <dd class="font-medium text-base-content mt-0.5">
              {{ transfer.reference || '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Group</dt>
            <dd class="font-mono text-xs text-(--color-muted) mt-0.5 break-all">
              {{ transfer.transfer_group_id || '—' }}
            </dd>
          </div>
          <div v-if="transfer.note" class="sm:col-span-2">
            <dt class="text-(--color-muted)">Note</dt>
            <dd class="whitespace-pre-wrap text-base-content mt-0.5">
              {{ transfer.note }}
            </dd>
          </div>
        </dl>
      </Card>

      <Card title="Movements" subtitle="Debit and credit entries posted by this transfer" :padded="false">
        <div v-if="!movements || movements.length === 0">
          <EmptyState
            icon="list"
            title="No movements"
            description="The transfer's ledger entries will appear here once recorded."
          />
        </div>
        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Account</th>
                <th>Type</th>
                <th class="text-right">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in movements" :key="m.id">
                <td>
                  <div class="flex items-center gap-2.5">
                    <span
                      class="h-8 w-8 rounded-lg bg-primary/10 text-primary inline-flex items-center justify-center shrink-0"
                    >
                      <Icon name="banknote" size="sm" />
                    </span>
                    <span class="font-medium">{{ m.account?.name || '—' }}</span>
                  </div>
                </td>
                <td>
                  <Badge :variant="movementTone(m.type)">{{ m.type }}</Badge>
                </td>
                <td class="text-right font-semibold tabular-nums">
                  {{ fmt(m.amount) }} {{ currency }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
