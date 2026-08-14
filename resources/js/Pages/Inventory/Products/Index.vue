<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiSwitch from '@/Components/ui/Switch.vue'
import { ref, watch } from 'vue'

const props = defineProps({ products: Object, filters: Object })

const q = ref(props.filters.q ?? '')
const category = ref(props.filters.category ?? '')
const isActive = ref(
  props.filters.is_active === '1' || props.filters.is_active === 1 || props.filters.is_active === true
    ? '1'
    : props.filters.is_active === '0' || props.filters.is_active === 0 || props.filters.is_active === false
      ? '0'
      : '',
)
const lowStock = ref(props.filters.low_stock === '1' || props.filters.low_stock === 1 || props.filters.low_stock === true)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/inventory/products',
      { q: q.value, category: category.value, is_active: isActive.value, low_stock: lowStock.value ? '1' : '' },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, category, isActive, lowStock], applyFilters)
</script>

<template>
  <Head title="Inventory — Products" />
  <AuthenticatedLayout>
    <UiCard title="Products">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-foreground">Products</h3>
          <Link href="/inventory/products/create"><UiButton>New product</UiButton></Link>
        </div>
      </template>
      <div class="grid grid-cols-4 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search by name, SKU, barcode" />
        <UiSelect
          v-model="category"
          :options="[
            { label: 'All categories', value: '' },
            { label: 'Consumable', value: 'consumable' },
            { label: 'Equipment', value: 'equipment' },
            { label: 'Medication', value: 'medication' },
            { label: 'Material', value: 'material' },
            { label: 'Other', value: 'other' },
          ]"
        />
        <UiSelect
          v-model="isActive"
          :options="[
            { label: 'All', value: '' },
            { label: 'Active only', value: '1' },
            { label: 'Inactive only', value: '0' },
          ]"
        />
        <div class="flex items-center gap-2 px-3">
          <UiSwitch v-model="lowStock" />
          <span class="text-sm text-foreground">Low stock only</span>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">SKU</th>
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Category</th>
              <th class="text-right px-4 py-2">Stock</th>
              <th class="text-right px-4 py-2">Reorder</th>
              <th class="text-left px-4 py-2">Status</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in products.data" :key="p.id" class="border-b border-border last:border-b-0">
              <td class="px-4 py-2 text-muted-foreground font-mono text-xs">{{ p.sku }}</td>
              <td class="px-4 py-2 text-foreground font-medium">{{ p.name }}</td>
              <td class="px-4 py-2 text-muted-foreground">{{ p.category_label || p.category }}</td>
              <td class="px-4 py-2 text-right">
                <UiBadge :variant="p.is_low ? 'danger' : 'neutral'">
                  {{ p.total_quantity }} {{ p.unit_short || p.unit }}
                </UiBadge>
              </td>
              <td class="px-4 py-2 text-right text-muted-foreground">{{ p.reorder_level }}</td>
              <td class="px-4 py-2">
                <UiBadge :variant="p.is_active ? 'success' : 'neutral'">
                  {{ p.is_active ? 'Active' : 'Inactive' }}
                </UiBadge>
              </td>
              <td class="px-4 py-2 text-right">
                <Link :href="`/inventory/products/${p.id}`" class="text-primary-600 hover:text-primary-700 mr-3 text-sm">View</Link>
                <Link :href="`/inventory/products/${p.id}/edit`" class="text-primary-600 hover:text-primary-700 text-sm">Edit</Link>
              </td>
            </tr>
            <tr v-if="products.data.length === 0">
              <td colspan="7" class="text-center text-muted-foreground py-6">No products found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-if="products.last_page > 1"
        class="mt-3 flex items-center justify-between text-sm text-muted-foreground"
      >
        <span>Page {{ products.current_page }} of {{ products.last_page }}</span>
        <div class="flex gap-1">
          <template v-for="link in products.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              class="px-2 py-1 rounded hover:bg-surface-muted"
            ><span v-html="link.label" /></Link>
          </template>
        </div>
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
