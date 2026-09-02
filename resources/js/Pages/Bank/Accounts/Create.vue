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
import Switch from '@/Components/ui/Switch.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

defineProps({ currency: String })

const form = useForm({
  name: '',
  type: 'bank',
  bank_name: '',
  account_name: '',
  branch: '',
  account_number: '',
  opening_balance: 0,
  is_active: true,
})

const typeOptions = [
  { label: 'Cash', value: 'cash' },
  { label: 'Bank', value: 'bank' },
  { label: 'External', value: 'external' },
]

function submit() {
  form.post(route('bank.accounts.store'))
}

const breadcrumbs = [
  { label: 'Bank', href: '/bank' },
  { label: 'Accounts', href: '/bank/accounts' },
  { label: 'New account' },
]
</script>

<template>
  <Head title="New account" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="New account"
        subtitle="Add a bank, cash or external account."
        icon="banknote"
        eyebrow="Add account"
      />

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Account details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Name" :error="form.errors.name" required html-for="name">
              <Input
                id="name"
                v-model="form.name"
                required
                placeholder="Petty cash, Main bank, etc."
              />
            </Field>
            <Field label="Type" :error="form.errors.type" html-for="type">
              <Select
                id="type"
                v-model="form.type"
                :options="typeOptions"
                placeholder="Select type"
              />
            </Field>
            <template v-if="form.type === 'bank'">
              <Field label="Bank name" :error="form.errors.bank_name" html-for="bank_name">
                <Input id="bank_name" v-model="form.bank_name" placeholder="e.g. ABC Bank" />
              </Field>
              <Field label="Account name" :error="form.errors.account_name" html-for="account_name">
                <Input id="account_name" v-model="form.account_name" />
              </Field>
              <Field label="Branch" html-for="branch">
                <Input id="branch" v-model="form.branch" />
              </Field>
              <Field label="Account number" :error="form.errors.account_number" html-for="account_number">
                <Input id="account_number" v-model="form.account_number" />
              </Field>
            </template>
            <Field
              :label="`Opening balance (${currency})`"
              :error="form.errors.opening_balance"
              html-for="opening_balance"
            >
              <Input
                id="opening_balance"
                v-model="form.opening_balance"
                type="number"
                step="0.01"
                min="0"
              />
            </Field>
            <Field label="Active account">
              <Switch
                v-model="form.is_active"
                label="Visible and usable"
              />
            </Field>
          </div>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="route('bank.accounts.index')">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button
            type="submit"
            :loading="form.processing"
            icon-right="check"
          >
            Create account
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
