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

const form = useForm({
  name: '',
  contact_person: '',
  phone: '',
  email: '',
  address: '',
  notes: '',
  is_active: true,
})

function submit() {
  form.post('/inventory/suppliers')
}

const breadcrumbs = [
  { label: 'Suppliers', href: '/inventory/suppliers' },
  { label: 'New supplier' },
]
</script>

<template>
  <Head title="New supplier" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        title="New supplier"
        subtitle="Capture vendor contact and ordering information."
        icon="send"
        eyebrow="Inventory"
      />

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Identity">
          <Field label="Name" required :error="form.errors.name" html-for="name">
            <Input id="name" v-model="form.name" placeholder="Acme Dental Supplies" />
          </Field>
        </Card>

        <Card title="Contact">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Contact person" html-for="contact">
              <Input id="contact" v-model="form.contact_person" placeholder="Jane Doe" />
            </Field>
            <Field label="Phone" html-for="phone">
              <Input id="phone" v-model="form.phone" type="tel" placeholder="+880…" />
            </Field>
          </div>
          <div class="mt-4">
            <Field label="Email" :error="form.errors.email" html-for="email">
              <Input id="email" v-model="form.email" type="email" placeholder="vendor@example.com" />
            </Field>
          </div>
        </Card>

        <Card title="Address & notes">
          <Field label="Address" html-for="address">
            <Textarea id="address" v-model="form.address" :rows="2" />
          </Field>
          <div class="mt-4">
            <Field label="Notes" html-for="notes">
              <Textarea id="notes" v-model="form.notes" :rows="2" placeholder="Payment terms, MOQ, etc." />
            </Field>
          </div>
        </Card>

        <Card title="Status">
          <Field label="Active supplier">
            <Switch v-model="form.is_active" label="Available for orders" />
          </Field>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link href="/inventory/suppliers">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :loading="form.processing" icon-right="check">Create supplier</Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
