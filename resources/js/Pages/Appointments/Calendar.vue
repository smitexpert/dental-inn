<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({ doctors: Array, buffer_minutes: Number })

const view = ref('week') // 'week' | 'day'
const doctorFilter = ref(null)
const currentDate = ref(new Date())

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']

const weekStart = computed(() => {
  const d = new Date(currentDate.value)
  d.setHours(0, 0, 0, 0)
  d.setDate(d.getDate() - d.getDay())
  return d
})

const weekDays = computed(() => {
  const days = []
  for (let i = 0; i < 7; i++) {
    const d = new Date(weekStart.value)
    d.setDate(d.getDate() + i)
    days.push(d)
  }
  return days
})

const dayDate = computed(() => {
  const d = new Date(currentDate.value)
  d.setHours(0, 0, 0, 0)
  return d
})

const rangeStart = computed(() => view.value === 'week' ? weekStart.value : dayDate.value)
const rangeEnd = computed(() => {
  const end = new Date(rangeStart.value)
  end.setDate(end.getDate() + (view.value === 'week' ? 7 : 1))
  return end
})

const appointments = ref([])
const loading = ref(false)

async function load() {
  loading.value = true
  const params = new URLSearchParams()
  params.set('from', rangeStart.value.toISOString().slice(0, 10))
  params.set('to', rangeEnd.value.toISOString().slice(0, 10))
  if (doctorFilter.value) params.set('doctor_id', doctorFilter.value)
  const url = `/appointments/api/list?${params.toString()}`
  try {
    const res = await fetch(url, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    appointments.value = data.data || []
  } catch (e) {
    appointments.value = []
  }
  loading.value = false
}

onMounted(load)
watch([rangeStart, rangeEnd, doctorFilter], load)

function prev() {
  const d = new Date(currentDate.value)
  d.setDate(d.getDate() - (view.value === 'week' ? 7 : 1))
  currentDate.value = d
}
function next() {
  const d = new Date(currentDate.value)
  d.setDate(d.getDate() + (view.value === 'week' ? 7 : 1))
  currentDate.value = d
}
function today() {
  currentDate.value = new Date()
}

const appointmentsByDate = computed(() => {
  const map = new Map()
  for (const a of appointments.value) {
    const d = new Date(a.scheduled_at)
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    if (!map.has(key)) map.set(key, [])
    map.get(key).push(a)
  }
  return map
})

function apptsFor(d) {
  const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  return appointmentsByDate.value.get(key) || []
}

function timeLabel(iso) {
  return new Date(iso).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
}

function rangeLabel() {
  if (view.value === 'day') {
    return `${dayNames[dayDate.value.getDay()]}, ${monthNames[dayDate.value.getMonth()]} ${dayDate.value.getDate()}, ${dayDate.value.getFullYear()}`
  }
  const start = weekStart.value
  const end = new Date(start)
  end.setDate(end.getDate() + 6)
  return `${monthNames[start.getMonth()]} ${start.getDate()} – ${monthNames[end.getMonth()]} ${end.getDate()}, ${end.getFullYear()}`
}
</script>

<template>
  <Head title="Calendar" />
  <AuthenticatedLayout>
    <div class="space-y-4">
      <div class="flex items-center justify-between gap-2">
        <div class="flex gap-2">
          <Link href="/appointments"><UiButton variant="secondary">List</UiButton></Link>
          <Link href="/appointments/calendar"><UiButton variant="primary">Calendar</UiButton></Link>
        </div>
        <Link href="/appointments/create"><UiButton>New appointment</UiButton></Link>
      </div>
      <UiCard>
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <UiButton variant="secondary" @click="prev">‹</UiButton>
            <UiButton variant="secondary" @click="today">Today</UiButton>
            <UiButton variant="secondary" @click="next">›</UiButton>
            <span class="text-base font-medium text-foreground ml-3">{{ rangeLabel() }}</span>
          </div>
          <div class="flex items-center gap-2">
            <UiSelect
              v-model="doctorFilter"
              :options="[{ label: 'All doctors', value: null }, ...doctors.map((d) => ({ label: d.name, value: d.id }))]"
            />
            <UiSelect
              v-model="view"
              :options="[{ label: 'Week', value: 'week' }, { label: 'Day', value: 'day' }]"
            />
          </div>
        </div>

        <div v-if="view === 'week'" class="mt-4 grid grid-cols-7 gap-2">
          <div v-for="d in weekDays" :key="d.toISOString()" class="border border-border rounded-md bg-surface min-h-32 p-2">
            <div class="text-xs font-medium text-muted-foreground">{{ dayNames[d.getDay()] }}</div>
            <div class="text-lg font-semibold text-foreground">{{ d.getDate() }}</div>
            <div class="mt-2 space-y-1">
              <Link
                v-for="a in apptsFor(d)"
                :key="a.id"
                :href="`/appointments/${a.id}`"
                class="block text-xs p-1 rounded border-l-2 hover:bg-surface-muted"
                :style="{ borderColor: a.doctor_color }"
              >
                <div class="flex justify-between">
                  <span class="text-foreground">{{ timeLabel(a.scheduled_at) }}</span>
                  <span class="text-muted-foreground">{{ a.duration_minutes }}m</span>
                </div>
                <div class="text-foreground truncate">{{ a.patient_name }}</div>
                <div class="text-muted-foreground truncate">{{ a.doctor_name }}</div>
              </Link>
            </div>
          </div>
        </div>

        <div v-else class="mt-4">
          <div class="space-y-2">
            <Link
              v-for="a in apptsFor(dayDate)"
              :key="a.id"
              :href="`/appointments/${a.id}`"
              class="block border border-border rounded-md p-3 bg-surface hover:bg-surface-muted"
              :style="{ borderLeftWidth: '4px', borderLeftColor: a.doctor_color }"
            >
              <div class="flex items-center justify-between">
                <div class="font-medium text-foreground">{{ timeLabel(a.scheduled_at) }} ({{ a.duration_minutes }} min)</div>
                <UiBadge variant="secondary">{{ a.status_label }}</UiBadge>
              </div>
              <div class="text-sm text-foreground mt-1">{{ a.patient_name }} · {{ a.patient_phone }}</div>
              <div class="text-xs text-muted-foreground">{{ a.doctor_name }}</div>
            </Link>
            <div v-if="apptsFor(dayDate).length === 0" class="text-center text-muted-foreground py-6">
              No appointments on this day.
            </div>
          </div>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
