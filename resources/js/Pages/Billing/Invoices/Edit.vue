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
import Textarea from '@/Components/ui/Textarea.vue'
import Button from '@/Components/ui/Button.vue'
import FormError from '@/Components/ui/Form/FormError.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ invoice: Object })

const invoice = props.invoice

function emptyItem() {
  return { description: '', quantity: 1, unit_price: 0 }
}

const form = useForm({
  invoice_date: props.invoice.invoice_date,
  due_date: props.invoice.due_date,
  tax_rate: Number(props.invoice.tax_rate),
  discount_type: props.invoice.discount_type,
  discount_value: Number(props.invoice.discount_value),
  notes: props.invoice.notes ?? '',
  items: props.invoice.items.length > 0
    ? props.invoice.items.map((i) => ({
        description: i.description,
        quantity: Number(i.quantity),
        unit_price: Number(i.unit_price),
      }))
    : [emptyItem()],
})

const subtotal = computed(() =>
  form.items.reduce((sum, r) => sum + Number(r.quantity || 0) * Number(r.unit_price || 0), 0),
)
const discountAmount = computed(() => {
  if (form.discount_type === 'percentage') return subtotal.value * (Number(form.discount_value) / 100)
  if (form.discount_type === 'fixed') return Math.min(Number(form.discount_value), subtotal.value)
  return 0
})
const taxAmount = computed(() => {
  const taxable = Math.max(0, subtotal.value - discountAmount.value)
  return taxable * (Number(form.tax_rate) / 100)
})
const total = computed(() => subtotal.value - discountAmount.value + taxAmount.value)

function addItem() {
  form.items.push(emptyItem())
}
function removeItem(i) {
  if (form.items.length > 1) form.items.splice(i, 1)
}

function submit() {
  form.patch(`/billing/invoices/${props.invoice.id}`)
}

const breadcrumbs = [
  { label: 'Invoices', href: '/billing/invoices' },
  { label: invoice.invoice_number, href: `/billing/invoices/${invoice.id}` },
  { label: 'Edit' },
]
</script>

<template>
  <Head :title="`Edit ${invoice.invoice_number}`" />
  <AuthenticatedLayout>
    <div class="max-w-4xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Edit ${invoice.invoice_number}`"
        subtitle="Adjust line items, dates, and tax or discount settings before issuing."
        icon="receipt-text"
        eyebrow="Edit"
      />

      <Form :processing="form.processing" @submit="submit" class="space-y-5">
        <FormError :errors="form.errors" />

        <Card title="Invoice details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Invoice date" required html-for="inv-date">
              <Input id="inv-date" v-model="form.invoice_date" type="date" />
            </Field>
            <Field label="Due date" required html-for="inv-due">
              <Input id="inv-due" v-model="form.due_date" type="date" />
            </Field>
          </div>
        </Card>

        <Card title="Adjustments">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Field label="Tax rate %" html-for="inv-tax">
              <Input id="inv-tax" v-model="form.tax_rate" type="number" min="0" max="100" step="0.01" />
            </Field>
            <Field label="Discount type" html-for="inv-dtype">
              <Select
                id="inv-dtype"
                v-model="form.discount_type"
                :options="[
                  { label: 'No discount', value: 'none' },
                  { label: 'Percentage', value: 'percentage' },
                  { label: 'Fixed amount', value: 'fixed' },
                ]"
              />
            </Field>
            <Field label="Discount value" html-for="inv-dval">
              <Input id="inv-dval" v-model="form.discount_value" type="number" min="0" step="0.01" />
            </Field>
          </div>
        </Card>

        <Card title="Line items" :padded="false">
          <div class="px-5 pt-5 pb-2">
            <div class="grid grid-cols-12 gap-2 text-xs font-semibold uppercase tracking-wide text-(--color-muted) mb-2">
              <div class="col-span-6">Description</div>
              <div class="col-span-2 text-right">Qty</div>
              <div class="col-span-3 text-right">Unit price</div>
              <div class="col-span-1"></div>
            </div>
            <div class="space-y-3">
              <div
                v-for="(item, idx) in form.items"
                :key="idx"
                class="grid grid-cols-12 gap-2 items-end"
              >
                <div class="col-span-6">
                  <Field required>
                    <Input v-model="item.description" placeholder="Service or product description" />
                  </Field>
                </div>
                <div class="col-span-2">
                  <Field required>
                    <Input v-model="item.quantity" type="number" min="0.01" step="0.01" />
                  </Field>
                </div>
                <div class="col-span-3">
                  <Field required>
                    <Input v-model="item.unit_price" type="number" min="0" step="0.01" />
                  </Field>
                </div>
                <div class="col-span-1 flex justify-end">
                  <Button
                    type="button"
                    variant="ghost"
                    icon="trash"
                    icon-only
                    @click="removeItem(idx)"
                    :disabled="form.items.length === 1"
                    aria-label="Remove line"
                  />
                </div>
              </div>
            </div>
            <div class="mt-3">
              <Button type="button" variant="outline" icon="plus" @click="addItem">Add line</Button>
            </div>
          </div>

          <div class="mx-5 my-4 border-t border-(--color-line)"></div>

          <div class="px-5 pb-5">
            <dl class="ml-auto max-w-sm space-y-1.5 text-sm">
              <div class="flex justify-between text-(--color-muted)">
                <dt>Subtotal</dt>
                <dd class="tabular-nums text-base-content">{{ subtotal.toFixed(2) }}</dd>
              </div>
              <div class="flex justify-between text-(--color-muted)">
                <dt>Discount</dt>
                <dd class="tabular-nums text-base-content">-{{ discountAmount.toFixed(2) }}</dd>
              </div>
              <div class="flex justify-between text-(--color-muted)">
                <dt>Tax</dt>
                <dd class="tabular-nums text-base-content">{{ taxAmount.toFixed(2) }}</dd>
              </div>
              <div class="flex justify-between font-semibold border-t border-(--color-line) pt-2 mt-1">
                <dt>Total</dt>
                <dd class="tabular-nums">{{ total.toFixed(2) }}</dd>
              </div>
            </dl>
          </div>
        </Card>

        <Card title="Notes">
          <Field label="Internal notes" html-for="inv-notes">
            <Textarea id="inv-notes" v-model="form.notes" :rows="3" />
          </Field>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="`/billing/invoices/${invoice.id}`">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :loading="form.processing" icon-right="check">Save changes</Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
