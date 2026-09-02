<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Select from '@/Components/ui/Select.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({ doctors: Array, buffer_minutes: Number })

const view = ref('week') // 'week' | 'day'
const doctorFilter = ref(null)
const currentDate = ref(new Date())

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

const todayDate = () => {
  const d = new Date()
  d.setHours(0, 0, 0, 0)
  return d
}

const isSameDay = (a, b) => {
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  )
}

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

const rangeStart = computed(() => (view.value === 'week' ? weekStart.value : dayDate.value))
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
function jumpToToday() {
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

function statusVariant(s) {
  return {
    scheduled: 'info',
    confirmed: 'primary',
    checked_in: 'warning',
    completed: 'success',
    cancelled: 'danger',
    no_show: 'danger',
  }[s] || 'neutral'
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
    <div class="space-y-5">
      <PageHeader
        title="Appointments"
        subtitle="Browse visits by week or day, filter by doctor."
        icon="calendar-clock"
        eyebrow="Schedule"
      >
        <template #actions>
          <Link href="/appointments">
            <Button variant="outline" icon="list">List</Button>
          </Link>
          <Link href="/appointments/create">
            <Button icon="plus">New appointment</Button>
          </Link>
        </template>
      </PageHeader>

      <Card>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex items-center gap-2 flex-wrap">
            <Button variant="outline" icon-only icon="chevron-left" @click="prev" aria-label="Previous" />
            <Button variant="outline" size="sm" @click="jumpToToday">Today</Button>
            <Button variant="outline" icon-only icon="chevron-right" @click="next" aria-label="Next" />
            <h2 class="text-base font-semibold text-base-content ml-2">
              {{ rangeLabel() }}
            </h2>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <Select
              v-model="doctorFilter"
              :options="[{ label: 'All doctors', value: null }, ...doctors.map((d) => ({ label: d.name, value: d.id }))]"
            />
            <Select
              v-model="view"
              :options="[{ label: 'Week', value: 'week' }, { label: 'Day', value: 'day' }]"
            />
          </div>
        </div>

        <div v-if="view === 'week'" class="mt-5 grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-2">
          <div
            v-for="d in weekDays"
            :key="d.toISOString()"
            class="rounded-lg border border-(--color-line) bg-base-100 min-h-36 p-2.5 flex flex-col transition-colors"
            :class="isSameDay(d, todayDate()) ? 'border-primary/40 bg-primary/[0.03]' : ''"
          >
            <div class="flex items-center justify-between">
              <div>
                <div class="text-[10px] font-semibold uppercase tracking-wider text-(--color-subtle)">
                  {{ dayNames[d.getDay()] }}
                </div>
                <div
                  class="text-lg font-semibold leading-tight"
                  :class="isSameDay(d, todayDate()) ? 'text-primary' : 'text-base-content'"
                >
                  {{ d.getDate() }}
                </div>
              </div>
              <Badge v-if="apptsFor(d).length > 0" variant="neutral" size="sm">
                {{ apptsFor(d).length }}
              </Badge>
            </div>
            <div class="mt-2 space-y-1.5 flex-1">
              <Link
                v-for="a in apptsFor(d)"
                :key="a.id"
                :href="`/appointments/${a.id}`"
                class="block text-xs p-1.5 rounded-md border-l-2 transition-all duration-150 hover:bg-base-200"
                :style="{ borderColor: a.doctor_color }"
              >
                <div class="flex justify-between gap-1">
                  <span class="font-medium text-base-content">{{ timeLabel(a.scheduled_at) }}</span>
                  <span class="text-muted">{{ a.duration_minutes }}m</span>
                </div>
                <div class="text-base-content truncate font-medium mt-0.5">{{ a.patient_name }}</div>
                <div class="text-muted truncate">{{ a.doctor_name }}</div>
              </Link>
              <p v-if="apptsFor(d).length === 0" class="text-[11px] text-(--color-subtle) text-center py-2">
                —
              </p>
            </div>
          </div>
        </div>

        <div v-else class="mt-5">
          <div v-if="apptsFor(dayDate).length === 0">
            <EmptyState
              icon="calendar"
              title="No appointments today"
              description="Schedule one to fill this day."
            >
              <template #action>
                <Link href="/appointments/create">
                  <Button icon="plus">New appointment</Button>
                </Link>
              </template>
            </EmptyState>
          </div>
          <div v-else class="space-y-2">
            <Link
              v-for="a in apptsFor(dayDate)"
              :key="a.id"
              :href="`/appointments/${a.id}`"
              class="block rounded-lg border border-(--color-line) p-3 bg-base-100 transition-all duration-150 hover:shadow-(--shadow-card) hover:border-primary/40"
              :style="{ borderLeftWidth: '4px', borderLeftColor: a.doctor_color }"
            >
              <div class="flex items-center justify-between gap-2">
                <div class="font-medium text-base-content">
                  {{ timeLabel(a.scheduled_at) }} · {{ a.duration_minutes }} min
                </div>
                <Badge :variant="statusVariant(a.status)" dot>{{ a.status_label }}</Badge>
              </div>
              <div class="text-sm text-base-content mt-1">{{ a.patient_name }}</div>
              <div class="text-xs text-muted mt-0.5 flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5">
                  <span
                    class="h-2 w-2 rounded-full"
                    :style="{ backgroundColor: a.doctor_color }"
                  />
                  {{ a.doctor_name }}
                </span>
                <span v-if="a.patient_phone">·</span>
                <span v-if="a.patient_phone">{{ a.patient_phone }}</span>
              </div>
            </Link>
          </div>
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
