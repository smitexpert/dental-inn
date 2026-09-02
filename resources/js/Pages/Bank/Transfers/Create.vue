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
import Textarea from '@/Components/ui/Textarea.vue'
import Alert from '@/Components/ui/Alert.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const props = defineProps({ accounts: Array, currency: String })

const form = useForm({
  from_account_id: props.accounts[0]?.id ?? '',
  to_account_id: '',
  amount: '',
  occurred_at: new Date().toISOString().slice(0, 16),
  reference: '',
  note: '',
})

const fromOptions = props.accounts.map((a) => ({
  label: `${a.name} (${Number(a.balance ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })})`,
  value: a.id,
  disabled: false,
}))
const toOptions = props.accounts.map((a) => ({
  label: `${a.name} (${Number(a.balance ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })})`,
  value: a.id,
  disabled: a.id === form.from_account_id,
}))

function submit() {
  form.post(route('bank.transfers.store'))
}

const breadcrumbs = [
  { label: 'Bank', href: '/bank' },
  { label: 'Transfers', href: '/bank/transfers' },
  { label: 'New transfer' },
]
</script>

<template>
  <Head title="New transfer" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="New transfer"
        subtitle="Move funds between two accounts."
        icon="arrow-right"
        eyebrow="Record transfer"
      />

      <Alert variant="info" title="Heads up">
        Transfers are atomic — both the debit on the source and the credit on the destination
        are posted together. Make sure amounts and accounts are correct before saving.
      </Alert>

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Transfer details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="From account" :error="form.errors.from_account_id" required html-for="from_account_id">
              <Select
                id="from_account_id"
                v-model="form.from_account_id"
                :options="fromOptions"
                placeholder="Select source account"
              />
            </Field>
            <Field label="To account" :error="form.errors.to_account_id" required html-for="to_account_id">
              <Select
                id="to_account_id"
                v-model="form.to_account_id"
                :options="toOptions"
                placeholder="Select destination account"
              />
            </Field>
            <Field :label="`Amount (${currency})`" :error="form.errors.amount" required html-for="amount">
              <Input
                id="amount"
                v-model="form.amount"
                type="number"
                step="0.01"
                min="0.01"
                placeholder="0.00"
              />
            </Field>
            <Field label="Date" :error="form.errors.occurred_at" html-for="occurred_at">
              <Input
                id="occurred_at"
                v-model="form.occurred_at"
                type="datetime-local"
              />
            </Field>
            <Field label="Reference" :error="form.errors.reference" html-for="reference" class="sm:col-span-2">
              <Input
                id="reference"
                v-model="form.reference"
                placeholder="Optional — e.g. cheque #, transfer ID"
              />
            </Field>
            <Field
              label="Note"
              :error="form.errors.note"
              html-for="note"
              class="sm:col-span-2"
            >
              <Textarea
                id="note"
                v-model="form.note"
                :rows="3"
                placeholder="Any context worth recording…"
              />
            </Field>
          </div>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="route('bank.transfers.index')">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button
            type="submit"
            :loading="form.processing"
            icon-right="check"
          >
            Record transfer
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
