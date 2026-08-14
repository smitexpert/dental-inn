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

const props = defineProps({ product: Object })

const form = useForm({
  sku: props.product.sku,
  barcode: props.product.barcode ?? '',
  name: props.product.name,
  category: props.product.category,
  unit: props.product.unit,
  cost_price: props.product.cost_price ?? '',
  selling_price: props.product.selling_price ?? '',
  reorder_level: props.product.reorder_level,
  reorder_quantity: props.product.reorder_quantity,
  description: props.product.description ?? '',
  is_active: props.product.is_active,
  track_stock: props.product.track_stock,
})

function submit() {
  form.patch(`/inventory/products/${props.product.id}`)
}
</script>

<template>
  <Head :title="`Edit ${product.name}`" />
  <AuthenticatedLayout>
    <UiCard :title="`Edit ${product.name}`">
      <UiForm @submit="submit">
        <UiFormError :errors="form.errors" />
        <div class="grid grid-cols-2 gap-4">
          <UiField label="SKU" required :error="form.errors.sku">
            <UiInput v-model="form.sku" />
          </UiField>
          <UiField label="Barcode" :error="form.errors.barcode">
            <UiInput v-model="form.barcode" />
          </UiField>
        </div>
        <UiField label="Name" required :error="form.errors.name">
          <UiInput v-model="form.name" />
        </UiField>
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Category" required>
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
          <UiField label="Unit" required>
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
          <UiField label="Cost price">
            <UiInput v-model="form.cost_price" type="number" step="0.01" min="0" />
          </UiField>
          <UiField label="Selling price">
            <UiInput v-model="form.selling_price" type="number" step="0.01" min="0" />
          </UiField>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Reorder level">
            <UiInput v-model="form.reorder_level" type="number" min="0" />
          </UiField>
          <UiField label="Reorder quantity">
            <UiInput v-model="form.reorder_quantity" type="number" min="0" />
          </UiField>
        </div>
        <UiField label="Description">
          <UiTextarea v-model="form.description" :rows="3" />
        </UiField>
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Active"><UiSwitch v-model="form.is_active" /></UiField>
          <UiField label="Track stock"><UiSwitch v-model="form.track_stock" /></UiField>
        </div>
        <UiFormActions
          :processing="form.processing"
          submit-label="Save changes"
          :show-cancel="true"
          :cancel-href="`/inventory/products/${product.id}`"
        />
      </UiForm>
    </UiCard>
  </AuthenticatedLayout>
</template>
