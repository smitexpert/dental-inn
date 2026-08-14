<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'

const props = defineProps({ product: Object, movements: Array, pagination: Object })
</script>

<template>
  <Head :title="`Movements — ${product.name}`" />
  <AuthenticatedLayout>
    <UiCard>
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">{{ product.name }} — Movement history</h3>
          <Link :href="`/inventory/products/${product.id}`" class="text-primary text-sm hover:text-primary">← Back to product</Link>
        </div>
      </template>
      <p class="text-xs text-base-content/70 mb-4 font-mono">SKU {{ product.sku }} · {{ pagination.total }} movements</p>
      <table class="w-full text-sm">
        <thead class="bg-base-200 text-base-content/70">
          <tr>
            <th class="text-left px-4 py-2">When</th>
            <th class="text-left px-4 py-2">Type</th>
            <th class="text-right px-4 py-2">Qty</th>
            <th class="text-left px-4 py-2">Location</th>
            <th class="text-left px-4 py-2">By</th>
            <th class="text-right px-4 py-2">Unit cost</th>
            <th class="text-left px-4 py-2">Reason</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in movements" :key="m.id" class="border-b border-base-300 last:border-b-0">
            <td class="px-4 py-2 text-base-content/70 text-xs">{{ m.created_at }}</td>
            <td class="px-4 py-2">
              <UiBadge :variant="m.quantity >= 0 ? 'success' : 'danger'">{{ m.type_label }}</UiBadge>
            </td>
            <td :class="['px-4 py-2 text-right font-mono', m.quantity >= 0 ? 'text-success' : 'text-error']">
              {{ m.quantity >= 0 ? '+' : '' }}{{ m.quantity }}
            </td>
            <td class="px-4 py-2 text-base-content/70">{{ m.location_name || '—' }}</td>
            <td class="px-4 py-2 text-base-content/70">{{ m.performer_name || '—' }}</td>
            <td class="px-4 py-2 text-right text-base-content/70">{{ m.unit_cost || '—' }}</td>
            <td class="px-4 py-2 text-base-content/70">{{ m.reason || '—' }}</td>
          </tr>
          <tr v-if="movements.length === 0">
            <td colspan="7" class="text-center text-base-content/70 py-6">No movements recorded.</td>
          </tr>
        </tbody>
      </table>
    </UiCard>
  </AuthenticatedLayout>
</template>
