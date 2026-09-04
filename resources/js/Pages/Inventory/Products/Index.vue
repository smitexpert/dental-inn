<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import KpiStrip from '@/Components/ui/KpiStrip.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import ResponsiveTable from '@/Components/ui/ResponsiveTable.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({
  products: Object,
  filters: Object,
  kpis: { type: Array, default: () => [] },
})

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

const columns = [
  { key: 'sku', label: 'SKU' },
  { key: 'name', label: 'Name' },
  { key: 'category', label: 'Category' },
  { key: 'total_quantity', label: 'Stock' },
  { key: 'reorder_level', label: 'Reorder' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Inventory — Products" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Products"
        subtitle="Manage catalog, stock levels, and reorder thresholds."
        icon="package"
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
      <KpiStrip :tiles="kpis" />

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ products.total }} {{ products.total === 1 ? 'product' : 'products' }}
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
              placeholder="Search by name, SKU, barcode"
              class="input-shell h-10 pl-9 pr-9"
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

        <ResponsiveTable v-else :columns="columns" :rows="products.data">
          <template #cell:sku="{ row }">
            <span class="font-mono text-xs text-muted">{{ row.sku }}</span>
          </template>
          <template #cell:name="{ row }">
            <Link :href="`/inventory/products/${row.id}`" class="font-medium text-base-content hover:text-primary transition-colors">
              {{ row.name }}
            </Link>
          </template>
          <template #cell:category="{ row }">
            <span class="text-muted">{{ row.category_label || row.category }}</span>
          </template>
          <template #cell:total_quantity="{ row }">
            <div class="text-right">
              <Badge :variant="row.is_low ? 'danger' : 'success'">
                {{ row.total_quantity }} {{ row.unit_short || row.unit }}
              </Badge>
            </div>
          </template>
          <template #cell:reorder_level="{ row }">
            <div class="text-right text-muted">{{ row.reorder_level }}</div>
          </template>
          <template #cell:is_active="{ row }">
            <Badge :variant="row.is_active ? 'success' : 'warning'" dot>
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </Badge>
          </template>
          <template #cell:actions="{ row }">
            <div class="inline-flex items-center gap-1 justify-end">
              <Link :href="`/inventory/products/${row.id}`" class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors" :aria-label="`View ${row.name}`" :title="`View ${row.name}`">
                <Icon name="eye" size="sm" />
              </Link>
              <Link :href="`/inventory/products/${row.id}/edit`" class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors" :aria-label="`Edit ${row.name}`" :title="`Edit ${row.name}`">
                <Icon name="edit" size="sm" />
              </Link>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="text-xs font-mono text-muted">{{ row.sku }}</div>
              <div class="font-medium">{{ row.name }}</div>
              <div class="text-sm text-muted">{{ row.category_label || row.category }}</div>
              <div class="flex items-center justify-between">
                <Badge :variant="row.is_active ? 'success' : 'warning'" dot>
                  {{ row.is_active ? 'Active' : 'Inactive' }}
                </Badge>
                <Badge :variant="row.is_low ? 'danger' : 'success'">
                  {{ row.total_quantity }} {{ row.unit_short || row.unit }}
                </Badge>
              </div>
            </div>
          </template>
        </ResponsiveTable>

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
