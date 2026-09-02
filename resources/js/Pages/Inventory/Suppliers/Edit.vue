<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Textarea from '@/Components/ui/Textarea.vue'
import Switch from '@/Components/ui/Switch.vue'
import Button from '@/Components/ui/Button.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const props = defineProps({ supplier: Object })

const supplier = props.supplier

const form = useForm({
  name: props.supplier.name,
  contact_person: props.supplier.contact_person ?? '',
  phone: props.supplier.phone ?? '',
  email: props.supplier.email ?? '',
  address: props.supplier.address ?? '',
  notes: props.supplier.notes ?? '',
  is_active: props.supplier.is_active,
})

function submit() {
  form.patch(`/inventory/suppliers/${props.supplier.id}`)
}

const breadcrumbs = [
  { label: 'Suppliers', href: '/inventory/suppliers' },
  { label: supplier.name, href: `/inventory/suppliers/${supplier.id}` },
  { label: 'Edit' },
]
</script>

<template>
  <Head :title="`Edit ${supplier.name}`" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`Edit ${supplier.name}`"
        subtitle="Update vendor contact and ordering information."
        icon="building"
        eyebrow="Inventory"
      />

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Identity">
          <Field label="Name" required :error="form.errors.name" html-for="name">
            <Input id="name" v-model="form.name" />
          </Field>
        </Card>

        <Card title="Contact">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Contact person" html-for="contact">
              <Input id="contact" v-model="form.contact_person" />
            </Field>
            <Field label="Phone" html-for="phone">
              <Input id="phone" v-model="form.phone" type="tel" />
            </Field>
          </div>
          <div class="mt-4">
            <Field label="Email" :error="form.errors.email" html-for="email">
              <Input id="email" v-model="form.email" type="email" />
            </Field>
          </div>
        </Card>

        <Card title="Address & notes">
          <Field label="Address" html-for="address">
            <Textarea id="address" v-model="form.address" :rows="2" />
          </Field>
          <div class="mt-4">
            <Field label="Notes" html-for="notes">
              <Textarea id="notes" v-model="form.notes" :rows="2" />
            </Field>
          </div>
        </Card>

        <Card title="Status">
          <Field label="Active supplier">
            <Switch v-model="form.is_active" label="Available for orders" />
          </Field>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="`/inventory/suppliers/${supplier.id}`">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :loading="form.processing" icon-right="check">Save changes</Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
