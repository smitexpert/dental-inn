<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Icon from '@/Components/ui/Icon.vue'
import Dialog from '@/Components/ui/Dialog.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import Switch from '@/Components/ui/Switch.vue'

const props = defineProps({ locations: Object, filters: Object })

const q = ref(props.filters.q ?? '')
const isActive = ref(
  props.filters.is_active === '1' ||
  props.filters.is_active === 1 ||
  props.filters.is_active === true
    ? '1'
    : props.filters.is_active === '0' ||
        props.filters.is_active === 0 ||
        props.filters.is_active === false
      ? '0'
      : '',
)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/inventory/locations',
      { q: q.value, is_active: isActive.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, isActive], applyFilters)

const createForm = useForm({ name: '', location_type: 'storage', is_active: true })
const editForm = useForm({ id: null, name: '', location_type: 'storage', is_active: true })

const createOpen = ref(false)
const editOpen = ref(false)

function openCreate() {
  createForm.reset()
  createForm.clearErrors()
  createOpen.value = true
}

function openEdit(loc) {
  editForm.id = loc.id
  editForm.name = loc.name
  editForm.location_type = loc.location_type
  editForm.is_active = loc.is_active
  editForm.clearErrors()
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

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Active only', value: '1' },
  { label: 'Inactive only', value: '0' },
]

function goPage(n) {
  router.get(
    `/inventory/locations?page=${n}`,
    { q: q.value, is_active: isActive.value },
    { preserveState: true },
  )
}
</script>

<template>
  <Head title="Inventory — Locations" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Locations"
        subtitle="Storage rooms, operatories, and cabinets where stock is tracked."
        icon="building"
        eyebrow="Inventory"
      >
        <template #actions>
          <Button icon="plus" @click="openCreate">New location</Button>
        </template>
      </PageHeader>

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ locations.total }} {{ locations.total === 1 ? 'location' : 'locations' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted pointer-events-none">
              <Icon name="search" size="sm" />
            </span>
            <input
              v-model="q"
              type="search"
              placeholder="Search by name"
              class="input-shell h-10 pl-9 pr-9"
            />
          </div>
        </div>
        <select v-model="isActive" class="input-shell h-10" @change="applyFilters">
          <option v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
      </FilterBar>

      <Card :padded="false">
        <div class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Name</th>
                <th class="w-32">Type</th>
                <th class="w-40 text-right">Products tracked</th>
                <th class="w-28">Status</th>
                <th class="w-24 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="loc in locations.data" :key="loc.id">
                <td class="font-medium text-base-content">{{ loc.name }}</td>
                <td class="text-muted capitalize">{{ loc.location_type }}</td>
                <td class="text-right text-muted">{{ loc.stock_levels_count }}</td>
                <td>
                  <Badge :variant="loc.is_active ? 'success' : 'warning'" dot>
                    {{ loc.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>
                <td class="text-right">
                  <button
                    type="button"
                    class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                    :aria-label="`Edit ${loc.name}`"
                    :title="`Edit ${loc.name}`"
                    @click="openEdit(loc)"
                  >
                    <Icon name="edit" size="sm" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <EmptyState
            v-if="locations.data.length === 0"
            icon="building"
            title="No locations yet"
            description="Add storage rooms, operatories, or cabinets to start tracking stock by site."
          >
            <template #action>
              <Button icon="plus" @click="openCreate">New location</Button>
            </template>
          </EmptyState>
        </div>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="locations.current_page"
            :last-page="locations.last_page"
            :total="locations.total"
            :per-page="locations.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>

    <Dialog v-model="createOpen" title="New location" icon="plus" size="md">
      <form @submit.prevent="submitCreate" class="space-y-4">
        <Field label="Name" required :error="createForm.errors.name" html-for="loc-name">
          <Input id="loc-name" v-model="createForm.name" placeholder="e.g. Operatory 1" />
        </Field>
        <Field label="Type" html-for="loc-type">
          <Select id="loc-type" v-model="createForm.location_type" :options="typeOptions" />
        </Field>
        <Field label="Active">
          <Switch v-model="createForm.is_active" label="Available for stock activity" />
        </Field>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="ghost" @click="createOpen = false">Cancel</Button>
          <Button type="submit" :loading="createForm.processing">Create</Button>
        </div>
      </form>
    </Dialog>

    <Dialog v-model="editOpen" title="Edit location" icon="edit" size="md">
      <form @submit.prevent="submitEdit" class="space-y-4">
        <Field label="Name" required :error="editForm.errors.name" html-for="edit-loc-name">
          <Input id="edit-loc-name" v-model="editForm.name" />
        </Field>
        <Field label="Type" html-for="edit-loc-type">
          <Select id="edit-loc-type" v-model="editForm.location_type" :options="typeOptions" />
        </Field>
        <Field label="Active">
          <Switch v-model="editForm.is_active" label="Available for stock activity" />
        </Field>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="ghost" @click="editOpen = false">Cancel</Button>
          <Button type="submit" :loading="editForm.processing">Save</Button>
        </div>
      </form>
    </Dialog>
  </AuthenticatedLayout>
</template>
