<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import Switch from '@/Components/ui/Switch.vue'
import Button from '@/Components/ui/Button.vue'
import FormError from '@/Components/ui/Form/FormError.vue'

const props = defineProps({
  account: { type: Object, required: true },
  parents: { type: Array, required: true },
})

const form = useForm({
  code: props.account.code,
  name: props.account.name,
  type: props.account.type,
  parent_id: props.account.parent_id,
  is_active: props.account.is_active,
})

const typeOptions = [
  { label: 'Asset', value: 'asset' },
  { label: 'Liability', value: 'liability' },
  { label: 'Equity', value: 'equity' },
  { label: 'Revenue', value: 'revenue' },
  { label: 'Expense', value: 'expense' },
]

const parentOptions = computed(() => [
  { label: '— No parent —', value: null },
  ...props.parents
    .filter((p) => p.id !== props.account.id)
    .map((p) => ({ label: `${p.code} — ${p.name}`, value: p.id })),
])

function submit() {
  form.patch(`/billing/accounts/${props.account.id}`, { preserveScroll: true })
}

const breadcrumbs = [
  { label: 'Chart of accounts', href: '/billing/accounts' },
  { label: `${account.code} — ${account.name}`, href: `/billing/accounts/${account.id}` },
  { label: 'Edit' },
]
</script>

<template>
  <Head title="Edit account" />
  <AuthenticatedLayout>
    <div class="max-w-2xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Edit ${account.code} — ${account.name}`"
        subtitle="Update account code, name, type, or parent."
        icon="banknote"
        eyebrow="Edit account"
      />

      <Form :processing="form.processing" @submit.prevent="submit" class="space-y-5">
        <FormError :errors="form.errors" />

        <Card title="Account details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Code" :error="form.errors.code" required html-for="acc-code">
              <Input id="acc-code" v-model="form.code" maxlength="10" required />
            </Field>
            <Field label="Type" :error="form.errors.type" required html-for="acc-type">
              <Select id="acc-type" v-model="form.type" :options="typeOptions" />
            </Field>
          </div>
          <Field label="Name" :error="form.errors.name" required html-for="acc-name" class="mt-4">
            <Input id="acc-name" v-model="form.name" required />
          </Field>
          <Field label="Parent account" :error="form.errors.parent_id" html-for="acc-parent" class="mt-4">
            <Select id="acc-parent" v-model="form.parent_id" :options="parentOptions" />
          </Field>
        </Card>

        <Card title="Status">
          <Field label="Active" description="Inactive accounts are hidden from new transactions.">
            <Switch v-model="form.is_active" label="Account is active" />
          </Field>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="`/billing/accounts/${account.id}`">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :loading="form.processing" icon-right="check">Save changes</Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
