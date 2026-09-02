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
import Avatar from '@/Components/ui/Avatar.vue'
import Badge from '@/Components/ui/Badge.vue'

const props = defineProps({ user: Object, roles: Array })

const user = props.user

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone,
  password: '',
  password_confirmation: '',
  role: props.user.role ?? 'receptionist',
  is_active: props.user.is_active,
})

function submit() {
  form.patch(`/admin/users/${props.user.id}`)
}

const breadcrumbs = [
  { label: 'Users', href: '/admin/users' },
  { label: user.name },
]
</script>

<template>
  <Head :title="`Edit ${user.name}`" />
  <AuthenticatedLayout>
    <div class="max-w-2xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="user.name"
        :subtitle="user.email"
        icon="user-cog"
        eyebrow="Admin · Edit user"
      >
        <template #actions>
          <Badge :variant="user.is_active ? 'success' : 'warning'" dot>
            {{ user.is_active ? 'Active' : 'Inactive' }}
          </Badge>
        </template>
      </PageHeader>

      <div class="flex items-center gap-3 px-1">
        <Avatar :name="user.name" size="lg" />
        <div class="min-w-0">
          <p class="font-medium">{{ user.name }}</p>
          <p class="text-sm text-(--color-muted)">
            {{ user.email }}
          </p>
        </div>
      </div>

      <Form :processing="form.processing" @submit="submit">
        <Card title="Account">
          <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Field label="Name" :error="form.errors.name" required>
                <Input v-model="form.name" required />
              </Field>
              <Field label="Email" :error="form.errors.email" required>
                <Input v-model="form.email" type="email" required />
              </Field>
            </div>
            <Field label="Phone" :error="form.errors.phone">
              <Input v-model="form.phone" type="tel" />
            </Field>
          </div>
        </Card>

        <Card title="Change password">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field
              label="New password"
              :error="form.errors.password"
              hint="Leave blank to keep current password"
            >
              <Input v-model="form.password" type="password" />
            </Field>
            <Field
              label="Confirm new password"
              :error="form.errors.password_confirmation"
            >
              <Input v-model="form.password_confirmation" type="password" />
            </Field>
          </div>
        </Card>

        <Card title="Access">
          <div class="space-y-4">
            <Field label="Role" required>
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
            Save changes
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
