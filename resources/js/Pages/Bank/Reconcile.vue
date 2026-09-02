<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import Select from '@/Components/ui/Select.vue'
import Alert from '@/Components/ui/Alert.vue'
import Badge from '@/Components/ui/Badge.vue'
import Icon from '@/Components/ui/Icon.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import { ref, computed } from 'vue'

const props = defineProps({ accounts: Array, currency: String })

const selectedId = ref(props.accounts[0]?.id ?? null)
const selected = computed(() => props.accounts.find((a) => a.id === selectedId.value))

const form = useForm({
  account_id: selectedId.value,
  counted_balance: Number(props.accounts[0]?.balance ?? 0),
})

const accountOptions = props.accounts.map((a) => ({
  label: `${a.name} — ${Number(a.balance ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })} ${props.currency}`,
  value: a.id,
}))

const diff = computed(() => {
  if (!selected.value) return 0
  return Number(form.counted_balance || 0) - Number(selected.value.balance ?? 0)
})
const diffTone = computed(() => {
  if (Math.abs(diff.value) < 0.01) return 'success'
  return 'warning'
})
const diffLabel = computed(() => {
  if (Math.abs(diff.value) < 0.01) return 'Balanced'
  return `${diff.value > 0 ? '+' : ''}${Number(diff.value).toLocaleString(undefined, { minimumFractionDigits: 2 })} ${props.currency}`
})

function choose(id) {
  selectedId.value = id
  const acc = props.accounts.find((a) => a.id === id)
  form.account_id = id
  form.counted_balance = Number(acc?.balance ?? 0)
}

function submit() {
  form.post(route('bank.reconcile.run'))
}

const fmt = (n) => Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })

const breadcrumbs = [
  { label: 'Bank', href: '/bank' },
  { label: 'Reconcile' },
]
</script>

<template>
  <Head title="Reconcile" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="Reconcile account"
        subtitle="Match the recorded balance against a physical count or statement."
        icon="refresh"
        eyebrow="Reconciliation"
      >
        <template #actions>
          <Link :href="route('bank.dashboard')">
            <Button variant="outline">Back to bank</Button>
          </Link>
        </template>
      </PageHeader>

      <Alert variant="info" title="How reconciliation works">
        Pick an account, enter the balance you actually count on hand or see on a statement,
        and save. The system records the difference so you can investigate unmatched amounts.
      </Alert>

      <Card title="Choose account">
        <Field label="Account" html-for="account_id">
          <Select
            id="account_id"
            :model-value="selectedId"
            :options="accountOptions"
            @change="(v) => choose(v)"
          />
        </Field>

        <div v-if="selected" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
          <div class="rounded-xl border border-(--color-line) p-4">
            <p class="app-section-title">Last reconciled</p>
            <p class="text-sm font-medium text-base-content mt-1">
              {{ selected.last_reconciled_at || 'Never' }}
            </p>
          </div>
          <div class="rounded-xl border border-(--color-line) p-4">
            <p class="app-section-title">Last reconciled balance</p>
            <p class="text-sm font-medium text-base-content mt-1 tabular-nums">
              {{ selected.last_reconciled_balance != null
                ? `${fmt(selected.last_reconciled_balance)} ${currency}`
                : '—' }}
            </p>
          </div>
          <div class="rounded-xl border border-(--color-line) p-4">
            <p class="app-section-title">Current balance</p>
            <p class="text-sm font-semibold text-base-content mt-1 tabular-nums">
              {{ fmt(selected.balance) }} {{ currency }}
            </p>
          </div>
        </div>
      </Card>

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Reconcile">
          <div class="space-y-4">
            <Field
              :label="`Counted balance (${currency})`"
              :error="form.errors.counted_balance"
              :hint="`Enter the balance you actually count or see on a statement.`"
              html-for="counted_balance"
            >
              <Input
                id="counted_balance"
                v-model="form.counted_balance"
                type="number"
                step="0.01"
                min="0"
              />
            </Field>
            <p v-if="form.errors.account_id" class="text-xs font-medium text-error flex items-center gap-1">
              <Icon name="x-circle" size="xs" />
              {{ form.errors.account_id }}
            </p>

            <div
              class="flex items-center justify-between gap-3 rounded-xl border border-(--color-line) bg-base-200/40 px-4 py-3"
            >
              <div class="flex items-center gap-3 min-w-0">
                <span
                  :class="[
                    'h-9 w-9 rounded-lg inline-flex items-center justify-center shrink-0',
                    diffTone === 'success'
                      ? 'bg-success/10 text-success'
                      : 'bg-warning/15 text-warning',
                  ]"
                >
                  <Icon
                    :name="diffTone === 'success' ? 'check-circle' : 'alert-triangle'"
                    size="md"
                  />
                </span>
                <div class="min-w-0">
                  <p class="app-section-title">Difference</p>
                  <p
                    class="text-sm font-semibold mt-0.5 tabular-nums"
                    :class="diffTone === 'success' ? 'text-success' : 'text-warning'"
                  >
                    {{ diffLabel }}
                  </p>
                </div>
              </div>
              <Badge :variant="diffTone" dot>
                {{ diffTone === 'success' ? 'Balanced' : 'Review' }}
              </Badge>
            </div>
          </div>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="route('bank.dashboard')">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button
            type="submit"
            :loading="form.processing"
            icon="check"
          >
            Reconcile
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
