<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'
import { computed, watch } from 'vue'

const props = defineProps({ channels: Array, templates: Array })

const form = useForm({
  channel: 'sms',
  template_key: null,
  subject: '',
  body: '',
  recipients: '',
  variables: '',
})

const requiresSubject = computed(() => form.channel === 'email')

const filteredTemplates = computed(() =>
  props.templates.filter((t) => t.channel === form.channel),
)

watch(
  () => form.template_key,
  (key) => {
    const tpl = props.templates.find((t) => t.key === key)
    if (tpl) {
      form.subject = tpl.subject ?? ''
    }
  },
)

function submit() {
  const recipients = form.recipients
    .split(/[\n,]+/)
    .map((s) => s.trim())
    .filter(Boolean)

  let variables = {}
  if (form.variables && form.variables.trim()) {
    try {
      variables = JSON.parse(form.variables)
    } catch (e) {
      form.errors.variables = 'Variables must be valid JSON.'
      return
    }
  }

  form.transform((data) => ({
    ...data,
    recipients,
    variables,
  })).post('/notifications/send')
}
</script>

<template>
  <Head title="Manual send" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-4">
      <div class="flex items-center justify-between">
        <div class="flex gap-2">
          <Link href="/notifications/templates"><UiButton variant="secondary">Templates</UiButton></Link>
          <Link href="/notifications/logs"><UiButton variant="secondary">Logs</UiButton></Link>
          <Link href="/notifications/send"><UiButton variant="secondary">Manual send</UiButton></Link>
        </div>
      </div>
      <UiCard title="Manual send">
        <form @submit.prevent="submit" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium mb-1 text-base-content">Channel</label>
              <UiSelect
                v-model="form.channel"
                :options="channels.map((c) => ({ label: c.label, value: c.value }))"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 text-base-content">Template (optional)</label>
              <UiSelect
                v-model="form.template_key"
                :options="[{ label: '— Ad-hoc —', value: null }, ...filteredTemplates.map((t) => ({ label: t.key, value: t.key }))]"
              />
            </div>
          </div>
          <div v-if="requiresSubject && !form.template_key">
            <label class="block text-sm font-medium mb-1 text-base-content">Subject</label>
            <UiInput v-model="form.subject" :error="form.errors.subject" />
          </div>
          <div v-if="!form.template_key">
            <label class="block text-sm font-medium mb-1 text-base-content">Body</label>
            <UiTextarea v-model="form.body" rows="5" :error="form.errors.body" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-base-content">Recipients</label>
            <UiTextarea v-model="form.recipients" rows="3" placeholder="One per line, or comma-separated" :error="form.errors.recipients" required />
          </div>
          <div v-if="form.template_key">
            <label class="block text-sm font-medium mb-1 text-base-content">Template variables (JSON)</label>
            <UiTextarea v-model="form.variables" rows="3" placeholder='{"patient_name": "Alice"}' :error="form.errors.variables" />
          </div>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-300">
            <UiButton type="submit" :loading="form.processing">Send</UiButton>
          </div>
        </form>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
