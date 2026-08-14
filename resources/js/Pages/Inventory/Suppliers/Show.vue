<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'

const props = defineProps({ supplier: Object, products: Array })
</script>

<template>
  <Head :title="supplier.name" />
  <AuthenticatedLayout>
    <UiCard>
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-base-content">{{ supplier.name }}</h3>
            <p class="text-xs text-base-content/70 mt-1">{{ supplier.contact_person || 'No contact on file' }}</p>
          </div>
          <div class="flex items-center gap-2">
            <UiBadge :variant="supplier.is_active ? 'success' : 'neutral'">{{ supplier.is_active ? 'Active' : 'Inactive' }}</UiBadge>
            <Link :href="`/inventory/suppliers/${supplier.id}/edit`" class="text-primary hover:text-primary text-sm">Edit</Link>
            <Link href="/inventory/suppliers" class="text-base-content/70 text-sm">← Back</Link>
          </div>
        </div>
      </template>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div>
          <p class="text-xs uppercase tracking-wide text-base-content/70">Phone</p>
          <p class="text-base-content">{{ supplier.phone || '—' }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-base-content/70">Email</p>
          <p class="text-base-content">{{ supplier.email || '—' }}</p>
        </div>
        <div class="col-span-2">
          <p class="text-xs uppercase tracking-wide text-base-content/70">Address</p>
          <p class="text-base-content whitespace-pre-line">{{ supplier.address || '—' }}</p>
        </div>
        <div class="col-span-2">
          <p class="text-xs uppercase tracking-wide text-base-content/70">Notes</p>
          <p class="text-base-content whitespace-pre-line">{{ supplier.notes || '—' }}</p>
        </div>
      </div>
    </UiCard>

    <UiCard class="mt-4" title="Products supplied">
      <table class="w-full text-sm">
        <thead class="bg-base-200 text-base-content/70">
          <tr>
            <th class="text-left px-4 py-2">SKU</th>
            <th class="text-left px-4 py-2">Name</th>
            <th class="text-left px-4 py-2">Their SKU</th>
            <th class="text-right px-4 py-2">Unit cost</th>
            <th class="text-right px-4 py-2">Lead time</th>
            <th class="text-left px-4 py-2">Preferred</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in products" :key="p.id" class="border-b border-base-300 last:border-b-0">
            <td class="px-4 py-2 text-base-content/70 font-mono text-xs">{{ p.sku }}</td>
            <td class="px-4 py-2 text-base-content">{{ p.name }}</td>
            <td class="px-4 py-2 text-base-content/70 font-mono text-xs">{{ p.supplier_sku || '—' }}</td>
            <td class="px-4 py-2 text-right">{{ p.unit_cost || '—' }}</td>
            <td class="px-4 py-2 text-right">{{ p.lead_time_days ? `${p.lead_time_days} d` : '—' }}</td>
            <td class="px-4 py-2">
              <UiBadge :variant="p.is_preferred ? 'primary' : 'neutral'">{{ p.is_preferred ? 'Yes' : 'No' }}</UiBadge>
            </td>
          </tr>
          <tr v-if="products.length === 0">
            <td colspan="6" class="text-center text-base-content/70 py-6">No products linked yet.</td>
          </tr>
        </tbody>
      </table>
    </UiCard>
  </AuthenticatedLayout>
</template>
