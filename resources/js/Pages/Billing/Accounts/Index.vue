<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiPagination from '@/Components/ui/Pagination.vue'
import UiEmptyState from '@/Components/ui/EmptyState.vue'
import { ref, watch } from 'vue'

const props = defineProps({ accounts: Object, filters: Object })

const q = ref(props.filters.q ?? '')
const type = ref(props.filters.type ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get('/billing/accounts', { q: q.value, type: type.value }, { preserveState: true, replace: true })
  }, 300)
}
watch([q, type], applyFilters)

function typeVariant(t) {
  const m = { asset: 'success', liability: 'danger', equity: 'info', revenue: 'success', expense: 'warning' }
  return m[t] ?? 'neutral'
}
</script>

<template>
  <Head title="Chart of accounts" />
  <AuthenticatedLayout>
    <UiCard title="Chart of accounts">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-foreground">Chart of accounts</h3>
          <Link href="/billing/accounts/create"><UiButton>New account</UiButton></Link>
        </div>
      </template>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search by code / name" />
        <UiSelect v-model="type" :options="[
          { label: 'All types', value: '' },
          { label: 'Asset', value: 'asset' },
          { label: 'Liability', value: 'liability' },
          { label: 'Equity', value: 'equity' },
          { label: 'Revenue', value: 'revenue' },
          { label: 'Expense', value: 'expense' },
        ]" />
      </div>

      <UiEmptyState v-if="accounts.data.length === 0" title="No accounts" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">Code</th>
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Type</th>
              <th class="text-left px-4 py-2">Status</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in accounts.data" :key="a.id" class="border-b border-border last:border-b-0">
              <td class="px-4 py-2 text-foreground font-mono text-xs">{{ a.code }}</td>
              <td class="px-4 py-2 text-foreground font-medium">{{ a.name }}</td>
              <td class="px-4 py-2">
                <UiBadge :variant="typeVariant(a.type)">{{ a.type_label ?? a.type }}</UiBadge>
              </td>
              <td class="px-4 py-2">
                <UiBadge :variant="a.is_active ? 'success' : 'neutral'">
                  {{ a.is_active ? 'Active' : 'Inactive' }}
                </UiBadge>
              </td>
              <td class="px-4 py-2 text-right">
                <Link :href="`/billing/accounts/${a.id}`" class="text-primary-600 hover:underline">View</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4"><UiPagination :paginator="accounts" /></div>
    </UiCard>
  </AuthenticatedLayout>
</template>
