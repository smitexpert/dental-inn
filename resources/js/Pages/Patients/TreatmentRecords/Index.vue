<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'
import { useCurrentUser } from '@/Composables/useCurrentUser.js'

const props = defineProps({
  patient: Object,
  records: Object,
})

const { can } = useCurrentUser()

function fmtDate(s) {
  if (!s) return '—'
  return new Date(s).toLocaleDateString()
}
</script>

<template>
  <Head :title="`Treatment records — ${patient.name}`" />
  <AuthenticatedLayout>
    <div class="space-y-4 max-w-5xl">
      <div class="flex items-center justify-between">
        <div>
          <Link href="/patients" class="text-sm text-base-content/70 hover:text-primary">Patients</Link>
          <h2 class="text-xl font-semibold text-base-content mt-1">
            Treatment records — {{ patient.name }}
          </h2>
          <p class="text-sm text-base-content/70 font-mono">{{ patient.patient_code }}</p>
        </div>
        <div class="flex gap-2">
          <Link :href="`/patients/${patient.id}`"><UiButton variant="secondary">Back to profile</UiButton></Link>
          <Link v-if="can('manage-treatment-records')" :href="`/patients/${patient.id}/treatment-records/create`">
            <UiButton>New treatment record</UiButton>
          </Link>
        </div>
      </div>

      <UiCard title="Records">
        <div v-if="records.data.length === 0" class="text-sm text-base-content/70 py-6 text-center">
          No treatment records for this patient yet.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-base-200 text-base-content/70">
              <tr>
                <th class="text-left px-4 py-2">Date</th>
                <th class="text-left px-4 py-2">Chief complaint</th>
                <th class="text-left px-4 py-2">Doctor</th>
                <th class="text-left px-4 py-2">Status</th>
                <th class="text-right px-4 py-2">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in records.data" :key="r.id" class="border-b border-base-300 last:border-b-0">
                <td class="px-4 py-2">{{ fmtDate(r.visited_at) }}</td>
                <td class="px-4 py-2 text-base-content font-medium">{{ r.chief_complaint || '—' }}</td>
                <td class="px-4 py-2 text-base-content/70">{{ r.doctor_name || '—' }}</td>
                <td class="px-4 py-2">
                  <UiBadge :variant="r.status === 'completed' ? 'success' : r.status === 'cancelled' ? 'danger' : 'warning'">
                    {{ r.status_label }}
                  </UiBadge>
                </td>
                <td class="px-4 py-2 text-right">
                  <Link
                    :href="`/patients/${patient.id}/treatment-records/${r.id}`"
                    class="text-primary hover:text-primary text-sm"
                    >View</Link
                  >
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div
          v-if="records.last_page > 1"
          class="mt-3 flex items-center justify-between text-sm text-base-content/70"
        >
          <span>Page {{ records.current_page }} of {{ records.last_page }}</span>
          <div class="flex gap-1">
            <template v-for="link in records.links" :key="link.label">
              <Link
                v-if="link.url"
                :href="link.url"
                class="px-2 py-1 rounded hover:bg-base-200"
                ><!-- eslint-disable-next-line vue/no-v-text-v-html-on-component --><span v-html="link.label" /></Link>
            </template>
          </div>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>