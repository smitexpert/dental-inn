<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiFormError from '@/Components/ui/Form/FormError.vue'
import UiFormActions from '@/Components/ui/Form/FormActions.vue'

const props = defineProps({ invoice: Object })

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
  items: props.invoice.items.length > 0 ? props.invoice.items.map((i) => ({
    description: i.description,
    quantity: Number(i.quantity),
    unit_price: Number(i.unit_price),
  })) : [emptyItem()],
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
</script>

<template>
  <Head :title="`Edit ${invoice.invoice_number}`" />
  <AuthenticatedLayout>
    <UiCard :title="`Edit ${invoice.invoice_number}`">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Edit invoice {{ invoice.invoice_number }}</h3>
          <Link :href="`/billing/invoices/${invoice.id}`"><UiButton variant="ghost">Back</UiButton></Link>
        </div>
      </template>

      <UiForm @submit="submit">
        <UiFormError :errors="form.errors" />
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Invoice date" required><UiInput v-model="form.invoice_date" type="date" /></UiField>
          <UiField label="Due date" required><UiInput v-model="form.due_date" type="date" /></UiField>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <UiField label="Tax rate %"><UiInput v-model="form.tax_rate" type="number" min="0" max="100" step="0.01" /></UiField>
          <UiField label="Discount type">
            <UiSelect v-model="form.discount_type" :options="[
              { label: 'No discount', value: 'none' },
              { label: 'Percentage', value: 'percentage' },
              { label: 'Fixed amount', value: 'fixed' },
            ]" />
          </UiField>
          <UiField label="Discount value"><UiInput v-model="form.discount_value" type="number" min="0" step="0.01" /></UiField>
        </div>

        <div class="mt-4">
          <h4 class="text-sm font-medium text-base-content mb-2">Line items</h4>
          <div class="space-y-2">
            <div v-for="(item, idx) in form.items" :key="idx" class="grid grid-cols-12 gap-2 items-end">
              <UiField class="col-span-6" label="Description" required><UiInput v-model="item.description" /></UiField>
              <UiField class="col-span-2" label="Qty" required><UiInput v-model="item.quantity" type="number" min="0.01" step="0.01" /></UiField>
              <UiField class="col-span-3" label="Unit price" required><UiInput v-model="item.unit_price" type="number" min="0" step="0.01" /></UiField>
              <div class="col-span-1"><UiButton type="button" variant="ghost" @click="removeItem(idx)" :disabled="form.items.length === 1">×</UiButton></div>
            </div>
          </div>
          <UiButton type="button" variant="secondary" class="mt-2" @click="addItem">+ Add line</UiButton>
        </div>

        <div class="mt-4 bg-base-200 p-4 rounded-md text-sm space-y-1">
          <div class="flex justify-between"><span>Subtotal</span><span>{{ subtotal.toFixed(2) }}</span></div>
          <div class="flex justify-between"><span>Discount</span><span>-{{ discountAmount.toFixed(2) }}</span></div>
          <div class="flex justify-between"><span>Tax</span><span>{{ taxAmount.toFixed(2) }}</span></div>
          <div class="flex justify-between font-semibold border-t border-base-300 pt-1 mt-1"><span>Total</span><span>{{ total.toFixed(2) }}</span></div>
        </div>

        <UiField label="Notes"><UiInput v-model="form.notes" /></UiField>

        <UiFormActions :processing="form.processing" submit-label="Save changes" :show-cancel="true" :cancel-href="`/billing/invoices/${invoice.id}`" />
      </UiForm>
    </UiCard>
  </AuthenticatedLayout>
</template>
