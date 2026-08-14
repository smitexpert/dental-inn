<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiDialog from '@/Components/ui/Dialog.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiSwitch from '@/Components/ui/Switch.vue'

const props = defineProps({ locations: Object, filters: Object })

const q = ref(props.filters.q ?? '')
const isActive = ref(
  props.filters.is_active === '1' || props.filters.is_active === 1 || props.filters.is_active === true
    ? '1'
    : props.filters.is_active === '0' || props.filters.is_active === 0 || props.filters.is_active === false
      ? '0'
      : '',
)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get('/inventory/locations', { q: q.value, is_active: isActive.value }, { preserveState: true, replace: true })
  }, 300)
}
watch([q, isActive], applyFilters)

const createForm = useForm({ name: '', location_type: 'storage', is_active: true })
const editForm = useForm({ id: null, name: '', location_type: 'storage', is_active: true })

const createOpen = ref(false)
const editOpen = ref(false)

function openCreate() {
  createForm.reset()
  createOpen.value = true
}

function openEdit(loc) {
  editForm.id = loc.id
  editForm.name = loc.name
  editForm.location_type = loc.location_type
  editForm.is_active = loc.is_active
  editOpen.value = true
}

function submitCreate() {
  createForm.post('/inventory/locations', { onSuccess: () => (createOpen.value = false) })
}

function submitEdit() {
  editForm.patch(`/inventory/locations/${editForm.id}`, { onSuccess: () => (editOpen.value = false) })
}

const typeOptions = [
  { label: 'Storage', value: 'storage' },
  { label: 'Operatory', value: 'operatory' },
  { label: 'Cabinet', value: 'cabinet' },
  { label: 'Other', value: 'other' },
]
</script>

<template>
  <Head title="Inventory — Locations" />
  <AuthenticatedLayout>
    <UiCard title="Locations">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Locations</h3>
          <UiButton @click="openCreate">New location</UiButton>
        </div>
      </template>
      <div class="grid grid-cols-2 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search by name" />
        <UiSelect
          v-model="isActive"
          :options="[
            { label: 'All', value: '' },
            { label: 'Active only', value: '1' },
            { label: 'Inactive only', value: '0' },
          ]"
        />
      </div>
      <table class="w-full text-sm">
        <thead class="bg-base-200 text-base-content/70">
          <tr>
            <th class="text-left px-4 py-2">Name</th>
            <th class="text-left px-4 py-2">Type</th>
            <th class="text-right px-4 py-2">Products tracked</th>
            <th class="text-left px-4 py-2">Status</th>
            <th class="text-right px-4 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="loc in locations.data" :key="loc.id" class="border-b border-base-300 last:border-b-0">
            <td class="px-4 py-2 text-base-content font-medium">{{ loc.name }}</td>
            <td class="px-4 py-2 text-base-content/70 capitalize">{{ loc.location_type }}</td>
            <td class="px-4 py-2 text-right text-base-content/70">{{ loc.stock_levels_count }}</td>
            <td class="px-4 py-2">
              <UiBadge :variant="loc.is_active ? 'success' : 'neutral'">{{ loc.is_active ? 'Active' : 'Inactive' }}</UiBadge>
            </td>
            <td class="px-4 py-2 text-right">
              <button type="button" class="text-primary hover:text-primary text-sm" @click="openEdit(loc)">Edit</button>
            </td>
          </tr>
          <tr v-if="locations.data.length === 0">
            <td colspan="5" class="text-center text-base-content/70 py-6">No locations yet.</td>
          </tr>
        </tbody>
      </table>
    </UiCard>

    <UiDialog v-model="createOpen" title="New location">
      <form @submit.prevent="submitCreate" class="space-y-3">
        <UiField label="Name" required>
          <UiInput v-model="createForm.name" placeholder="e.g. Operatory 1" />
          <p v-if="createForm.errors.name" class="text-xs text-error mt-1">{{ createForm.errors.name }}</p>
        </UiField>
        <UiField label="Type">
          <UiSelect v-model="createForm.location_type" :options="typeOptions" />
        </UiField>
        <UiField label="Active"><UiSwitch v-model="createForm.is_active" /></UiField>
        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="createOpen = false">Cancel</UiButton>
          <UiButton type="submit" :disabled="createForm.processing">Create</UiButton>
        </div>
      </form>
    </UiDialog>

    <UiDialog v-model="editOpen" title="Edit location">
      <form @submit.prevent="submitEdit" class="space-y-3">
        <UiField label="Name" required>
          <UiInput v-model="editForm.name" />
          <p v-if="editForm.errors.name" class="text-xs text-error mt-1">{{ editForm.errors.name }}</p>
        </UiField>
        <UiField label="Type">
          <UiSelect v-model="editForm.location_type" :options="typeOptions" />
        </UiField>
        <UiField label="Active"><UiSwitch v-model="editForm.is_active" /></UiField>
        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="editOpen = false">Cancel</UiButton>
          <UiButton type="submit" :disabled="editForm.processing">Save</UiButton>
        </div>
      </form>
    </UiDialog>
  </AuthenticatedLayout>
</template>
