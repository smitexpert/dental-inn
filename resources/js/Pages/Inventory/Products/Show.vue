<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiDialog from '@/Components/ui/Dialog.vue'

const props = defineProps({ product: Object, recent_movements: Array, locations: Array, suppliers: Array })

const purchaseOpen = ref(false)
const consumeOpen = ref(false)
const transferOpen = ref(false)
const adjustOpen = ref(false)

const purchaseForm = useForm({
  supplier_id: '',
  location_id: props.locations[0]?.id ?? '',
  quantity: 1,
  unit_cost: '',
  reason: '',
})

const consumeForm = useForm({
  location_id: props.locations[0]?.id ?? '',
  quantity: 1,
  reason: '',
  patient_id: '',
})

const transferForm = useForm({
  from_location_id: props.locations[0]?.id ?? '',
  to_location_id: props.locations[1]?.id ?? props.locations[0]?.id ?? '',
  quantity: 1,
  notes: '',
})

const adjustForm = useForm({
  location_id: props.locations[0]?.id ?? '',
  quantity: 0,
  reason: '',
})

function submitPurchase() {
  purchaseForm.post(`/inventory/products/${props.product.id}/purchase`, { onSuccess: () => (purchaseOpen.value = false) })
}
function submitConsume() {
  consumeForm.post(`/inventory/products/${props.product.id}/consume`, { onSuccess: () => (consumeOpen.value = false) })
}
function submitTransfer() {
  transferForm.post(`/inventory/products/${props.product.id}/transfer`, { onSuccess: () => (transferOpen.value = false) })
}
function submitAdjust() {
  adjustForm.post(`/inventory/products/${props.product.id}/adjust`, { onSuccess: () => (adjustOpen.value = false) })
}
</script>

