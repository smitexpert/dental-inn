<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'

const initial = usePage().props.working_hours
const days = ['mon','tue','wed','thu','fri','sat','sun']
const labels = { mon:'Monday', tue:'Tuesday', wed:'Wednesday', thu:'Thursday', fri:'Friday', sat:'Saturday', sun:'Sunday' }

const form = useForm({
  working_hours: Object.fromEntries(
    days.map((d) => [d, initial[d] ?? []])
  ),
})

function addSlot(day) {
  form.working_hours[day] = [...(form.working_hours[day] || []), { start: '09:00', end: '17:00' }]
}
function removeSlot(day, idx) {
  form.working_hours[day] = form.working_hours[day].filter((_, i) => i !== idx)
}

function submit() { form.patch('/settings/working-hours') }
</script>

<template>
  <Head title="Working hours" />
  <AuthenticatedLayout>
    <div class="max-w-2xl">
      <UiCard title="Working hours">
        <form @submit.prevent="submit" class="space-y-4">
          <div v-for="day in days" :key="day" class="border-b border-border pb-3 last:border-b-0 last:pb-0">
            <div class="flex items-center justify-between mb-2">
              <span class="font-medium text-foreground">{{ labels[day] }}</span>
              <UiButton size="sm" variant="secondary" type="button" @click="addSlot(day)">+ Add slot</UiButton>
            </div>
            <div v-if="form.working_hours[day].length === 0" class="text-sm text-muted-foreground">Closed</div>
            <div v-for="(slot, idx) in form.working_hours[day]" :key="idx" class="flex items-center gap-2 mb-2">
              <UiInput v-model="form.working_hours[day][idx].start" type="time" />
              <span class="text-muted-foreground">to</span>
              <UiInput v-model="form.working_hours[day][idx].end" type="time" />
              <UiButton size="sm" variant="ghost" type="button" @click="removeSlot(day, idx)">Remove</UiButton>
            </div>
            <p v-if="form.errors[`working_hours.${day}`]" class="text-sm text-danger mt-1">
              {{ form.errors[`working_hours.${day}`] }}
            </p>
          </div>
          <div class="flex justify-end pt-2">
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </form>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
