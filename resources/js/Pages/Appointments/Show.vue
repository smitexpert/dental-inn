<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiButton from '@/Components/ui/Button.vue'
import { ref, computed } from 'vue'

const props = defineProps({ appointment: Object })

function fmt(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString()
}

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

const transitions = computed(() => {
  const t = []
  if (props.appointment.status === 'scheduled') t.push('confirmed', 'cancelled', 'no_show')
  if (props.appointment.status === 'confirmed') t.push('checked_in', 'cancelled', 'no_show')
  if (props.appointment.status === 'checked_in') t.push('completed', 'no_show')
  return t
})

const labels = {
  scheduled: 'Mark scheduled',
  confirmed: 'Confirm',
  checked_in: 'Check in',
  completed: 'Complete',
  cancelled: 'Cancel',
  no_show: 'No show',
}

function transition(target) {
  let reason = null
  if (['cancelled', 'no_show'].includes(target)) {
    reason = prompt(`Reason for ${labels[target].toLowerCase()}?`) || null
  }
  router.patch(`/appointments/${props.appointment.id}/status`, { status: target, reason })
}

function destroy() {
  if (confirm('Permanently delete this appointment?')) {
    router.delete(`/appointments/${props.appointment.id}`)
  }
}
</script>

<template>
  <Head title="Appointment" />
  <AuthenticatedLayout>
    <div class="space-y-4 max-w-3xl">
      <UiCard>
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-xl font-semibold text-foreground">{{ appointment.patient_name }}</h2>
            <p class="text-sm text-muted-foreground">{{ appointment.patient_phone }}</p>
          </div>
          <div class="flex items-center gap-2">
            <UiBadge :variant="statusVariant(appointment.status)">{{ appointment.status_label }}</UiBadge>
            <Link :href="`/appointments/${appointment.id}/edit`"><UiButton variant="secondary">Edit</UiButton></Link>
          </div>
        </div>
        <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-muted-foreground">Doctor</dt>
            <dd class="text-foreground">
              <span class="inline-block h-2 w-2 rounded-full mr-2" :style="{ backgroundColor: appointment.doctor_color }" />
              {{ appointment.doctor_name }}
            </dd>
          </div>
          <div>
            <dt class="text-muted-foreground">Scheduled at</dt>
            <dd class="text-foreground">{{ fmt(appointment.scheduled_at) }}</dd>
          </div>
          <div>
            <dt class="text-muted-foreground">Duration</dt>
            <dd class="text-foreground">{{ appointment.duration_minutes }} minutes</dd>
          </div>
          <div>
            <dt class="text-muted-foreground">Ends at</dt>
            <dd class="text-foreground">{{ fmt(appointment.end_at) }}</dd>
          </div>
          <div v-if="appointment.notes" class="col-span-2">
            <dt class="text-muted-foreground">Notes</dt>
            <dd class="text-foreground whitespace-pre-wrap">{{ appointment.notes }}</dd>
          </div>
          <div v-if="appointment.cancelled_at" class="col-span-2">
            <dt class="text-muted-foreground">Cancelled at</dt>
            <dd class="text-foreground">
              {{ fmt(appointment.cancelled_at) }}
              <span v-if="appointment.cancelled_reason"> · {{ appointment.cancelled_reason }}</span>
            </dd>
          </div>
        </dl>
      </UiCard>

      <UiCard v-if="transitions.length > 0" title="Status actions">
        <div class="flex flex-wrap gap-2">
          <UiButton
            v-for="t in transitions"
            :key="t"
            :variant="['cancelled', 'no_show'].includes(t) ? 'danger' : 'primary'"
            @click="transition(t)"
          >
            {{ labels[t] }}
          </UiButton>
        </div>
      </UiCard>

      <UiCard>
        <div class="flex justify-end">
          <UiButton variant="danger" @click="destroy">Delete appointment</UiButton>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
