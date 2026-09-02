<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import Switch from '@/Components/ui/Switch.vue'
import Textarea from '@/Components/ui/Textarea.vue'
import Button from '@/Components/ui/Button.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const props = defineProps({ product: Object })

const product = props.product

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

const categoryOptions = [
  { label: 'Consumable', value: 'consumable' },
  { label: 'Equipment', value: 'equipment' },
  { label: 'Medication', value: 'medication' },
  { label: 'Material', value: 'material' },
  { label: 'Other', value: 'other' },
]

const unitOptions = [
  { label: 'Piece', value: 'piece' },
  { label: 'Box', value: 'box' },
  { label: 'Vial', value: 'vial' },
  { label: 'Syringe', value: 'syringe' },
  { label: 'Millilitre (ml)', value: 'ml' },
  { label: 'Gram', value: 'gram' },
  { label: 'Kilogram', value: 'kg' },
  { label: 'Litre', value: 'liter' },
  { label: 'Pack', value: 'pack' },
]

function submit() {
  form.patch(`/inventory/products/${props.product.id}`)
}

const breadcrumbs = [
  { label: 'Products', href: '/inventory/products' },
  { label: product.name, href: `/inventory/products/${product.id}` },
  { label: 'Edit' },
]
</script>

<template>
  <Head :title="`Edit ${product.name}`" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Edit ${product.name}`"
        :subtitle="`SKU ${product.sku}`"
        icon="package"
        eyebrow="Inventory"
      />

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Identity">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="SKU" required :error="form.errors.sku" html-for="sku">
              <Input id="sku" v-model="form.sku" />
            </Field>
            <Field label="Barcode" :error="form.errors.barcode" html-for="barcode">
              <Input id="barcode" v-model="form.barcode" />
            </Field>
          </div>
          <div class="mt-4">
            <Field label="Name" required :error="form.errors.name" html-for="name">
              <Input id="name" v-model="form.name" />
            </Field>
          </div>
        </Card>

        <Card title="Classification">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Category" required html-for="category">
              <Select id="category" v-model="form.category" :options="categoryOptions" />
            </Field>
            <Field label="Unit" required html-for="unit">
              <Select id="unit" v-model="form.unit" :options="unitOptions" />
            </Field>
          </div>
        </Card>

        <Card title="Pricing">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Cost price" html-for="cost">
              <Input id="cost" v-model="form.cost_price" type="number" step="0.01" min="0" />
            </Field>
            <Field label="Selling price" html-for="sell">
              <Input id="sell" v-model="form.selling_price" type="number" step="0.01" min="0" />
            </Field>
          </div>
        </Card>

        <Card title="Reorder">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Reorder level" html-for="reorder-level">
              <Input id="reorder-level" v-model="form.reorder_level" type="number" min="0" />
            </Field>
            <Field label="Reorder quantity" html-for="reorder-qty">
              <Input id="reorder-qty" v-model="form.reorder_quantity" type="number" min="0" />
            </Field>
          </div>
        </Card>

        <Card title="Notes & flags">
          <Field label="Description" html-for="desc">
            <Textarea id="desc" v-model="form.description" :rows="3" />
          </Field>
          <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Active product">
              <Switch v-model="form.is_active" label="Visible in catalog" />
            </Field>
            <Field label="Track stock">
              <Switch v-model="form.track_stock" label="Maintain stock levels" />
            </Field>
          </div>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="`/inventory/products/${product.id}`">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :loading="form.processing" icon-right="check">Save changes</Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
