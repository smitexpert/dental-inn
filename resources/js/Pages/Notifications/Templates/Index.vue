<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import { ref, watch } from 'vue'

const props = defineProps({ templates: Object, channels: Array, filters: Object })

const q = ref(props.filters.q ?? '')
const channel = ref(props.filters.channel ?? null)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/notifications/templates',
      { q: q.value, channel: channel.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, channel], applyFilters)

function destroy(id) {
  if (!confirm('Delete this template?')) return
  router.delete(`/notifications/templates/${id}`)
}
</script>

<template>
  <Head title="Notification templates" />
  <AuthenticatedLayout>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div class="flex gap-2">
          <Link href="/notifications/templates"><UiButton variant="secondary">Templates</UiButton></Link>
          <Link href="/notifications/logs"><UiButton variant="secondary">Logs</UiButton></Link>
          <Link href="/notifications/send"><UiButton variant="secondary">Manual send</UiButton></Link>
        </div>
        <Link href="/notifications/templates/create"><UiButton>New template</UiButton></Link>
      </div>
      <UiCard title="Notification templates">
        <div class="grid grid-cols-2 gap-3 mb-4">
          <UiInput v-model="q" placeholder="Search key" />
          <UiSelect
            v-model="channel"
            :options="[{ label: 'All channels', value: null }, ...channels.map((c) => ({ label: c.label, value: c.value }))]"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="app-table">
            <thead class="bg-base-200 text-base-content/70">
              <tr>
                <th class="text-left px-4 py-2">Key</th>
                <th class="text-left px-4 py-2">Channel</th>
                <th class="text-left px-4 py-2">Subject</th>
                <th class="text-left px-4 py-2">Status</th>
                <th class="text-right px-4 py-2"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="t in templates.data"
                :key="t.id"
                class="border-b border-base-300 last:border-b-0"
              >
                <td class="px-4 py-2 text-base-content font-mono">{{ t.key }}</td>
                <td class="px-4 py-2">
                  <UiBadge variant="neutral">{{ t.channel }}</UiBadge>
                </td>
                <td class="px-4 py-2 text-base-content/70">{{ t.subject || '—' }}</td>
                <td class="px-4 py-2">
                  <UiBadge :variant="t.is_active ? 'success' : 'neutral'">
                    {{ t.is_active ? 'Active' : 'Inactive' }}
                  </UiBadge>
                </td>
                <td class="px-4 py-2 text-right space-x-2">
                  <Link :href="`/notifications/templates/${t.id}/edit`" class="text-primary hover:text-primary text-sm">Edit</Link>
                  <button type="button" class="text-error hover:text-error/80 text-sm" @click="destroy(t.id)">Delete</button>
                </td>
              </tr>
              <tr v-if="templates.data.length === 0">
                <td colspan="5" class="text-center text-base-content/70 py-6">No templates.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
