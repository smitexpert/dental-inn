<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import { ref, computed, watch } from 'vue'
import { useToast } from '@/Composables/useToast.js'

const props = defineProps({
  currencies: { type: Array, default: () => [] },
  drawers: { type: Array, default: () => [] },
})

const page = usePage()
const errors = computed(() => page.props.errors ?? {})

const TYPES = [
  { value: 'cash_in', label: 'Cash in' },
  { value: 'cash_out', label: 'Cash out' },
  { value: 'exchange_in', label: 'Exchange in' },
  { value: 'exchange_out', label: 'Exchange out' },
  { value: 'adjustment', label: 'Adjustment' },
]

const form = ref({
  type: 'cash_in',
  currency_code: props.currencies[0]?.code ?? '',
  to_currency_code: props.currencies[1]?.code ?? props.currencies[0]?.code ?? '',
  amount: '',
  reason: '',
})

const isExchange = computed(() => form.value.type === 'exchange_in' || form.value.type === 'exchange_out')
const requiresReason = computed(() => ['cash_in', 'cash_out', 'adjustment'].includes(form.value.type))

const sourceDrawer = computed(() => props.drawers.find((d) => d.currency_code === form.value.currency_code))

function findCurrency(code) {
  return props.currencies.find((c) => c.code === code)
}

function submit() {
  router.post('/exchange/movements', form.value, { preserveScroll: true })
}

watch(() => form.value.type, (t) => {
  // when switching to exchange, default the to_currency_code to a different one
  if (t === 'exchange_in' || t === 'exchange_out') {
    if (form.value.to_currency_code === form.value.currency_code) {
      const other = props.currencies.find((c) => c.code !== form.value.currency_code)
      if (other) form.value.to_currency_code = other.code
    }
  }
})
</script>

<template>
  <Head title="New cash movement" />
  <AuthenticatedLayout>
    <UiCard title="New cash movement">
      <form @submit.prevent="submit" class="grid grid-cols-2 gap-3">
        <UiSelect
          v-model="form.type"
          label="Type"
          :options="TYPES"
          :error="errors.type"
        />

        <UiSelect
          v-if="!isExchange"
          v-model="form.currency_code"
          label="Currency"
          :options="currencies.map((c) => ({ label: `${c.code} – ${c.name}`, value: c.code }))"
          :error="errors.currency_code"
        />
        <div v-else class="grid grid-cols-2 gap-3 col-span-1">
          <UiSelect
            v-model="form.currency_code"
            label="From"
            :options="currencies.map((c) => ({ label: `${c.code} – ${c.name}`, value: c.code }))"
            :error="errors.currency_code"
          />
          <UiSelect
            v-model="form.to_currency_code"
            label="To"
            :options="currencies.map((c) => ({ label: `${c.code} – ${c.name}`, value: c.code }))"
            :error="errors.to_currency_code"
          />
        </div>

        <UiInput
          v-model="form.amount"
          label="Amount"
          type="number"
          step="0.0001"
          :error="errors.amount"
        />

        <div v-if="sourceDrawer" class="text-xs text-muted-foreground self-end">
          Current balance:
          <span class="font-medium text-foreground">
            {{ findCurrency(form.currency_code)?.symbol }}{{ Number(sourceDrawer.balance).toFixed(4) }}
          </span>
        </div>

        <UiInput
          v-if="requiresReason"
          v-model="form.reason"
          label="Reason"
          placeholder="What is this for?"
          :error="errors.reason"
        />

        <div class="col-span-2 flex gap-2 justify-end">
          <Link href="/exchange/movements"><UiButton variant="secondary">Cancel</UiButton></Link>
          <UiButton type="submit">Save</UiButton>
        </div>
      </form>
    </UiCard>
  </AuthenticatedLayout>
</template>
