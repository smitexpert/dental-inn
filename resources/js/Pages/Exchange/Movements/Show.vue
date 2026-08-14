<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'

const props = defineProps({ movement: { type: Object, required: true } })

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}
</script>

<template>
  <Head title="Cash movement" />
  <AuthenticatedLayout>
    <UiCard :title="`Movement #${movement.id}`">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-foreground">Movement #{{ movement.id }}</h3>
          <Link href="/exchange/movements" class="text-primary-600 hover:underline text-sm">Back to list</Link>
        </div>
      </template>

      <dl class="grid grid-cols-2 gap-3 text-sm">
        <div>
          <dt class="text-muted-foreground text-xs">Date</dt>
          <dd class="text-foreground">{{ formatDate(movement.created_at) }}</dd>
        </div>
        <div>
          <dt class="text-muted-foreground text-xs">Type</dt>
          <dd>
            <UiBadge :variant="movement.type_color">{{ movement.type_label }}</UiBadge>
          </dd>
        </div>
        <div>
          <dt class="text-muted-foreground text-xs">Currency</dt>
          <dd class="text-foreground font-mono">{{ movement.currency?.symbol }} {{ movement.currency_code }}</dd>
        </div>
        <div>
          <dt class="text-muted-foreground text-xs">Amount</dt>
          <dd class="text-foreground font-medium">{{ movement.amount }}</dd>
        </div>
        <div v-if="movement.counter_amount">
          <dt class="text-muted-foreground text-xs">Counter amount</dt>
          <dd class="text-foreground">{{ movement.counter_currency_code }} {{ movement.counter_amount }}</dd>
        </div>
        <div v-if="movement.exchange_group_id">
          <dt class="text-muted-foreground text-xs">Exchange group</dt>
          <dd class="text-foreground font-mono text-xs">{{ movement.exchange_group_id }}</dd>
        </div>
        <div class="col-span-2">
          <dt class="text-muted-foreground text-xs">Reason</dt>
          <dd class="text-foreground">{{ movement.reason ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-muted-foreground text-xs">Performed by</dt>
          <dd class="text-foreground">{{ movement.performer ?? '—' }}</dd>
        </div>
      </dl>
    </UiCard>
  </AuthenticatedLayout>
</template>
