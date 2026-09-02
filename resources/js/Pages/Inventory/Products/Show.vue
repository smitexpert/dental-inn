<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import Alert from '@/Components/ui/Alert.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Icon from '@/Components/ui/Icon.vue'
import Dialog from '@/Components/ui/Dialog.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'

const props = defineProps({
  product: Object,
  recent_movements: Array,
  locations: Array,
  suppliers: Array,
})

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
  purchaseForm.post(`/inventory/products/${props.product.id}/purchase`, {
    onSuccess: () => (purchaseOpen.value = false),
  })
}
function submitConsume() {
  consumeForm.post(`/inventory/products/${props.product.id}/consume`, {
    onSuccess: () => (consumeOpen.value = false),
  })
}
function submitTransfer() {
  transferForm.post(`/inventory/products/${props.product.id}/transfer`, {
    onSuccess: () => (transferOpen.value = false),
  })
}
function submitAdjust() {
  adjustForm.post(`/inventory/products/${props.product.id}/adjust`, {
    onSuccess: () => (adjustOpen.value = false),
  })
}

const supplierOptions = computed(() => [
  { label: '— None —', value: '' },
  ...props.suppliers.map((s) => ({ label: s.name, value: s.id })),
])

const locationOptions = computed(() =>
  props.locations.map((l) => ({ label: l.name, value: l.id })),
)

const breadcrumbs = computed(() => [
  { label: 'Products', href: '/inventory/products' },
  { label: props.product.name },
])
</script>

