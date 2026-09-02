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
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ product: Object, movements: Array, pagination: Object })

const product = props.product

const breadcrumbs = computed(() => [
  { label: 'Products', href: '/inventory/products' },
  { label: product.name, href: `/inventory/products/${product.id}` },
  { label: 'Movements' },
])
</script>

<template>
  <Head :title="`Movements — ${product.name}`" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-6xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`${product.name} — Movements`"
        :subtitle="`Full stock movement history · ${pagination.total} entries`"
        icon="refresh"
        eyebrow="Inventory"
      >
        <template #actions>
          <Link :href="`/inventory/products/${product.id}`">
            <Button variant="outline" icon="arrow-left">Back to product</Button>
          </Link>
        </template>
      </PageHeader>

      <Card :padded="false">
        <div class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th class="w-44">When</th>
                <th class="w-32">Type</th>
                <th class="w-24 text-right">Qty</th>
                <th>Location</th>
                <th>By</th>
                <th class="w-28 text-right">Unit cost</th>
                <th>Reason</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in movements" :key="m.id">
                <td class="text-(--color-muted) text-xs">{{ m.created_at }}</td>
                <td>
                  <Badge :variant="m.quantity >= 0 ? 'success' : 'danger'">
                    {{ m.type_label }}
                  </Badge>
                </td>
                <td :class="['text-right font-mono font-medium', m.quantity >= 0 ? 'text-success' : 'text-error']">
                  {{ m.quantity >= 0 ? '+' : '' }}{{ m.quantity }}
                </td>
                <td class="text-(--color-muted)">{{ m.location_name || '—' }}</td>
                <td class="text-(--color-muted)">{{ m.performer_name || '—' }}</td>
                <td class="text-right text-(--color-muted)">{{ m.unit_cost || '—' }}</td>
                <td class="text-(--color-muted)">{{ m.reason || '—' }}</td>
              </tr>
            </tbody>
          </table>

          <EmptyState
            v-if="movements.length === 0"
            icon="refresh"
            title="No movements recorded"
            description="Stock movements will appear here once the product is purchased, consumed, transferred or adjusted."
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
