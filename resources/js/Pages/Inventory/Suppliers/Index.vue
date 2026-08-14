<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import { ref, watch } from 'vue'

const props = defineProps({ suppliers: Object, filters: Object })

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
    router.get('/inventory/suppliers', { q: q.value, is_active: isActive.value }, { preserveState: true, replace: true })
  }, 300)
}
watch([q, isActive], applyFilters)
</script>

<template>
  <Head title="Inventory — Suppliers" />
  <AuthenticatedLayout>
    <UiCard title="Suppliers">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Suppliers</h3>
          <Link href="/inventory/suppliers/create"><UiButton>New supplier</UiButton></Link>
        </div>
      </template>
      <div class="grid grid-cols-2 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search by name, phone, email" />
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
            <th class="text-left px-4 py-2">Contact</th>
            <th class="text-left px-4 py-2">Phone</th>
            <th class="text-left px-4 py-2">Email</th>
            <th class="text-left px-4 py-2">Status</th>
            <th class="text-right px-4 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in suppliers.data" :key="s.id" class="border-b border-base-300 last:border-b-0">
            <td class="px-4 py-2 text-base-content font-medium">{{ s.name }}</td>
            <td class="px-4 py-2 text-base-content/70">{{ s.contact_person || '—' }}</td>
            <td class="px-4 py-2 text-base-content/70">{{ s.phone || '—' }}</td>
            <td class="px-4 py-2 text-base-content/70">{{ s.email || '—' }}</td>
            <td class="px-4 py-2">
              <UiBadge :variant="s.is_active ? 'success' : 'neutral'">
                {{ s.is_active ? 'Active' : 'Inactive' }}
              </UiBadge>
            </td>
            <td class="px-4 py-2 text-right">
              <Link :href="`/inventory/suppliers/${s.id}`" class="text-primary hover:text-primary mr-3 text-sm">View</Link>
              <Link :href="`/inventory/suppliers/${s.id}/edit`" class="text-primary hover:text-primary text-sm">Edit</Link>
            </td>
          </tr>
          <tr v-if="suppliers.data.length === 0">
            <td colspan="6" class="text-center text-base-content/70 py-6">No suppliers found.</td>
          </tr>
        </tbody>
      </table>
    </UiCard>
  </AuthenticatedLayout>
</template>
