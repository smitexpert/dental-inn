<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiButton from '@/Components/ui/Button.vue'

const props = defineProps({ role: Object, permissions: Array })

const form = useForm({
  name: props.role.name,
  permissions: [...props.role.permissions],
})

function submit() {
  form.patch(`/admin/roles/${props.role.id}`)
}

function toggleAll(checked) {
  form.permissions = checked ? [...props.permissions] : []
}
</script>

<template>
  <Head :title="`Edit role ${role.name}`" />
  <AuthenticatedLayout>
    <div class="max-w-3xl">
      <UiCard :title="`Edit ${role.name}`">
        <UiForm :processing="form.processing" @submit.prevent="submit">
          <UiField label="Name" :error="form.errors.name" required
            ><UiInput v-model="form.name" required
          /></UiField>
          <UiField :error="form.errors.permissions">
            <template #label>
              <div class="flex items-center justify-between">
                <span>Permissions</span>
                <label class="flex items-center gap-2 text-xs text-base-content/70 cursor-pointer">
                  <input
                    type="checkbox"
                    class="checkbox checkbox-xs"
                    :checked="form.permissions.length === permissions.length"
                    @change="toggleAll($event.target.checked)"
                  />
                  Select all
                </label>
              </div>
            </template>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 p-3 border border-base-300 rounded-md max-h-72 overflow-y-auto">
              <label
                v-for="p in permissions"
                :key="p"
                class="flex items-center gap-2 text-sm text-base-content cursor-pointer"
              >
                <input
                  type="checkbox"
                  class="checkbox checkbox-sm checkbox-primary"
                  :value="p"
                  v-model="form.permissions"
                />
                <span>{{ p }}</span>
              </label>
            </div>
            <p class="text-xs text-base-content/70 mt-1">
              {{ form.permissions.length }} of {{ permissions.length }} selected
            </p>
          </UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-300">
            <Link
              href="/admin/roles"
              class="h-10 px-4 inline-flex items-center rounded-md bg-base-200 text-base-content border border-base-300 text-sm"
              >Cancel</Link
            >
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>