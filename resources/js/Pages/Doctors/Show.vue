<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/ui/Button.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'
import Icon from '@/Components/ui/Icon.vue'
import { useConfirm } from '@/Composables/useConfirm.js'

const props = defineProps({ doctor: Object })
const doctor = props.doctor

const confirm = useConfirm()
const editingSchedule = ref(false)
const editingTimeOff = ref(false)

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
const scheduleRows = ref(
  doctor.schedules && doctor.schedules.length > 0
    ? doctor.schedules.map((s) => ({ ...s }))
    : Array.from({ length: 7 }, (_, i) => ({
        day_of_week: i,
        start_time: '09:00',
        end_time: '17:00',
        is_active: i > 0 && i < 6,
      })),
)

const timeOff = ref({ start_at: '', end_at: '', reason: '' })

function saveSchedule() {
  router.patch(
    `/doctors/${doctor.id}/schedule`,
    { schedules: scheduleRows.value },
    {
      onSuccess: () => (editingSchedule.value = false),
    },
  )
}

function addTimeOff() {
  router.post(`/doctors/${doctor.id}/time-off`, timeOff.value, {
    onSuccess: () => {
      timeOff.value = { start_at: '', end_at: '', reason: '' }
      editingTimeOff.value = false
    },
  })
}

function removeTimeOff(id) {
  confirm.require({
    header: 'Remove time off?',
    message: 'This entry will be removed from the doctor’s schedule.',
    acceptLabel: 'Remove',
    acceptClass: 'btn-error',
    accept: () => router.delete(`/doctors/${doctor.id}/time-off/${id}`),
  })
}

function fmtDateTime(s) {
  if (!s) return '—'
  return new Date(s).toLocaleString()
}

const breadcrumbs = [
  { label: 'Doctors', href: '/doctors' },
  { label: doctor.name },
]
</script>

