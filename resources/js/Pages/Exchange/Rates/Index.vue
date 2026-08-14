<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiPagination from '@/Components/ui/Pagination.vue'
import UiEmptyState from '@/Components/ui/EmptyState.vue'
import { ref, watch, computed } from 'vue'

const props = defineProps({
  rates: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  currencies: { type: Array, default: () => [] },
})

const page = usePage()
const canManage = computed(() => page.props.auth?.user?.permissions?.includes('manage-exchange-rates'))

const from = ref(props.filters.from ?? '')
const to = ref(props.filters.to ?? '')
const q = ref(props.filters.q ?? '')
const showForm = ref(false)
const form = ref({
  from_currency_code: props.currencies[0]?.code ?? '',
  to_currency_code: props.currencies[1]?.code ?? props.currencies[0]?.code ?? '',
  rate: '',
  effective_at: new Date().toISOString().slice(0, 10),
})

const errors = computed(() => page.props.errors ?? {})

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get('/exchange/rates', { from: from.value, to: to.value, q: q.value }, { preserveState: true, replace: true })
  }, 300)
}
watch([from, to, q], applyFilters)

function submit() {
  router.post('/exchange/rates', form.value, { preserveScroll: true })
}
</script>

<template>
  <Head title="Exchange rates" />
  <AuthenticatedLayout>
    <UiCard title="Exchange rates">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Exchange rates</h3>
          <UiButton v-if="canManage && !showForm" @click="showForm = true">Add rate</UiButton>
        </div>
      </template>

      <div v-if="canManage && showForm" class="bg-base-200 rounded-md p-4 mb-4 grid grid-cols-5 gap-3">
        <UiSelect
          v-model="form.from_currency_code"
          label="From"
          :options="currencies.map((c) => ({ label: `${c.code} – ${c.name}`, value: c.code }))"
          :error="errors.from_currency_code"
        />
        <UiSelect
          v-model="form.to_currency_code"
          label="To"
          :options="currencies.map((c) => ({ label: `${c.code} – ${c.name}`, value: c.code }))"
          :error="errors.to_currency_code"
        />
        <UiInput v-model="form.rate" label="Rate" type="number" step="0.00000001" :error="errors.rate" />
        <UiInput v-model="form.effective_at" label="Effective at" type="date" :error="errors.effective_at" />
        <div class="flex items-end gap-2">
          <UiButton @click="submit">Save</UiButton>
          <UiButton variant="secondary" @click="showForm = false">Cancel</UiButton>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-3 mb-4">
        <UiSelect
          v-model="from"
          :options="[{ label: 'All from', value: '' }, ...currencies.map((c) => ({ label: c.code, value: c.code }))]"
        />
        <UiSelect
          v-model="to"
          :options="[{ label: 'All to', value: '' }, ...currencies.map((c) => ({ label: c.code, value: c.code }))]"
        />
        <UiInput v-model="q" placeholder="Search…" />
      </div>

      <UiEmptyState v-if="rates.data.length === 0" title="No rates yet" description="Add a rate above to get started." />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-base-200 text-base-content/70">
            <tr>
              <th class="text-left px-4 py-2">From</th>
              <th class="text-left px-4 py-2">To</th>
              <th class="text-right px-4 py-2">Rate</th>
              <th class="text-left px-4 py-2">Effective at</th>
              <th class="text-left px-4 py-2">By</th>
              <th v-if="canManage" class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rates.data" :key="r.id" class="border-b border-base-300 last:border-b-0">
              <td class="px-4 py-2 text-base-content font-mono text-xs">{{ r.from_currency_code }}</td>
              <td class="px-4 py-2 text-base-content font-mono text-xs">{{ r.to_currency_code }}</td>
              <td class="px-4 py-2 text-right text-base-content">{{ r.rate }}</td>
              <td class="px-4 py-2 text-base-content/70">{{ r.effective_at }}</td>
              <td class="px-4 py-2 text-base-content/70 text-xs">{{ r.creator?.name ?? '—' }}</td>
              <td v-if="canManage" class="px-4 py-2 text-right">
                <button
                  class="text-error-600 hover:underline"
                  @click="router.delete(`/exchange/rates/${r.id}`)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        <UiPagination :paginator="rates" />
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
