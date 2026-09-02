<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
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

const props = defineProps({ suppliers: Object, filters: Object })

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
      '/inventory/suppliers',
      { q: q.value, is_active: isActive.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, isActive], applyFilters)

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Active only', value: '1' },
  { label: 'Inactive only', value: '0' },
]

function goPage(n) {
  router.get(
    `/inventory/suppliers?page=${n}`,
    { q: q.value, is_active: isActive.value },
    { preserveState: true },
  )
}
</script>

<template>
  <Head title="Inventory — Suppliers" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Suppliers"
        subtitle="Manage vendor contacts and ordering information."
        icon="send"
        eyebrow="Inventory"
      >
        <template #actions>
          <Link href="/inventory/suppliers/create">
            <Button icon="plus">New supplier</Button>
          </Link>
        </template>
      </PageHeader>

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ suppliers.total }} {{ suppliers.total === 1 ? 'supplier' : 'suppliers' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-(--color-muted) pointer-events-none">
              <Icon name="search" size="sm" />
            </span>
            <input
              v-model="q"
              type="search"
              placeholder="Search by name, phone, email"
              class="input-shell h-10 pl-9"
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
                <th>Contact</th>
                <th>Phone</th>
                <th>Email</th>
                <th class="w-28">Status</th>
                <th class="w-28 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in suppliers.data" :key="s.id">
                <td>
                  <Link :href="`/inventory/suppliers/${s.id}`" class="font-medium text-base-content hover:text-primary transition-colors">
                    {{ s.name }}
                  </Link>
                </td>
                <td class="text-(--color-muted)">{{ s.contact_person || '—' }}</td>
                <td class="text-(--color-muted)">{{ s.phone || '—' }}</td>
                <td class="text-(--color-muted)">{{ s.email || '—' }}</td>
                <td>
                  <Badge :variant="s.is_active ? 'success' : 'warning'" dot>
                    {{ s.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>
                <td class="text-right">
                  <div class="inline-flex items-center gap-1">
                    <Link :href="`/inventory/suppliers/${s.id}`" class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors" :aria-label="`View ${s.name}`" :title="`View ${s.name}`">
                      <Icon name="eye" size="sm" />
                    </Link>
                    <Link :href="`/inventory/suppliers/${s.id}/edit`" class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors" :aria-label="`Edit ${s.name}`" :title="`Edit ${s.name}`">
                      <Icon name="edit" size="sm" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <EmptyState
            v-if="suppliers.data.length === 0"
            icon="send"
            title="No suppliers yet"
            description="Add your first supplier to start tracking cost, lead time and orders."
          >
            <template #action>
              <Link href="/inventory/suppliers/create">
                <Button icon="plus">New supplier</Button>
              </Link>
            </template>
          </EmptyState>
        </div>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="suppliers.current_page"
            :last-page="suppliers.last_page"
            :total="suppliers.total"
            :per-page="suppliers.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
