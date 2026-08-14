<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiFormError from '@/Components/ui/Form/FormError.vue'
import UiFormActions from '@/Components/ui/Form/FormActions.vue'

const props = defineProps({ invoice_number: String })

function emptyItem() {
  return { description: '', quantity: 1, unit_price: 0 }
}

const form = useForm({
  invoice_date: new Date().toISOString().slice(0, 10),
  due_date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
  tax_rate: 0,
  discount_type: 'none',
  discount_value: 0,
  notes: '',
  items: [emptyItem(), emptyItem()],
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
  form.post('/billing/invoices')
}
</script>

<template>
  <Head title="New invoice" />
  <AuthenticatedLayout>
    <UiCard title="New invoice">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-foreground">New invoice</h3>
            <p class="text-xs text-muted-foreground font-mono">Next number: {{ invoice_number }}</p>
          </div>
          <Link href="/billing/invoices"><UiButton variant="ghost">Back</UiButton></Link>
        </div>
      </template>

      <UiForm @submit="submit">
        <UiFormError :errors="form.errors" />
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Invoice date" required :error="form.errors.invoice_date">
            <UiInput v-model="form.invoice_date" type="date" />
          </UiField>
          <UiField label="Due date" required :error="form.errors.due_date">
            <UiInput v-model="form.due_date" type="date" />
          </UiField>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <UiField label="Tax rate %" :error="form.errors.tax_rate">
            <UiInput v-model="form.tax_rate" type="number" min="0" max="100" step="0.01" />
          </UiField>
          <UiField label="Discount type" :error="form.errors.discount_type">
            <UiSelect
              v-model="form.discount_type"
              :options="[
                { label: 'No discount', value: 'none' },
                { label: 'Percentage', value: 'percentage' },
                { label: 'Fixed amount', value: 'fixed' },
              ]"
            />
          </UiField>
          <UiField label="Discount value" :error="form.errors.discount_value">
            <UiInput v-model="form.discount_value" type="number" min="0" step="0.01" />
          </UiField>
        </div>

        <div class="mt-4">
          <h4 class="text-sm font-medium text-foreground mb-2">Line items</h4>
          <div class="space-y-2">
            <div v-for="(item, idx) in form.items" :key="idx" class="grid grid-cols-12 gap-2 items-end">
              <UiField class="col-span-6" label="Description" required :error="form.errors[`items.${idx}.description`]">
                <UiInput v-model="item.description" />
              </UiField>
              <UiField class="col-span-2" label="Qty" required :error="form.errors[`items.${idx}.quantity`]">
                <UiInput v-model="item.quantity" type="number" min="0.01" step="0.01" />
              </UiField>
              <UiField class="col-span-3" label="Unit price" required :error="form.errors[`items.${idx}.unit_price`]">
                <UiInput v-model="item.unit_price" type="number" min="0" step="0.01" />
              </UiField>
              <div class="col-span-1">
                <UiButton type="button" variant="ghost" @click="removeItem(idx)" :disabled="form.items.length === 1">×</UiButton>
              </div>
            </div>
          </div>
          <UiButton type="button" variant="secondary" class="mt-2" @click="addItem">+ Add line</UiButton>
        </div>

        <div class="mt-4 bg-surface-muted p-4 rounded-md text-sm space-y-1">
          <div class="flex justify-between"><span>Subtotal</span><span>{{ subtotal.toFixed(2) }}</span></div>
          <div class="flex justify-between"><span>Discount</span><span>-{{ discountAmount.toFixed(2) }}</span></div>
          <div class="flex justify-between"><span>Tax</span><span>{{ taxAmount.toFixed(2) }}</span></div>
          <div class="flex justify-between font-semibold border-t border-border pt-1 mt-1">
            <span>Total</span><span>{{ total.toFixed(2) }}</span>
          </div>
        </div>

        <UiField label="Notes" :error="form.errors.notes">
          <UiInput v-model="form.notes" />
        </UiField>

        <UiFormActions :processing="form.processing" submit-label="Save as draft" :show-cancel="true" cancel-href="/billing/invoices" />
      </UiForm>
    </UiCard>
  </AuthenticatedLayout>
</template>
