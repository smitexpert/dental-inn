<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'
import { ref } from 'vue'

const props = defineProps({ doctor: Object })

const editingSchedule = ref(false)
const editingTimeOff = ref(false)

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
const scheduleRows = ref(
  props.doctor.schedules && props.doctor.schedules.length > 0
    ? props.doctor.schedules.map((s) => ({ ...s }))
    : Array.from({ length: 7 }, (_, i) => ({
        day_of_week: i,
        start_time: '09:00',
        end_time: '17:00',
        is_active: i > 0 && i < 6,
      })),
)

const timeOff = ref({ start_at: '', end_at: '', reason: '' })

function saveSchedule() {
  router.patch(`/doctors/${props.doctor.id}/schedule`, {
    schedules: scheduleRows.value,
  }, {
    onSuccess: () => (editingSchedule.value = false),
  })
}

function addTimeOff() {
  router.post(`/doctors/${props.doctor.id}/time-off`, timeOff.value, {
    onSuccess: () => {
      timeOff.value = { start_at: '', end_at: '', reason: '' }
      editingTimeOff.value = false
    },
  })
}

function removeTimeOff(id) {
  if (confirm('Remove this time-off?')) {
    router.delete(`/doctors/${props.doctor.id}/time-off/${id}`)
  }
}
</script>

<template>
  <Head :title="doctor.name" />
  <AuthenticatedLayout>
    <div class="space-y-6">
      <UiCard>
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            <span class="inline-block h-4 w-4 rounded-full" :style="{ backgroundColor: doctor.color }" />
            <div>
              <h2 class="text-xl font-semibold text-foreground">{{ doctor.name }}</h2>
              <p class="text-sm text-muted-foreground">
                {{ doctor.email }}
                <span v-if="doctor.phone">· {{ doctor.phone }}</span>
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <UiBadge :variant="doctor.is_available ? 'success' : 'danger'">{{
              doctor.is_available ? 'Available' : 'Unavailable'
            }}</UiBadge>
            <UiBadge v-if="!doctor.is_active" variant="warning">Inactive</UiBadge>
            <Link :href="`/doctors/${doctor.id}/edit`"><UiButton variant="secondary">Edit</UiButton></Link>
          </div>
        </div>
        <dl class="mt-6 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <div>
            <dt class="text-muted-foreground">Specialization</dt>
            <dd class="text-foreground">{{ doctor.specialization || '—' }}</dd>
          </div>
          <div>
            <dt class="text-muted-foreground">License</dt>
            <dd class="text-foreground">{{ doctor.license_number || '—' }}</dd>
          </div>
          <div>
            <dt class="text-muted-foreground">Years of experience</dt>
            <dd class="text-foreground">{{ doctor.years_of_experience ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-muted-foreground">Consultation fee</dt>
            <dd class="text-foreground">
              {{ doctor.consultation_fee != null ? '$' + Number(doctor.consultation_fee).toFixed(2) : '—' }}
            </dd>
          </div>
          <div v-if="doctor.bio" class="col-span-2 md:col-span-3">
            <dt class="text-muted-foreground">Bio</dt>
            <dd class="text-foreground whitespace-pre-wrap">{{ doctor.bio }}</dd>
          </div>
        </dl>
      </UiCard>

      <UiCard title="Weekly schedule">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-foreground">Weekly schedule</h3>
            <UiButton variant="secondary" @click="editingSchedule = !editingSchedule">
              {{ editingSchedule ? 'Cancel' : 'Edit' }}
            </UiButton>
          </div>
        </template>
        <div v-if="editingSchedule" class="space-y-2">
          <div
            v-for="row in scheduleRows"
            :key="row.day_of_week"
            class="grid grid-cols-[120px_1fr_1fr_120px] gap-2 items-center"
          >
            <span class="text-sm text-foreground">{{ dayNames[row.day_of_week] }}</span>
            <input v-model="row.start_time" type="time" class="h-9 rounded-md border border-border bg-surface px-2 text-sm" />
            <input v-model="row.end_time" type="time" class="h-9 rounded-md border border-border bg-surface px-2 text-sm" />
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="row.is_active" />
              Active
            </label>
          </div>
          <div class="flex justify-end">
            <UiButton @click="saveSchedule">Save schedule</UiButton>
          </div>
        </div>
        <table v-else class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">Day</th>
              <th class="text-left px-4 py-2">Start</th>
              <th class="text-left px-4 py-2">End</th>
              <th class="text-left px-4 py-2">Active</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in scheduleRows"
              :key="row.day_of_week"
              class="border-b border-border last:border-b-0"
            >
              <td class="px-4 py-2 text-foreground">{{ dayNames[row.day_of_week] }}</td>
              <td class="px-4 py-2 text-muted-foreground">{{ row.start_time }}</td>
              <td class="px-4 py-2 text-muted-foreground">{{ row.end_time }}</td>
              <td class="px-4 py-2">
                <UiBadge :variant="row.is_active ? 'success' : 'secondary'">
                  {{ row.is_active ? 'Yes' : 'No' }}
                </UiBadge>
              </td>
            </tr>
          </tbody>
        </table>
      </UiCard>

      <UiCard title="Time off">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-foreground">Time off</h3>
            <UiButton variant="secondary" @click="editingTimeOff = !editingTimeOff">
              {{ editingTimeOff ? 'Cancel' : 'Add time off' }}
            </UiButton>
          </div>
        </template>
        <div v-if="editingTimeOff" class="grid grid-cols-3 gap-2 items-end mb-3">
          <div>
            <label class="text-xs text-muted-foreground">From</label>
            <input v-model="timeOff.start_at" type="datetime-local" class="h-10 w-full rounded-md border border-border bg-surface px-3 text-sm" />
          </div>
          <div>
            <label class="text-xs text-muted-foreground">To</label>
            <input v-model="timeOff.end_at" type="datetime-local" class="h-10 w-full rounded-md border border-border bg-surface px-3 text-sm" />
          </div>
          <div>
            <label class="text-xs text-muted-foreground">Reason</label>
            <input v-model="timeOff.reason" type="text" class="h-10 w-full rounded-md border border-border bg-surface px-3 text-sm" />
          </div>
          <div class="col-span-3 flex justify-end">
            <UiButton @click="addTimeOff">Save</UiButton>
          </div>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">From</th>
              <th class="text-left px-4 py-2">To</th>
              <th class="text-left px-4 py-2">Reason</th>
              <th class="text-right px-4 py-2"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="t in doctor.time_offs"
              :key="t.id"
              class="border-b border-border last:border-b-0"
            >
              <td class="px-4 py-2 text-muted-foreground">{{ new Date(t.start_at).toLocaleString() }}</td>
              <td class="px-4 py-2 text-muted-foreground">{{ new Date(t.end_at).toLocaleString() }}</td>
              <td class="px-4 py-2 text-foreground">{{ t.reason || '—' }}</td>
              <td class="px-4 py-2 text-right">
                <button @click="removeTimeOff(t.id)" class="text-danger hover:text-danger/80 text-sm">Remove</button>
              </td>
            </tr>
            <tr v-if="!doctor.time_offs || doctor.time_offs.length === 0">
              <td colspan="4" class="text-center text-muted-foreground py-6">No time off scheduled.</td>
            </tr>
          </tbody>
        </table>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
