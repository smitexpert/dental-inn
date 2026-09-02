<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Stat from '@/Components/ui/Stat.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ accounts: Object, currency: String })

const totalBalance = (props.accounts?.data ?? []).reduce(
  (acc, a) => acc + Number(a.balance ?? 0),
  0,
)
const activeCount = (props.accounts?.data ?? []).filter((a) => a.is_active).length
const inactiveCount = (props.accounts?.data ?? []).filter((a) => !a.is_active).length
const lastReconciled = (props.accounts?.data ?? [])
  .filter((a) => a.last_reconciled_at)
  .sort((a, b) => new Date(b.last_reconciled_at) - new Date(a.last_reconciled_at))[0]

const fmt = (n) => Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const typeIcon = (t) => ({ cash: 'banknote', bank: 'building', external: 'wallet' })[t] || 'banknote'
const typeTone = (t) => ({ cash: 'success', bank: 'primary', external: 'info' })[t] || 'neutral'
</script>

<template>
  <Head title="Bank & Cash" />
  <AuthenticatedLayout>
    <div class="space-y-6">
      <PageHeader
        title="Bank & Cash"
        subtitle="Manage bank accounts, cash balances, transfers and reconciliation."
        icon="banknote"
        eyebrow="Finance"
      >
        <template #actions>
          <Link :href="route('bank.reconcile.index')">
            <Button variant="outline" icon="refresh">Reconcile</Button>
          </Link>
          <Link :href="route('bank.transfers.create')">
            <Button variant="soft" icon="arrow-right">New transfer</Button>
          </Link>
          <Link :href="route('bank.accounts.create')">
            <Button icon="plus">New account</Button>
          </Link>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Stat
          label="Total balance"
          :value="`${fmt(totalBalance)} ${currency}`"
          hint="Across all accounts"
          icon="banknote"
          tone="primary"
        />
        <Stat
          label="Active accounts"
          :value="activeCount"
          :hint="`${props.accounts?.total ?? 0} total`"
          icon="check-circle"
          tone="success"
        />
        <Stat
          label="Inactive accounts"
          :value="inactiveCount"
          hint="Not in use"
          icon="alert-triangle"
          tone="warning"
        />
        <Stat
          label="Last reconciled"
          :value="lastReconciled?.name ?? '—'"
          :hint="lastReconciled?.last_reconciled_at ?? 'Never'"
          icon="refresh"
          tone="info"
        />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card
          title="Accounts overview"
          subtitle="All bank, cash and external accounts"
          class="lg:col-span-2"
          :padded="false"
        >
          <div v-if="(accounts?.data?.length ?? 0) === 0">
            <EmptyState
              icon="banknote"
              title="No accounts yet"
              description="Add your first bank or cash account to start tracking balances and transfers."
            >
              <template #action>
                <Link :href="route('bank.accounts.create')">
                  <Button icon="plus">New account</Button>
                </Link>
              </template>
            </EmptyState>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="app-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Type</th>
                  <th>Bank</th>
                  <th class="text-right">Balance</th>
                  <th>Last reconciled</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in accounts.data" :key="a.id">
                  <td>
                    <div class="flex items-center gap-2.5">
                      <span
                        :class="[
                          'h-8 w-8 rounded-lg inline-flex items-center justify-center shrink-0',
                          a.type === 'cash'
                            ? 'bg-success/10 text-success'
                            : a.type === 'bank'
                              ? 'bg-primary/10 text-primary'
                              : 'bg-info/10 text-info',
                        ]"
                      >
                        <Icon :name="typeIcon(a.type)" size="sm" />
                      </span>
                      <div class="min-w-0">
                        <p class="font-medium text-base-content">{{ a.name }}</p>
                        <p class="text-xs text-(--color-muted)">
                          {{ a.account_number || '—' }}
                        </p>
                      </div>
                    </div>
                  </td>
                  <td>
                    <Badge :variant="typeTone(a.type)">{{ a.type }}</Badge>
                  </td>
                  <td class="text-(--color-muted)">{{ a.bank_name || '—' }}</td>
                  <td class="text-right font-semibold tabular-nums">
                    {{ fmt(a.balance) }} {{ currency }}
                  </td>
                  <td class="text-(--color-muted)">
                    {{ a.last_reconciled_at || 'Never' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </Card>

        <Card title="Quick actions" subtitle="Common tasks">
          <div class="space-y-2.5">
            <Link
              :href="route('bank.accounts.create')"
              class="flex items-center justify-between gap-3 p-3 rounded-xl bg-base-200/50 hover:bg-base-200 transition-colors group"
            >
              <span class="flex items-center gap-3 min-w-0">
                <span
                  class="h-9 w-9 rounded-lg bg-primary/10 text-primary inline-flex items-center justify-center"
                >
                  <Icon name="plus" size="md" />
                </span>
                <span class="min-w-0">
                  <span class="block text-sm font-medium text-base-content">Add account</span>
                  <span class="block text-xs text-(--color-muted)">
                    Bank, cash or external
                  </span>
                </span>
              </span>
              <Icon
                name="chevron-right"
                size="sm"
                class="text-(--color-muted) group-hover:text-primary transition-colors"
              />
            </Link>
            <Link
              :href="route('bank.transfers.create')"
              class="flex items-center justify-between gap-3 p-3 rounded-xl bg-base-200/50 hover:bg-base-200 transition-colors group"
            >
              <span class="flex items-center gap-3 min-w-0">
                <span
                  class="h-9 w-9 rounded-lg bg-secondary/10 text-secondary inline-flex items-center justify-center"
                >
                  <Icon name="arrow-right" size="md" />
                </span>
                <span class="min-w-0">
                  <span class="block text-sm font-medium text-base-content">Record transfer</span>
                  <span class="block text-xs text-(--color-muted)">Move funds between accounts</span>
                </span>
              </span>
              <Icon
                name="chevron-right"
                size="sm"
                class="text-(--color-muted) group-hover:text-primary transition-colors"
              />
            </Link>
            <Link
              :href="route('bank.reconcile.index')"
              class="flex items-center justify-between gap-3 p-3 rounded-xl bg-base-200/50 hover:bg-base-200 transition-colors group"
            >
              <span class="flex items-center gap-3 min-w-0">
                <span
                  class="h-9 w-9 rounded-lg bg-info/10 text-info inline-flex items-center justify-center"
                >
                  <Icon name="refresh" size="md" />
                </span>
                <span class="min-w-0">
                  <span class="block text-sm font-medium text-base-content">Reconcile</span>
                  <span class="block text-xs text-(--color-muted)">Match counted vs recorded</span>
                </span>
              </span>
              <Icon
                name="chevron-right"
                size="sm"
                class="text-(--color-muted) group-hover:text-primary transition-colors"
              />
            </Link>
            <Link
              :href="route('bank.transfers.index')"
              class="flex items-center justify-between gap-3 p-3 rounded-xl bg-base-200/50 hover:bg-base-200 transition-colors group"
            >
              <span class="flex items-center gap-3 min-w-0">
                <span
                  class="h-9 w-9 rounded-lg bg-base-300 text-base-content/70 inline-flex items-center justify-center"
                >
                  <Icon name="list" size="md" />
                </span>
                <span class="min-w-0">
                  <span class="block text-sm font-medium text-base-content">Transfer history</span>
                  <span class="block text-xs text-(--color-muted)">View all transfers</span>
                </span>
              </span>
              <Icon
                name="chevron-right"
                size="sm"
                class="text-(--color-muted) group-hover:text-primary transition-colors"
              />
            </Link>
          </div>
        </Card>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