<template>
  <Head :title="product.name" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-6xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="product.name"
        :subtitle="`SKU ${product.sku}${product.barcode ? ' · Barcode ' + product.barcode : ''}`"
        icon="package"
        :eyebrow="`Product · ${product.category_label}`"
      >
        <template #actions>
          <Badge :variant="product.is_active ? 'success' : 'warning'" dot>
            {{ product.is_active ? 'Active' : 'Inactive' }}
          </Badge>
          <Link :href="`/inventory/products/${product.id}/edit`">
            <Button variant="outline" icon="edit">Edit</Button>
          </Link>
        </template>
      </PageHeader>

      <Alert
        v-if="product.is_low"
        variant="warning"
        title="Stock below reorder level"
        :description="`Current total is ${product.total_quantity} ${product.unit_short}. Reorder level is ${product.reorder_level}.`"
      />

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <Card title="Overview">
          <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div>
              <dt class="text-(--color-muted)">Total stock</dt>
              <dd class="mt-0.5">
                <Badge :variant="product.is_low ? 'danger' : 'success'">
                  {{ product.total_quantity }} {{ product.unit_short }}
                </Badge>
              </dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Reorder level</dt>
              <dd class="font-medium mt-0.5">{{ product.reorder_level }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Reorder qty</dt>
              <dd class="font-medium mt-0.5">{{ product.reorder_quantity }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Track stock</dt>
              <dd class="font-medium mt-0.5">{{ product.track_stock ? 'Yes' : 'No' }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Unit</dt>
              <dd class="font-medium mt-0.5">
                {{ product.unit_label }}
                <span class="text-(--color-muted)">({{ product.unit_short }})</span>
              </dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Category</dt>
              <dd class="font-medium mt-0.5">{{ product.category_label }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Cost price</dt>
              <dd class="font-medium mt-0.5">{{ product.cost_price ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Selling price</dt>
              <dd class="font-medium mt-0.5">{{ product.selling_price ?? '—' }}</dd>
            </div>
          </dl>
          <p v-if="product.description" class="text-sm text-(--color-muted) mt-4 whitespace-pre-line">
            {{ product.description }}
          </p>
          <div class="mt-5 flex flex-wrap gap-2">
            <Button size="sm" icon="plus" @click="purchaseOpen = true">Record purchase</Button>
            <Button size="sm" variant="secondary" icon="minus" @click="consumeOpen = true">Consume</Button>
            <Button size="sm" variant="secondary" icon="arrow-right" @click="transferOpen = true">Transfer</Button>
            <Button size="sm" variant="ghost" icon="edit" @click="adjustOpen = true">Adjust</Button>
            <Link :href="`/inventory/products/${product.id}/movements`" class="ml-auto">
              <Button size="sm" variant="ghost" icon-right="arrow-right">Full history</Button>
            </Link>
          </div>
        </Card>

        <Card title="Stock per location" :padded="false">
          <div v-if="product.stock_levels.length === 0">
            <EmptyState
              icon="building"
              title="No stock recorded"
              description="Record a purchase or transfer to populate this product at a location."
            />
          </div>
          <div v-else class="overflow-x-auto">
            <table class="app-table">
              <thead>
                <tr>
                  <th>Location</th>
                  <th class="text-right">Quantity</th>
                  <th class="text-right">Last counted</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="l in product.stock_levels" :key="l.id">
                  <td class="text-base-content">{{ l.location_name }}</td>
                  <td class="text-right font-medium">{{ l.quantity }}</td>
                  <td class="text-right text-(--color-muted) text-xs">{{ l.last_counted_at || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </Card>
      </div>

      <Card title="Recent movements" :padded="false">
        <EmptyState
          v-if="recent_movements.length === 0"
          icon="refresh"
          title="No movements yet"
          description="Stock activity will appear here as soon as the product moves."
        />
        <ul v-else class="divide-y divide-(--color-line)">
          <li v-for="m in recent_movements" :key="m.id" class="flex items-start justify-between gap-3 px-5 py-3">
            <div class="min-w-0">
              <Badge :variant="m.quantity >= 0 ? 'success' : 'danger'">
                {{ m.type_label }}
              </Badge>
              <p class="text-(--color-muted) text-xs mt-1">
                {{ m.location_name || '—' }} · {{ m.performer_name || '—' }}
              </p>
              <p v-if="m.reason" class="text-(--color-muted) text-xs">{{ m.reason }}</p>
            </div>
            <div class="text-right shrink-0">
              <p :class="['font-mono font-medium', m.quantity >= 0 ? 'text-success' : 'text-error']">
                {{ m.quantity >= 0 ? '+' : '' }}{{ m.quantity }}
              </p>
              <p class="text-xs text-(--color-muted)">{{ m.created_at }}</p>
            </div>
          </li>
        </ul>
      </Card>

      <Card title="Suppliers" :padded="false">
        <EmptyState
          v-if="product.suppliers.length === 0"
          icon="send"
          title="No suppliers linked"
          description="Link suppliers to track cost, lead time and preferred ordering."
        />
        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Supplier</th>
                <th>Their SKU</th>
                <th class="text-right">Unit cost</th>
                <th class="text-right">Lead time</th>
                <th>Preferred</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in product.suppliers" :key="s.id">
                <td>
                  <Link :href="`/inventory/suppliers/${s.id}`" class="font-medium text-base-content hover:text-primary transition-colors">
                    {{ s.name }}
                  </Link>
                </td>
                <td class="font-mono text-xs text-(--color-muted)">{{ s.supplier_sku || '—' }}</td>
                <td class="text-right">{{ s.unit_cost || '—' }}</td>
                <td class="text-right">{{ s.lead_time_days ? `${s.lead_time_days} d` : '—' }}</td>
                <td>
                  <Badge :variant="s.is_preferred ? 'primary' : 'neutral'">
                    {{ s.is_preferred ? 'Preferred' : 'Alternate' }}
                  </Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <div class="flex justify-end">
        <Link href="/inventory/products">
          <Button variant="ghost" icon="arrow-left">Back to products</Button>
        </Link>
      </div>
    </div>

    <Dialog v-model="purchaseOpen" title="Record purchase" icon="plus" size="md">
      <form @submit.prevent="submitPurchase" class="space-y-4">
        <Field label="Supplier">
          <Select v-model="purchaseForm.supplier_id" :options="supplierOptions" placeholder="Select supplier" />
        </Field>
        <Field label="Location" required>
          <Select v-model="purchaseForm.location_id" :options="locationOptions" placeholder="Select location" />
        </Field>
        <Field label="Quantity" required :error="purchaseForm.errors.quantity">
          <Input v-model="purchaseForm.quantity" type="number" min="1" />
        </Field>
        <Field label="Unit cost">
          <Input v-model="purchaseForm.unit_cost" type="number" step="0.01" min="0" placeholder="0.00" />
        </Field>
        <Field label="Reason / note">
          <Input v-model="purchaseForm.reason" placeholder="Optional" />
        </Field>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="ghost" @click="purchaseOpen = false">Cancel</Button>
          <Button type="submit" :loading="purchaseForm.processing">Record</Button>
        </div>
      </form>
    </Dialog>

    <Dialog v-model="consumeOpen" title="Consume stock" icon="minus" size="md">
      <form @submit.prevent="submitConsume" class="space-y-4">
        <Field label="Location" required>
          <Select v-model="consumeForm.location_id" :options="locationOptions" />
        </Field>
        <Field label="Quantity" required :error="consumeForm.errors.quantity">
          <Input v-model="consumeForm.quantity" type="number" min="1" />
        </Field>
        <Field label="Reason">
          <Input v-model="consumeForm.reason" placeholder="Optional" />
        </Field>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="ghost" @click="consumeOpen = false">Cancel</Button>
          <Button type="submit" :loading="consumeForm.processing">Consume</Button>
        </div>
      </form>
    </Dialog>

    <Dialog v-model="transferOpen" title="Transfer stock" icon="arrow-right" size="md">
      <form @submit.prevent="submitTransfer" class="space-y-4">
        <Field label="From" required>
          <Select v-model="transferForm.from_location_id" :options="locationOptions" />
        </Field>
        <Field label="To" required :error="transferForm.errors.to_location_id">
          <Select v-model="transferForm.to_location_id" :options="locationOptions" />
        </Field>
        <Field label="Quantity" required :error="transferForm.errors.quantity">
          <Input v-model="transferForm.quantity" type="number" min="1" />
        </Field>
        <Field label="Notes">
          <Input v-model="transferForm.notes" placeholder="Optional" />
        </Field>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="ghost" @click="transferOpen = false">Cancel</Button>
          <Button type="submit" :loading="transferForm.processing">Transfer</Button>
        </div>
      </form>
    </Dialog>

    <Dialog v-model="adjustOpen" title="Adjust stock" icon="edit" size="md">
      <form @submit.prevent="submitAdjust" class="space-y-4">
        <Field label="Location" required>
          <Select v-model="adjustForm.location_id" :options="locationOptions" />
        </Field>
        <Field label="New absolute quantity" required>
          <Input v-model="adjustForm.quantity" type="number" min="0" />
        </Field>
        <Field label="Reason" required>
          <Input v-model="adjustForm.reason" placeholder="Why?" />
        </Field>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="ghost" @click="adjustOpen = false">Cancel</Button>
          <Button type="submit" :loading="adjustForm.processing">Adjust</Button>
        </div>
      </form>
    </Dialog>
  </AuthenticatedLayout>
</template>
