<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import Checkbox from '@/Components/ui/Checkbox.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Badge from '@/Components/ui/Badge.vue'

const props = defineProps({ permissions: Array })

const form = useForm({
  name: '',
  permissions: [],
})

function submit() {
  form.post('/admin/roles')
}

const allSelected = () => form.permissions.length === props.permissions.length

function toggleAll(checked) {
  form.permissions = checked ? [...props.permissions] : []
}

const breadcrumbs = [
  { label: 'Roles', href: '/admin/roles' },
  { label: 'New role' },
]
</script>

<template>
  <Head title="New role" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="New role"
        subtitle="Define a new role and the permissions it grants."
        icon="shield"
        eyebrow="Admin"
      />

      <Form :processing="form.processing" @submit="submit">
        <Card title="Role details">
          <Field label="Name" :error="form.errors.name" required>
            <Input v-model="form.name" placeholder="e.g. Senior receptionist" required />
          </Field>
        </Card>

        <Card>
          <template #header>
            <div class="flex items-center justify-between w-full">
              <div>
                <h3 class="text-base font-semibold text-base-content">Permissions</h3>
                <p class="text-sm text-muted mt-0.5">
                  Pick what this role can do. Inactive checkboxes mean it's not granted.
                </p>
              </div>
              <Badge variant="primary">
                {{ form.permissions.length }} / {{ permissions.length }}
              </Badge>
            </div>
          </template>

          <div class="flex items-center justify-between pb-3 border-b border-(--color-line)">
            <Checkbox
              :model-value="allSelected()"
              label="Select all permissions"
              @update:model-value="toggleAll"
            />
          </div>

          <p v-if="form.errors.permissions" class="mt-3 text-xs font-medium text-error">
            {{ form.errors.permissions }}
          </p>

          <div
            class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 p-4 border border-(--color-line) rounded-xl max-h-80 overflow-y-auto bg-base-200/30"
          >
            <label
              v-for="p in permissions"
              :key="p"
              class="flex items-center gap-2 text-sm text-base-content cursor-pointer px-2 py-1.5 rounded-md hover:bg-base-100 transition-colors"
            >
              <input
                type="checkbox"
                class="checkbox checkbox-xs checkbox-primary"
                :value="p"
                v-model="form.permissions"
              />
              <span class="font-mono text-xs">{{ p }}</span>
            </label>
          </div>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link href="/admin/roles">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button
            type="submit"
            :loading="form.processing"
            icon-right="check"
          >
            Create role
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
