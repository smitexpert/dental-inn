<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Icon from '@/Components/ui/Icon.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const initial = usePage().props.working_hours
const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
const labels = {
  mon: 'Monday',
  tue: 'Tuesday',
  wed: 'Wednesday',
  thu: 'Thursday',
  fri: 'Friday',
  sat: 'Saturday',
  sun: 'Sunday',
}
const shortLabels = {
  mon: 'Mon',
  tue: 'Tue',
  wed: 'Wed',
  thu: 'Thu',
  fri: 'Fri',
  sat: 'Sat',
  sun: 'Sun',
}

const form = useForm({
  working_hours: Object.fromEntries(days.map((d) => [d, initial[d] ?? []])),
})

function addSlot(day) {
  form.working_hours[day] = [
    ...(form.working_hours[day] || []),
    { start: '09:00', end: '17:00' },
  ]
}
function removeSlot(day, idx) {
  form.working_hours[day] = form.working_hours[day].filter((_, i) => i !== idx)
}

function submit() {
  form.patch('/settings/working-hours')
}

const breadcrumbs = [
  { label: 'Settings', href: '/settings' },
  { label: 'Working hours' },
]
</script>

<template>
  <Head title="Working hours" />
  <AuthenticatedLayout>
    <div class="max-w-2xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="Working hours"
        subtitle="Define when the clinic accepts appointments each day."
        icon="clock"
        eyebrow="Settings"
      />

      <Form :processing="form.processing" @submit="submit">
        <Card>
          <div class="divide-y divide-(--color-line)">
            <div
              v-for="day in days"
              :key="day"
              class="py-4 first:pt-0 last:pb-0"
            >
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                  <span
                    class="h-8 w-8 rounded-lg bg-primary/10 text-primary inline-flex items-center justify-center text-xs font-semibold"
                  >
                    {{ shortLabels[day] }}
                  </span>
                  <span class="font-medium text-base-content">{{ labels[day] }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Badge :variant="form.working_hours[day].length === 0 ? 'neutral' : 'success'" dot>
                    {{ form.working_hours[day].length === 0 ? 'Closed' : `${form.working_hours[day].length} slot${form.working_hours[day].length === 1 ? '' : 's'}` }}
                  </Badge>
                  <Button
                    size="sm"
                    variant="soft"
                    icon="plus"
                    type="button"
                    @click="addSlot(day)"
                  >
                    Add slot
                  </Button>
                </div>
              </div>

              <div
                v-if="form.working_hours[day].length === 0"
                class="text-sm text-muted flex items-center gap-2 pl-10"
              >
                <Icon name="info" size="sm" class="text-muted" />
                Closed all day
              </div>

              <div class="space-y-2 pl-10">
                <div
                  v-for="(slot, idx) in form.working_hours[day]"
                  :key="idx"
                  class="flex items-center gap-2"
                >
                  <Input v-model="form.working_hours[day][idx].start" type="time" />
                  <span class="text-muted text-sm">to</span>
                  <Input v-model="form.working_hours[day][idx].end" type="time" />
                  <Button
                    size="sm"
                    variant="ghost"
                    icon="trash"
                    type="button"
                    class="text-error hover:bg-error/10"
                    @click="removeSlot(day, idx)"
                  >
                    Remove
                  </Button>
                </div>
                <p
                  v-if="form.errors[`working_hours.${day}`]"
                  class="text-xs font-medium text-error mt-1"
                >
                  {{ form.errors[`working_hours.${day}`] }}
                </p>
              </div>
            </div>
          </div>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link href="/settings">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button
            type="submit"
            :loading="form.processing"
            icon-right="check"
          >
            Save working hours
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