<template>
  <Head :title="doctor.name" />
  <AuthenticatedLayout>
    <div class="space-y-5 max-w-5xl">
      <Breadcrumbs :items="breadcrumbs" />

      <PageHeader
        :title="doctor.name"
        :subtitle="`${doctor.specialization || 'General dentistry'} · License ${doctor.license_number || '—'}`"
        icon="user-round"
        :eyebrow="`Doctor · ${doctor.is_active ? 'Active' : 'Inactive'}`"
      >
        <template #actions>
          <Badge :variant="doctor.is_active ? 'success' : 'warning'" dot>
            {{ doctor.is_active ? 'Active' : 'Inactive' }}
          </Badge>
          <Badge :variant="doctor.is_available ? 'info' : 'neutral'" dot>
            {{ doctor.is_available ? 'Available' : 'Unavailable' }}
          </Badge>
          <Link :href="`/doctors/${doctor.id}/edit`">
            <Button variant="outline" icon="edit">Edit</Button>
          </Link>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <Card title="Contact">
          <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div>
              <dt class="text-(--color-muted)">Email</dt>
              <dd class="font-medium mt-0.5">{{ doctor.email || '—' }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Phone</dt>
              <dd class="font-medium mt-0.5">{{ doctor.phone || '—' }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Specialization</dt>
              <dd class="font-medium mt-0.5">{{ doctor.specialization || '—' }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">License</dt>
              <dd class="font-medium mt-0.5 font-mono text-xs">{{ doctor.license_number || '—' }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Calendar color</dt>
              <dd class="font-medium mt-0.5 flex items-center gap-2">
                <span
                  class="inline-block h-3 w-3 rounded-full ring-2 ring-base-100"
                  :style="{ backgroundColor: doctor.color }"
                />
                <span class="font-mono text-xs">{{ doctor.color }}</span>
              </dd>
            </div>
          </dl>
        </Card>

        <Card title="Practice">
          <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div>
              <dt class="text-(--color-muted)">Years of experience</dt>
              <dd class="font-medium mt-0.5">{{ doctor.years_of_experience ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-(--color-muted)">Consultation fee</dt>
              <dd class="font-medium mt-0.5">
                {{ doctor.consultation_fee != null ? '$' + Number(doctor.consultation_fee).toFixed(2) : '—' }}
              </dd>
            </div>
            <div v-if="doctor.bio" class="col-span-2">
              <dt class="text-(--color-muted)">Bio</dt>
              <dd class="font-medium mt-0.5 whitespace-pre-wrap">{{ doctor.bio }}</dd>
            </div>
          </dl>
        </Card>
      </div>

      <Card title="Weekly schedule">
        <template #actions>
          <Button
            variant="outline"
            :icon="editingSchedule ? 'x' : 'edit'"
            @click="editingSchedule = !editingSchedule"
          >
            {{ editingSchedule ? 'Cancel' : 'Edit' }}
          </Button>
        </template>

        <div v-if="editingSchedule" class="space-y-2">
          <div
            v-for="row in scheduleRows"
            :key="row.day_of_week"
            class="grid grid-cols-[120px_1fr_1fr_120px] gap-2 items-center"
          >
            <span class="text-sm text-base-content">{{ dayNames[row.day_of_week] }}</span>
            <input
              v-model="row.start_time"
              type="time"
              class="h-9 rounded-md border border-(--color-line-strong) bg-base-100 px-2 text-sm"
            />
            <input
              v-model="row.end_time"
              type="time"
              class="h-9 rounded-md border border-(--color-line-strong) bg-base-100 px-2 text-sm"
            />
            <label class="inline-flex items-center gap-2 text-sm text-base-content">
              <input
                v-model="row.is_active"
                type="checkbox"
                class="rounded border-(--color-line-strong)"
              />
              Active
            </label>
          </div>
          <div class="flex justify-end pt-2">
            <Button icon-right="check" @click="saveSchedule">Save schedule</Button>
          </div>
        </div>

        <div v-else class="overflow-x-auto -mx-1">
          <table class="app-table">
            <thead>
              <tr>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
                <th class="w-28">Active</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in scheduleRows" :key="row.day_of_week">
                <td class="font-medium">{{ dayNames[row.day_of_week] }}</td>
                <td class="text-(--color-muted)">{{ row.start_time }}</td>
                <td class="text-(--color-muted)">{{ row.end_time }}</td>
                <td>
                  <Badge :variant="row.is_active ? 'success' : 'neutral'" dot>
                    {{ row.is_active ? 'Active' : 'Off' }}
                  </Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <Card title="Time off">
        <template #actions>
          <Button
            variant="outline"
            :icon="editingTimeOff ? 'x' : 'plus'"
            @click="editingTimeOff = !editingTimeOff"
          >
            {{ editingTimeOff ? 'Cancel' : 'Add time off' }}
          </Button>
        </template>

        <div v-if="editingTimeOff" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 pb-4 border-b border-(--color-line)">
          <div>
            <label class="text-xs font-medium text-base-content mb-1.5 block">From</label>
            <input
              v-model="timeOff.start_at"
              type="datetime-local"
              class="input-shell h-10"
            />
          </div>
          <div>
            <label class="text-xs font-medium text-base-content mb-1.5 block">To</label>
            <input
              v-model="timeOff.end_at"
              type="datetime-local"
              class="input-shell h-10"
            />
          </div>
          <div>
            <label class="text-xs font-medium text-base-content mb-1.5 block">Reason</label>
            <input
              v-model="timeOff.reason"
              type="text"
              class="input-shell h-10"
              placeholder="e.g. Conference"
            />
          </div>
          <div class="sm:col-span-3 flex justify-end">
            <Button icon-right="check" @click="addTimeOff">Save</Button>
          </div>
        </div>

        <div v-if="!doctor.time_offs || doctor.time_offs.length === 0">
          <EmptyState
            icon="calendar-off"
            title="No time off scheduled"
            description="Add upcoming time off to block appointments automatically."
          />
        </div>
        <div v-else class="overflow-x-auto -mx-1">
          <table class="app-table">
            <thead>
              <tr>
                <th>From</th>
                <th>To</th>
                <th>Reason</th>
                <th class="w-20 text-right"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in doctor.time_offs" :key="t.id">
                <td class="text-(--color-muted)">{{ fmtDateTime(t.start_at) }}</td>
                <td class="text-(--color-muted)">{{ fmtDateTime(t.end_at) }}</td>
                <td>{{ t.reason || '—' }}</td>
                <td class="text-right">
                  <button
                    type="button"
                    class="p-1.5 rounded-md text-(--color-muted) hover:bg-error/10 hover:text-error transition-colors"
                    :aria-label="`Remove time off`"
                    title="Remove"
                    @click="removeTimeOff(t.id)"
                  >
                    <Icon name="trash" size="sm" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
