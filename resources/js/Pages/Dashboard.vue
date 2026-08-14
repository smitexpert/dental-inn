<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiStat from '@/Components/ui/Stat.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import { useCurrentUser } from '@/Composables/useCurrentUser.js'

const props = defineProps({ stats: { type: Object, required: true } })
const { user } = useCurrentUser()
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <div class="max-w-7xl space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">
            Welcome back, {{ user?.name?.split(' ')[0] }}
          </h1>
          <p class="text-sm text-muted-foreground mt-1">
            Foundation phase — KPI cards below are placeholders. Real numbers arrive in the upcoming
            sub-projects.
          </p>
        </div>
        <UiBadge variant="primary">Foundation</UiBadge>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <UiStat
          v-for="(stat, key) in stats"
          :key="key"
          :label="stat.label"
          :value="stat.value"
          :hint="stat.hint"
        />
      </div>

      <UiCard title="What ships next">
        <ol class="list-decimal list-inside space-y-1 text-sm text-foreground">
          <li><strong>Doctors &amp; Appointments</strong> — doctor module, working hours, patient booking</li>
          <li><strong>Patients &amp; Treatment Records</strong> — patient CRUD and visit history</li>
          <li><strong>Inventory</strong> — products, stock in/out, low-stock alerts</li>
          <li>
            <strong>Billing, Payments &amp; Ledger</strong> — invoices, payments, partial payments, ledger
            book, expenses
          </li>
          <li><strong>Money Exchange</strong> — multi-currency cash drawer</li>
          <li><strong>Notifications</strong> — SMS / WhatsApp confirmations and reminders</li>
        </ol>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
