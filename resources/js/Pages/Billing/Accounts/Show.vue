<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'

const props = defineProps({ account: Object })
</script>

<template>
  <Head :title="`Account ${account.code}`" />
  <AuthenticatedLayout>
    <UiCard>
      <template #header>
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-base-content font-mono">{{ account.code }} — {{ account.name }}</h3>
            <p class="text-xs text-base-content/70 mt-1">{{ account.type_label }}</p>
          </div>
          <div class="flex items-center gap-2">
            <UiBadge :variant="account.is_active ? 'success' : 'neutral'">
              {{ account.is_active ? 'Active' : 'Inactive' }}
            </UiBadge>
            <Link :href="`/billing/accounts/${account.id}/edit`"><UiButton variant="secondary">Edit</UiButton></Link>
            <Link href="/billing/accounts"><UiButton variant="ghost">Back</UiButton></Link>
          </div>
        </div>
      </template>

      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-base-content/70 text-xs">Code</div>
          <div class="text-base-content font-mono">{{ account.code }}</div>
        </div>
        <div>
          <div class="text-base-content/70 text-xs">Type</div>
          <div class="text-base-content">{{ account.type_label }}</div>
        </div>
        <div>
          <div class="text-base-content/70 text-xs">Parent account</div>
          <div class="text-base-content">{{ account.parent_code ?? '—' }}</div>
        </div>
        <div>
          <div class="text-base-content/70 text-xs">Name</div>
          <div class="text-base-content">{{ account.name }}</div>
        </div>
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
