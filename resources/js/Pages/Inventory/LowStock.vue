<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ products: Object })
</script>

<template>
  <Head title="Inventory — Low stock" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Low stock"
        subtitle="Products whose minimum stock across all locations is at or below their reorder level."
        icon="alert-triangle"
        eyebrow="Inventory"
      >
        <template #actions>
          <Link href="/inventory/products">
            <Button variant="outline" icon="boxes">All products</Button>
          </Link>
        </template>
      </PageHeader>

      <Card :padded="false">
        <div class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th class="w-32">SKU</th>
                <th>Name</th>
                <th class="w-32 text-right">Min stock</th>
                <th class="w-28 text-right">Reorder</th>
                <th class="w-32 text-right">Suggested qty</th>
                <th class="w-32 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in products.data" :key="p.id">
                <td class="font-mono text-xs text-muted">{{ p.sku }}</td>
                <td>
                  <Link :href="`/inventory/products/${p.id}`" class="font-medium text-base-content hover:text-primary transition-colors">
                    {{ p.name }}
                  </Link>
                </td>
                <td class="text-right">
                  <Badge variant="danger">
                    {{ p.lowest_quantity }} {{ p.unit?.shortLabel?.() ?? '' }}
                  </Badge>
                </td>
                <td class="text-right text-muted">{{ p.reorder_level }}</td>
                <td class="text-right text-muted">{{ p.reorder_quantity }}</td>
                <td class="text-right">
                  <div class="inline-flex items-center gap-1">
                    <Link :href="`/inventory/products/${p.id}`" class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors" :aria-label="`View ${p.name}`" :title="`View ${p.name}`">
                      <Icon name="eye" size="sm" />
                    </Link>
                    <a href="#" class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors" title="Auto-reorder coming soon" :aria-label="`Create purchase order for ${p.name}`">
                      <Icon name="send" size="sm" />
                    </a>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <EmptyState
            v-if="products.data.length === 0"
            icon="check-circle"
            tone="success"
            title="All stocked up"
            description="Every product is currently above its reorder threshold."
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
