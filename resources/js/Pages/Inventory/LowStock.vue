<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'

const props = defineProps({ products: Object })
</script>

<template>
  <Head title="Inventory — Low stock" />
  <AuthenticatedLayout>
    <UiCard>
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-foreground">Low stock products</h3>
          <Link href="/inventory/products" class="text-sm text-primary-600 hover:text-primary-700">All products →</Link>
        </div>
      </template>
      <p class="text-xs text-muted-foreground mb-4">
        Showing products whose minimum stock across all locations is at or below their reorder level.
      </p>
      <table class="w-full text-sm">
        <thead class="bg-surface-muted text-muted-foreground">
          <tr>
            <th class="text-left px-4 py-2">SKU</th>
            <th class="text-left px-4 py-2">Name</th>
            <th class="text-right px-4 py-2">Min stock</th>
            <th class="text-right px-4 py-2">Reorder</th>
            <th class="text-right px-4 py-2">Suggested qty</th>
            <th class="text-right px-4 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in products.data" :key="p.id" class="border-b border-border last:border-b-0">
            <td class="px-4 py-2 text-muted-foreground font-mono text-xs">{{ p.sku }}</td>
            <td class="px-4 py-2 text-foreground font-medium">{{ p.name }}</td>
            <td class="px-4 py-2 text-right">
              <UiBadge variant="danger">{{ p.lowest_quantity }} {{ p.unit?.shortLabel?.() ?? '' }}</UiBadge>
            </td>
            <td class="px-4 py-2 text-right text-muted-foreground">{{ p.reorder_level }}</td>
            <td class="px-4 py-2 text-right text-muted-foreground">{{ p.reorder_quantity }}</td>
            <td class="px-4 py-2 text-right">
              <Link :href="`/inventory/products/${p.id}`" class="text-primary-600 hover:text-primary-700 mr-3 text-sm">View</Link>
              <a href="#" class="text-muted-foreground text-sm" title="Auto-reorder coming in sub-project 6">Create PO</a>
            </td>
          </tr>
          <tr v-if="products.data.length === 0">
            <td colspan="6" class="text-center text-muted-foreground py-6">
              All products are above their reorder threshold.
            </td>
          </tr>
        </tbody>
      </table>
    </UiCard>
  </AuthenticatedLayout>
</template>
