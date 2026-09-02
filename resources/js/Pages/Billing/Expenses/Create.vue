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
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ categories: Array, methods: Array })

const form = useForm({
  expense_date: new Date().toISOString().slice(0, 10),
  category_id: props.categories[0]?.id ?? '',
  amount: '',
  currency_code: 'BDT',
  exchange_rate: '1.0',
  payment_method: 'cash',
  vendor: '',
  description: '',
})

function submit() {
  form.post('/billing/expenses', { forceFormData: true })
}

const breadcrumbs = [
  { label: 'Expenses', href: '/billing/expenses' },
  { label: 'New expense' },
]
</script>

<template>
  <Head title="New expense" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        title="Submit expense"
        subtitle="Record a new clinic expense with category, vendor, and payment method."
        icon="wallet"
        eyebrow="Add expense"
      />

      <Form :processing="form.processing" @submit="submit" class="space-y-5">
        <FormError :errors="form.errors" />

        <Card title="Expense details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Date" required :error="form.errors.expense_date" html-for="exp-date">
              <Input id="exp-date" v-model="form.expense_date" type="date" />
            </Field>
            <Field label="Category" required :error="form.errors.category_id" html-for="exp-cat">
              <Select
                id="exp-cat"
                v-model="form.category_id"
                :options="categories.map((c) => ({ label: c.name, value: c.id }))"
                placeholder="Select category"
              />
            </Field>
          </div>
        </Card>

        <Card title="Amount & payment">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Field label="Amount" required :error="form.errors.amount" html-for="exp-amt">
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
          <Field label="Vendor" :error="form.errors.vendor" html-for="exp-vendor">
            <Input id="exp-vendor" v-model="form.vendor" placeholder="Supplier or payee name" />
          </Field>
          <Field label="Description" :error="form.errors.description" html-for="exp-desc" class="mt-4">
            <Textarea id="exp-desc" v-model="form.description" :rows="3" placeholder="Optional notes about this expense" />
          </Field>
          <Field label="Receipt (optional)" :error="form.errors.receipt" html-for="exp-receipt" hint="PDF, JPG, or PNG." class="mt-4">
            <input
              id="exp-receipt"
              type="file"
              @change="(e) => form.receipt = e.target.files[0]"
              class="block w-full text-sm text-(--color-muted) file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary hover:file:bg-primary/15"
            />
          </Field>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link href="/billing/expenses">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :loading="form.processing" icon-right="check">Submit expense</Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
