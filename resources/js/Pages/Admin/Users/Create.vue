<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import Select from '@/Components/ui/Select.vue'
import Switch from '@/Components/ui/Switch.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const props = defineProps({ roles: Array })

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role: 'receptionist',
  is_active: true,
})

function submit() {
  form.post('/admin/users')
}

const breadcrumbs = [
  { label: 'Users', href: '/admin/users' },
  { label: 'New user' },
]
</script>

<template>
  <Head title="New user" />
  <AuthenticatedLayout>
    <div class="max-w-2xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="New user"
        subtitle="Add a new staff account and assign a role."
        icon="user-plus"
        eyebrow="Admin"
      />

      <Form :processing="form.processing" @submit="submit">
        <Card title="Account">
          <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Field label="Name" :error="form.errors.name" required html-for="name">
                <Input id="name" v-model="form.name" required />
              </Field>
              <Field label="Email" :error="form.errors.email" required html-for="email">
                <Input id="email" v-model="form.email" type="email" required />
              </Field>
            </div>
            <Field label="Phone" :error="form.errors.phone" html-for="phone">
              <Input id="phone" v-model="form.phone" type="tel" placeholder="+880…" />
            </Field>
          </div>
        </Card>

        <Card title="Password">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Password" :error="form.errors.password" required>
              <Input v-model="form.password" type="password" required />
            </Field>
            <Field label="Confirm password" :error="form.errors.password_confirmation" required>
              <Input v-model="form.password_confirmation" type="password" required />
            </Field>
          </div>
        </Card>

        <Card title="Access">
          <div class="space-y-4">
            <Field label="Role" :error="form.errors.role" required>
              <Select
                v-model="form.role"
                :options="roles.map((r) => ({ label: r, value: r }))"
              />
            </Field>
            <Field label="Status">
              <Switch v-model="form.is_active" label="Active" />
            </Field>
          </div>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link href="/admin/users">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button
            type="submit"
            :loading="form.processing"
            icon-right="check"
          >
            Create user
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
