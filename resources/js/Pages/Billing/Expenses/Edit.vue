<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import Textarea from '@/Components/ui/Textarea.vue'
import Button from '@/Components/ui/Button.vue'
import FormError from '@/Components/ui/Form/FormError.vue'

const props = defineProps({ expense: Object, categories: Array, methods: Array })

const expense = props.expense

const form = useForm({
  expense_date: props.expense.expense_date,
  category_id: props.expense.category.id,
  amount: props.expense.amount,
  currency_code: props.expense.currency_code,
  exchange_rate: props.expense.exchange_rate,
  payment_method: props.expense.payment_method,
  vendor: props.expense.vendor ?? '',
  description: props.expense.description ?? '',
})

function submit() {
  form.patch(`/billing/expenses/${props.expense.id}`, { forceFormData: true })
}

const breadcrumbs = [
  { label: 'Expenses', href: '/billing/expenses' },
  { label: `Expense #${expense.id}`, href: `/billing/expenses/${expense.id}` },
  { label: 'Edit' },
]
</script>

<template>
  <Head title="Edit expense" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Edit expense #${expense.id}`"
        subtitle="Update category, amount, vendor, or notes for this expense."
        icon="credit-card"
        eyebrow="Edit expense"
      />

      <Form :processing="form.processing" @submit="submit" class="space-y-5">
        <FormError :errors="form.errors" />

        <Card title="Expense details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Date" required html-for="exp-date">
              <Input id="exp-date" v-model="form.expense_date" type="date" />
            </Field>
            <Field label="Category" required html-for="exp-cat">
              <Select
                id="exp-cat"
                v-model="form.category_id"
                :options="categories.map((c) => ({ label: c.name, value: c.id }))"
              />
            </Field>
          </div>
        </Card>

        <Card title="Amount & payment">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Field label="Amount" required html-for="exp-amt">
              <Input id="exp-amt" v-model="form.amount" type="number" min="0.01" step="0.01" />
            </Field>
            <Field label="Currency" html-for="exp-cur">
              <Input id="exp-cur" v-model="form.currency_code" />
            </Field>
            <Field label="Payment method" html-for="exp-pm">
              <Select
                id="exp-pm"
                v-model="form.payment_method"
                :options="methods.map((m) => ({ label: m.label, value: m.value }))"
              />
            </Field>
          </div>
        </Card>

        <Card title="Vendor & notes">
          <Field label="Vendor" html-for="exp-vendor">
            <Input id="exp-vendor" v-model="form.vendor" />
          </Field>
          <Field label="Description" html-for="exp-desc" class="mt-4">
            <Textarea id="exp-desc" v-model="form.description" :rows="3" />
          </Field>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="`/billing/expenses/${expense.id}`">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :loading="form.processing" icon-right="check">Save changes</Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
