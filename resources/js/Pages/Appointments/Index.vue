<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import { ref, watch, computed } from 'vue'

const props = defineProps({ appointments: Object, doctors: Array, statuses: Array, filters: Object })

const q = ref(props.filters.q ?? '')
const doctorId = ref(props.filters.doctor_id ?? null)
const status = ref(props.filters.status ?? null)
const from = ref(props.filters.from ?? '')
const to = ref(props.filters.to ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/appointments',
      { q: q.value, doctor_id: doctorId.value, status: status.value, from: from.value, to: to.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, doctorId, status, from, to], applyFilters)

const statusLabels = computed(() => {
  const map = {}
  props.statuses.forEach((s) => (map[s.value] = s.label))
  return map
})

function statusVariant(s) {
  return {
    scheduled: 'secondary',
    confirmed: 'primary',
    checked_in: 'warning',
    completed: 'success',
    cancelled: 'danger',
    no_show: 'danger',
  }[s] || 'secondary'
}

function fmt(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString()
}
</script>

<template>
  <Head title="Appointments" />
  <AuthenticatedLayout>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div class="flex gap-2">
          <Link href="/appointments"><UiButton variant="secondary">List</UiButton></Link>
          <Link href="/appointments/calendar"><UiButton variant="secondary">Calendar</UiButton></Link>
        </div>
        <Link href="/appointments/create"><UiButton>New appointment</UiButton></Link>
      </div>
      <UiCard title="Appointments">
        <div class="grid grid-cols-5 gap-3 mb-4">
          <UiInput v-model="q" placeholder="Search patient name or phone" />
          <UiSelect
            v-model="doctorId"
            :options="[{ label: 'All doctors', value: null }, ...doctors.map((d) => ({ label: d.name, value: d.id }))]"
          />
          <UiSelect
            v-model="status"
            :options="[{ label: 'All statuses', value: null }, ...statuses.map((s) => ({ label: s.label, value: s.value }))]"
          />
          <input v-model="from" type="date" class="h-10 rounded-md border border-border bg-surface px-3 text-sm" />
          <input v-model="to" type="date" class="h-10 rounded-md border border-border bg-surface px-3 text-sm" />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-surface-muted text-muted-foreground">
              <tr>
                <th class="text-left px-4 py-2">When</th>
                <th class="text-left px-4 py-2">Doctor</th>
                <th class="text-left px-4 py-2">Patient</th>
                <th class="text-left px-4 py-2">Duration</th>
                <th class="text-left px-4 py-2">Status</th>
                <th class="text-right px-4 py-2"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="a in appointments.data"
                :key="a.id"
                class="border-b border-border last:border-b-0"
              >
                <td class="px-4 py-2 text-foreground">{{ fmt(a.scheduled_at) }}</td>
                <td class="px-4 py-2 text-foreground">
                  <div class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: a.doctor_color }" />
                    {{ a.doctor_name }}
                  </div>
                </td>
                <td class="px-4 py-2 text-foreground">{{ a.patient_name }}</td>
                <td class="px-4 py-2 text-muted-foreground">{{ a.duration_minutes }} min</td>
                <td class="px-4 py-2">
                  <UiBadge :variant="statusVariant(a.status)">{{ statusLabels[a.status] || a.status }}</UiBadge>
                </td>
                <td class="px-4 py-2 text-right">
                  <Link :href="`/appointments/${a.id}`" class="text-primary-600 hover:text-primary-700 text-sm">View</Link>
                </td>
              </tr>
              <tr v-if="appointments.data.length === 0">
                <td colspan="6" class="text-center text-muted-foreground py-6">No appointments.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div
          v-if="appointments.last_page > 1"
          class="mt-3 flex items-center justify-between text-sm text-muted-foreground"
        >
          <span>Page {{ appointments.current_page }} of {{ appointments.last_page }}</span>
          <div class="flex gap-1">
            <template v-for="link in appointments.links" :key="link.label">
              <Link
                v-if="link.url"
                :href="link.url"
                class="px-2 py-1 rounded hover:bg-surface-muted"
                ><!-- eslint-disable-next-line vue/no-v-text-v-html-on-component --><span v-html="link.label" /></Link>
            </template>
          </div>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
