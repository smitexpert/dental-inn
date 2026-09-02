<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const props = defineProps({ supplier: Object, products: Array })

const supplier = props.supplier

const breadcrumbs = computed(() => [
  { label: 'Suppliers', href: '/inventory/suppliers' },
  { label: supplier.name },
])
</script>

<template>
  <Head :title="supplier.name" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-5xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="supplier.name"
        :subtitle="supplier.contact_person || 'No contact on file'"
        icon="send"
        :eyebrow="`Supplier · ${supplier.is_active ? 'Active' : 'Inactive'}`"
      >
        <template #actions>
          <Badge :variant="supplier.is_active ? 'success' : 'warning'" dot>
            {{ supplier.is_active ? 'Active' : 'Inactive' }}
          </Badge>
          <Link :href="`/inventory/suppliers/${supplier.id}/edit`">
            <Button variant="outline" icon="edit">Edit</Button>
          </Link>
        </template>
      </PageHeader>

      <Card title="Contact">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
          <div>
            <dt class="text-(--color-muted)">Phone</dt>
            <dd class="font-medium mt-0.5">{{ supplier.phone || '—' }}</dd>
          </div>
          <div>
            <dt class="text-(--color-muted)">Email</dt>
            <dd class="font-medium mt-0.5">{{ supplier.email || '—' }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-(--color-muted)">Address</dt>
            <dd class="font-medium mt-0.5 whitespace-pre-line">{{ supplier.address || '—' }}</dd>
          </div>
          <div v-if="supplier.notes" class="sm:col-span-2">
            <dt class="text-(--color-muted)">Notes</dt>
            <dd class="font-medium mt-0.5 whitespace-pre-line">{{ supplier.notes }}</dd>
          </div>
        </dl>
      </Card>

      <Card title="Products supplied" :padded="false">
        <EmptyState
          v-if="products.length === 0"
          icon="package"
          title="No products linked"
          description="Link products to track their supplier cost, lead time, and preferred ordering."
        />
        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th class="w-32">SKU</th>
                <th>Name</th>
                <th>Their SKU</th>
                <th class="text-right">Unit cost</th>
                <th class="text-right">Lead time</th>
                <th>Preferred</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in products" :key="p.id">
                <td class="font-mono text-xs text-(--color-muted)">{{ p.sku }}</td>
                <td>
                  <Link :href="`/inventory/products/${p.id}`" class="font-medium text-base-content hover:text-primary transition-colors">
                    {{ p.name }}
                  </Link>
                </td>
                <td class="font-mono text-xs text-(--color-muted)">{{ p.supplier_sku || '—' }}</td>
                <td class="text-right">{{ p.unit_cost || '—' }}</td>
                <td class="text-right">{{ p.lead_time_days ? `${p.lead_time_days} d` : '—' }}</td>
                <td>
                  <Badge :variant="p.is_preferred ? 'primary' : 'neutral'">
                    {{ p.is_preferred ? 'Yes' : 'No' }}
                  </Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <div class="flex justify-end">
        <Link href="/inventory/suppliers">
          <Button variant="ghost" icon="arrow-left">Back to suppliers</Button>
        </Link>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
