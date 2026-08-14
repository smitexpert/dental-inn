<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiSwitch from '@/Components/ui/Switch.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'
import UiFormError from '@/Components/ui/Form/FormError.vue'
import UiFormActions from '@/Components/ui/Form/FormActions.vue'

const form = useForm({
  sku: '',
  barcode: '',
  name: '',
  category: 'consumable',
  unit: 'piece',
  cost_price: '',
  selling_price: '',
  reorder_level: 0,
  reorder_quantity: 0,
  description: '',
  is_active: true,
  track_stock: true,
})

function submit() {
  form.post('/inventory/products')
}
</script>

<template>
  <Head title="New product" />
  <AuthenticatedLayout>
    <UiCard title="New product">
      <UiForm @submit="submit">
        <UiFormError :errors="form.errors" />
        <div class="grid grid-cols-2 gap-4">
          <UiField label="SKU" required :error="form.errors.sku">
            <UiInput v-model="form.sku" placeholder="SKU-AB1234" />
          </UiField>
          <UiField label="Barcode" :error="form.errors.barcode">
            <UiInput v-model="form.barcode" placeholder="Optional" />
          </UiField>
        </div>
        <UiField label="Name" required :error="form.errors.name">
          <UiInput v-model="form.name" placeholder="Composite resin A2" />
        </UiField>
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Category" required :error="form.errors.category">
            <UiSelect
              v-model="form.category"
              :options="[
                { label: 'Consumable', value: 'consumable' },
                { label: 'Equipment', value: 'equipment' },
                { label: 'Medication', value: 'medication' },
                { label: 'Material', value: 'material' },
                { label: 'Other', value: 'other' },
              ]"
            />
          </UiField>
          <UiField label="Unit" required :error="form.errors.unit">
            <UiSelect
              v-model="form.unit"
              :options="[
                { label: 'Piece', value: 'piece' },
                { label: 'Box', value: 'box' },
                { label: 'Vial', value: 'vial' },
                { label: 'Syringe', value: 'syringe' },
                { label: 'Millilitre (ml)', value: 'ml' },
                { label: 'Gram', value: 'gram' },
                { label: 'Kilogram', value: 'kg' },
                { label: 'Litre', value: 'liter' },
                { label: 'Pack', value: 'pack' },
              ]"
            />
          </UiField>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Cost price" :error="form.errors.cost_price">
            <UiInput v-model="form.cost_price" type="number" step="0.01" min="0" placeholder="0.00" />
          </UiField>
          <UiField label="Selling price" :error="form.errors.selling_price">
            <UiInput v-model="form.selling_price" type="number" step="0.01" min="0" placeholder="0.00" />
          </UiField>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Reorder level" :error="form.errors.reorder_level">
            <UiInput v-model="form.reorder_level" type="number" min="0" />
          </UiField>
          <UiField label="Reorder quantity" :error="form.errors.reorder_quantity">
            <UiInput v-model="form.reorder_quantity" type="number" min="0" />
          </UiField>
        </div>
        <UiField label="Description" :error="form.errors.description">
          <UiTextarea v-model="form.description" :rows="3" />
        </UiField>
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Active">
            <UiSwitch v-model="form.is_active" />
          </UiField>
          <UiField label="Track stock">
            <UiSwitch v-model="form.track_stock" />
          </UiField>
        </div>
        <UiFormActions
          :processing="form.processing"
          submit-label="Create product"
          :show-cancel="true"
          cancel-href="/inventory/products"
        />
      </UiForm>
    </UiCard>
  </AuthenticatedLayout>
</template>
