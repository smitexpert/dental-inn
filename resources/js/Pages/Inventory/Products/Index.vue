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

const props = defineProps({ products: Object, filters: Object })

const q = ref(props.filters.q ?? '')
const category = ref(props.filters.category ?? '')
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
const lowStock = ref(
  props.filters.low_stock === '1' ||
  props.filters.low_stock === 1 ||
  props.filters.low_stock === true,
)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/inventory/products',
      {
        q: q.value,
        category: category.value,
        is_active: isActive.value,
        low_stock: lowStock.value ? '1' : '',
      },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, category, isActive, lowStock], applyFilters)

const categoryOptions = [
  { label: 'All categories', value: '' },
  { label: 'Consumable', value: 'consumable' },
  { label: 'Equipment', value: 'equipment' },
  { label: 'Medication', value: 'medication' },
  { label: 'Material', value: 'material' },
  { label: 'Other', value: 'other' },
]

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Active only', value: '1' },
  { label: 'Inactive only', value: '0' },
]

function goPage(n) {
  router.get(
    `/inventory/products?page=${n}`,
    {
      q: q.value,
      category: category.value,
      is_active: isActive.value,
      low_stock: lowStock.value ? '1' : '',
    },
    { preserveState: true },
  )
}
</script>

<template>
  <Head title="Inventory — Products" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Products"
        subtitle="Manage catalog, stock levels, and reorder thresholds."
        icon="boxes"
        eyebrow="Inventory"
      >
        <template #actions>
          <Link href="/inventory/low-stock">
            <Button variant="outline" icon="alert-triangle">Low stock</Button>
          </Link>
          <Link href="/inventory/products/create">
            <Button icon="plus">New product</Button>
          </Link>
        </template>
      </PageHeader>

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ products.total }} {{ products.total === 1 ? 'product' : 'products' }}
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
              placeholder="Search by name, SKU, barcode"
              class="input-shell h-10 pl-9"
            />
          </div>
        </div>
        <select v-model="category" class="input-shell h-10" @change="applyFilters">
          <option v-for="o in categoryOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
        <select v-model="isActive" class="input-shell h-10" @change="applyFilters">
          <option v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
        <label class="inline-flex items-center gap-2 px-3 h-10 cursor-pointer select-none">
          <input
            v-model="lowStock"
            type="checkbox"
            class="h-4 w-4 rounded border-base-300 text-primary focus:ring-primary/30"
            @change="applyFilters"
          />
          <span class="text-sm text-base-content">Low stock only</span>
        </label>
      </FilterBar>

      <Card :padded="false">
        <div class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th class="w-32">SKU</th>
                <th>Name</th>
                <th>Category</th>
                <th class="w-36 text-right">Stock</th>
                <th class="w-24 text-right">Reorder</th>
                <th class="w-28">Status</th>
                <th class="w-28 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in products.data" :key="p.id">
                <td class="font-mono text-xs text-(--color-muted)">{{ p.sku }}</td>
                <td>
                  <Link :href="`/inventory/products/${p.id}`" class="font-medium text-base-content hover:text-primary transition-colors">
                    {{ p.name }}
                  </Link>
                </td>
                <td class="text-(--color-muted)">{{ p.category_label || p.category }}</td>
                <td class="text-right">
                  <Badge :variant="p.is_low ? 'danger' : 'success'">
                    {{ p.total_quantity }} {{ p.unit_short || p.unit }}
                  </Badge>
                </td>
                <td class="text-right text-(--color-muted)">{{ p.reorder_level }}</td>
                <td>
                  <Badge :variant="p.is_active ? 'success' : 'warning'" dot>
                    {{ p.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>
                <td class="text-right">
                  <div class="inline-flex items-center gap-1">
                    <Link :href="`/inventory/products/${p.id}`" class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors" :aria-label="`View ${p.name}`" :title="`View ${p.name}`">
                      <Icon name="eye" size="sm" />
                    </Link>
                    <Link :href="`/inventory/products/${p.id}/edit`" class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors" :aria-label="`Edit ${p.name}`" :title="`Edit ${p.name}`">
                      <Icon name="edit" size="sm" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <EmptyState
            v-if="products.data.length === 0"
            icon="boxes"
            title="No products found"
            description="Try adjusting your filters or add a new product to the catalog."
          >
            <template #action>
              <Link href="/inventory/products/create">
                <Button icon="plus">New product</Button>
              </Link>
            </template>
          </EmptyState>
        </div>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="products.current_page"
            :last-page="products.last_page"
            :total="products.total"
            :per-page="products.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
