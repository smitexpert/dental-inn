<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import Stat from '@/Components/ui/Stat.vue'

const props = defineProps({ account: Object })

const account = props.account

function typeVariant(t) {
  const m = { asset: 'success', liability: 'danger', equity: 'info', revenue: 'success', expense: 'warning' }
  return m[t] ?? 'neutral'
}

const breadcrumbs = [
  { label: 'Chart of accounts', href: '/billing/accounts' },
  { label: `${account.code} — ${account.name}` },
]
</script>

<template>
  <Head :title="`Account ${account.code}`" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-4xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="`${account.code} — ${account.name}`"
        :subtitle="account.type_label"
        icon="building"
        eyebrow="Account"
      >
        <template #actions>
          <Badge :variant="account.is_active ? 'success' : 'warning'" dot>
            {{ account.is_active ? 'Active' : 'Inactive' }}
          </Badge>
          <Link :href="`/billing/accounts/${account.id}/edit`">
            <Button variant="outline" icon="edit">Edit</Button>
          </Link>
          <Link href="/billing/accounts">
            <Button variant="ghost">Back</Button>
          </Link>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Stat
          label="Code"
          :value="account.code"
          icon="hash"
          tone="primary"
        />
        <Stat
          label="Type"
          :value="account.type_label"
          icon="wallet"
          :tone="typeVariant(account.type)"
        />
        <Stat
          label="Parent account"
          :value="account.parent_code ?? '—'"
          icon="building"
          tone="neutral"
        />
      </div>

      <Card title="Account details">
        <dl class="grid grid-cols-2 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
          <div>
            <dt class="text-muted">Code</dt>
            <dd class="font-medium mt-0.5 font-mono">{{ account.code }}</dd>
          </div>
          <div>
            <dt class="text-muted">Name</dt>
            <dd class="font-medium mt-0.5">{{ account.name }}</dd>
          </div>
          <div>
            <dt class="text-muted">Type</dt>
            <dd class="font-medium mt-0.5">{{ account.type_label }}</dd>
          </div>
          <div>
            <dt class="text-muted">Parent</dt>
            <dd class="font-medium mt-0.5">
              <Link
                v-if="account.parent_code"
                :href="`/billing/accounts/${account.parent_id}`"
                class="text-primary hover:underline font-mono"
              >
                {{ account.parent_code }}
              </Link>
              <span v-else>—</span>
            </dd>
          </div>
        </dl>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
