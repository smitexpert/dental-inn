<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import { ref, watch } from 'vue'

const props = defineProps({ logs: Object, channels: Array, statuses: Array, filters: Object })

const channel = ref(props.filters.channel ?? null)
const status = ref(props.filters.status ?? null)
const recipient = ref(props.filters.recipient ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/notifications/logs',
      { channel: channel.value, status: status.value, recipient: recipient.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([channel, status, recipient], applyFilters)

function fmt(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString()
}

function variantFor(s) {
  return { sent: 'success', failed: 'danger', skipped: 'warning' }[s] || 'secondary'
}
</script>

<template>
  <Head title="Notification logs" />
  <AuthenticatedLayout>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div class="flex gap-2">
          <Link href="/notifications/templates"><UiButton variant="secondary">Templates</UiButton></Link>
          <Link href="/notifications/logs"><UiButton variant="secondary">Logs</UiButton></Link>
          <Link href="/notifications/send"><UiButton variant="secondary">Manual send</UiButton></Link>
        </div>
      </div>
      <UiCard title="Notification logs">
        <div class="grid grid-cols-3 gap-3 mb-4">
          <UiSelect
            v-model="channel"
            :options="[{ label: 'All channels', value: null }, ...channels.map((c) => ({ label: c.label, value: c.value }))]"
          />
          <UiSelect
            v-model="status"
            :options="[{ label: 'All statuses', value: null }, ...statuses.map((s) => ({ label: s.label, value: s.value }))]"
          />
          <UiInput v-model="recipient" placeholder="Recipient contains…" />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-base-200 text-base-content/70">
              <tr>
                <th class="text-left px-4 py-2">When</th>
                <th class="text-left px-4 py-2">Channel</th>
                <th class="text-left px-4 py-2">Recipient</th>
                <th class="text-left px-4 py-2">Subject</th>
                <th class="text-left px-4 py-2">Status</th>
                <th class="text-left px-4 py-2">Template</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="l in logs.data"
                :key="l.id"
                class="border-b border-base-300 last:border-b-0"
              >
                <td class="px-4 py-2 text-base-content">{{ fmt(l.created_at) }}</td>
                <td class="px-4 py-2"><UiBadge variant="secondary">{{ l.channel }}</UiBadge></td>
                <td class="px-4 py-2 text-base-content font-mono text-xs">{{ l.recipient }}</td>
                <td class="px-4 py-2 text-base-content/70">{{ l.subject || '—' }}</td>
                <td class="px-4 py-2"><UiBadge :variant="variantFor(l.status)">{{ l.status }}</UiBadge></td>
                <td class="px-4 py-2 text-base-content/70">{{ l.template?.key || '—' }}</td>
              </tr>
              <tr v-if="logs.data.length === 0">
                <td colspan="6" class="text-center text-base-content/70 py-6">No logs.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