<template>
  <Head :title="product.name" />
  <AuthenticatedLayout>
    <div class="space-y-4">
      <UiCard>
        <template #header>
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-base font-semibold text-foreground">{{ product.name }}</h3>
              <p class="text-xs text-muted-foreground font-mono mt-1">SKU {{ product.sku }}<span v-if="product.barcode"> · Barcode {{ product.barcode }}</span></p>
            </div>
            <div class="flex items-center gap-2">
              <UiBadge :variant="product.is_active ? 'success' : 'neutral'">
                {{ product.is_active ? 'Active' : 'Inactive' }}
              </UiBadge>
              <Link :href="`/inventory/products/${product.id}/edit`"><UiButton variant="secondary">Edit</UiButton></Link>
              <Link href="/inventory/products"><UiButton variant="ghost">Back</UiButton></Link>
            </div>
          </div>
        </template>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Category</p>
            <p class="text-foreground font-medium">{{ product.category_label }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Unit</p>
            <p class="text-foreground font-medium">{{ product.unit_label }} <span class="text-muted-foreground">({{ product.unit_short }})</span></p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Total stock</p>
            <UiBadge :variant="product.is_low ? 'danger' : 'neutral'">
              {{ product.total_quantity }} {{ product.unit_short }}
            </UiBadge>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Reorder level</p>
            <p class="text-foreground font-medium">{{ product.reorder_level }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Cost price</p>
            <p class="text-foreground font-medium">{{ product.cost_price ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Selling price</p>
            <p class="text-foreground font-medium">{{ product.selling_price ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Track stock</p>
            <p class="text-foreground font-medium">{{ product.track_stock ? 'Yes' : 'No' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Reorder qty</p>
            <p class="text-foreground font-medium">{{ product.reorder_quantity }}</p>
          </div>
        </div>
        <p v-if="product.description" class="text-sm text-muted-foreground mt-4 whitespace-pre-line">{{ product.description }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
          <UiButton variant="primary" size="sm" @click="purchaseOpen = true">Record purchase</UiButton>
          <UiButton variant="secondary" size="sm" @click="consumeOpen = true">Consume</UiButton>
          <UiButton variant="secondary" size="sm" @click="transferOpen = true">Transfer</UiButton>
          <UiButton variant="ghost" size="sm" @click="adjustOpen = true">Adjust</UiButton>
          <Link :href="`/inventory/products/${product.id}/movements`" class="ml-auto"><UiButton variant="ghost" size="sm">Full history →</UiButton></Link>
        </div>
      </UiCard>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <UiCard title="Stock per location">
          <table class="w-full text-sm">
            <thead class="bg-surface-muted text-muted-foreground">
              <tr>
                <th class="text-left px-4 py-2">Location</th>
                <th class="text-right px-4 py-2">Quantity</th>
                <th class="text-right px-4 py-2">Last counted</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in product.stock_levels" :key="l.id" class="border-b border-border last:border-b-0">
                <td class="px-4 py-2 text-foreground">{{ l.location_name }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ l.quantity }}</td>
                <td class="px-4 py-2 text-right text-muted-foreground text-xs">{{ l.last_counted_at || '—' }}</td>
              </tr>
              <tr v-if="product.stock_levels.length === 0">
                <td colspan="3" class="text-center text-muted-foreground py-6">No stock recorded yet.</td>
              </tr>
            </tbody>
          </table>
        </UiCard>

        <UiCard title="Recent movements">
          <ul class="space-y-2 text-sm">
            <li v-for="m in recent_movements" :key="m.id" class="flex items-start justify-between gap-2 border-b border-border pb-2 last:border-b-0 last:pb-0">
              <div>
                <UiBadge :variant="m.quantity >= 0 ? 'success' : 'danger'">
                  {{ m.type_label }}
                </UiBadge>
                <p class="text-muted-foreground text-xs mt-1">{{ m.location_name || '—' }} · {{ m.performer_name || '—' }}</p>
                <p v-if="m.reason" class="text-muted-foreground text-xs">{{ m.reason }}</p>
              </div>
              <div class="text-right">
                <p :class="['font-mono font-medium', m.quantity >= 0 ? 'text-success' : 'text-danger']">
                  {{ m.quantity >= 0 ? '+' : '' }}{{ m.quantity }}
                </p>
                <p class="text-xs text-muted-foreground">{{ m.created_at }}</p>
              </div>
            </li>
            <li v-if="recent_movements.length === 0" class="text-center text-muted-foreground py-4">
              No movements yet.
            </li>
          </ul>
        </UiCard>
      </div>

      <UiCard title="Suppliers">
        <table class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">Supplier</th>
              <th class="text-left px-4 py-2">Their SKU</th>
              <th class="text-right px-4 py-2">Unit cost</th>
              <th class="text-right px-4 py-2">Lead time</th>
              <th class="text-left px-4 py-2">Preferred</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in product.suppliers" :key="s.id" class="border-b border-border last:border-b-0">
              <td class="px-4 py-2 text-foreground">{{ s.name }}</td>
              <td class="px-4 py-2 text-muted-foreground font-mono text-xs">{{ s.supplier_sku || '—' }}</td>
              <td class="px-4 py-2 text-right">{{ s.unit_cost || '—' }}</td>
              <td class="px-4 py-2 text-right">{{ s.lead_time_days ? `${s.lead_time_days} d` : '—' }}</td>
              <td class="px-4 py-2">
                <UiBadge :variant="s.is_preferred ? 'primary' : 'neutral'">
                  {{ s.is_preferred ? 'Preferred' : 'Alternate' }}
                </UiBadge>
              </td>
            </tr>
            <tr v-if="product.suppliers.length === 0">
              <td colspan="5" class="text-center text-muted-foreground py-6">No suppliers linked.</td>
            </tr>
          </tbody>
        </table>
      </UiCard>
    </div>

    <UiDialog v-model="purchaseOpen" title="Record purchase">
      <form @submit.prevent="submitPurchase" class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-1">Supplier</label>
          <UiSelect v-model="purchaseForm.supplier_id" :options="[{ label: '— None —', value: '' }, ...suppliers.map(s => ({ label: s.name, value: s.id }))]" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Location</label>
          <UiSelect v-model="purchaseForm.location_id" :options="locations.map(l => ({ label: l.name, value: l.id }))" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Quantity</label>
          <UiInput v-model="purchaseForm.quantity" type="number" min="1" />
          <p v-if="purchaseForm.errors.quantity" class="text-xs text-danger mt-1">{{ purchaseForm.errors.quantity }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Unit cost</label>
          <UiInput v-model="purchaseForm.unit_cost" type="number" step="0.01" min="0" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Reason / note</label>
          <UiInput v-model="purchaseForm.reason" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="purchaseOpen = false">Cancel</UiButton>
          <UiButton type="submit" :disabled="purchaseForm.processing">Record</UiButton>
        </div>
      </form>
    </UiDialog>

    <UiDialog v-model="consumeOpen" title="Consume stock">
      <form @submit.prevent="submitConsume" class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-1">Location</label>
          <UiSelect v-model="consumeForm.location_id" :options="locations.map(l => ({ label: l.name, value: l.id }))" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Quantity</label>
          <UiInput v-model="consumeForm.quantity" type="number" min="1" />
          <p v-if="consumeForm.errors.quantity" class="text-xs text-danger mt-1">{{ consumeForm.errors.quantity }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Reason</label>
          <UiInput v-model="consumeForm.reason" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="consumeOpen = false">Cancel</UiButton>
          <UiButton type="submit" :disabled="consumeForm.processing">Consume</UiButton>
        </div>
      </form>
    </UiDialog>

    <UiDialog v-model="transferOpen" title="Transfer stock">
      <form @submit.prevent="submitTransfer" class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-1">From</label>
          <UiSelect v-model="transferForm.from_location_id" :options="locations.map(l => ({ label: l.name, value: l.id }))" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">To</label>
          <UiSelect v-model="transferForm.to_location_id" :options="locations.map(l => ({ label: l.name, value: l.id }))" />
          <p v-if="transferForm.errors.to_location_id" class="text-xs text-danger mt-1">{{ transferForm.errors.to_location_id }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Quantity</label>
          <UiInput v-model="transferForm.quantity" type="number" min="1" />
          <p v-if="transferForm.errors.quantity" class="text-xs text-danger mt-1">{{ transferForm.errors.quantity }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Notes</label>
          <UiInput v-model="transferForm.notes" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="transferOpen = false">Cancel</UiButton>
          <UiButton type="submit" :disabled="transferForm.processing">Transfer</UiButton>
        </div>
      </form>
    </UiDialog>

    <UiDialog v-model="adjustOpen" title="Adjust stock">
      <form @submit.prevent="submitAdjust" class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-1">Location</label>
          <UiSelect v-model="adjustForm.location_id" :options="locations.map(l => ({ label: l.name, value: l.id }))" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">New absolute quantity</label>
          <UiInput v-model="adjustForm.quantity" type="number" min="0" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Reason *</label>
          <UiInput v-model="adjustForm.reason" required />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="adjustOpen = false">Cancel</UiButton>
          <UiButton type="submit" :disabled="adjustForm.processing">Adjust</UiButton>
        </div>
      </form>
    </UiDialog>
  </AuthenticatedLayout>
</template>
