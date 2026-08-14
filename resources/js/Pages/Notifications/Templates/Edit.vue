<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'
import { ref } from 'vue'

const props = defineProps({ template: Object, channels: Array })

const form = useForm({
  channel: props.template.channel,
  subject: props.template.subject ?? '',
  body: props.template.body,
  is_active: props.template.is_active,
  locale: props.template.locale,
})

const previewVarsText = ref('')
const preview = ref({ subject: null, body: '' })
async function runPreview() {
  let vars
  try {
    vars = JSON.parse(previewVarsText.value || '{}')
  } catch (e) {
    form.errors.body = 'Preview variables must be valid JSON.'
    return
  }
  const response = await fetch(`/notifications/templates/${props.template.id}/preview`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: JSON.stringify({ variables: vars }),
  })
  if (response.ok) {
    preview.value = await response.json()
  }
}

function submit() {
  form.patch(`/notifications/templates/${props.template.id}`)
}
</script>

<template>
  <Head :title="`Edit ${template.key}`" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-4">
      <UiCard :title="`Edit ${template.key}`">
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
              <label class="block text-sm font-medium mb-1 text-base-content">Locale</label>
              <UiInput v-model="form.locale" :error="form.errors.locale" required />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-base-content">Subject (email only)</label>
            <UiInput v-model="form.subject" :error="form.errors.subject" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-base-content">Body</label>
            <UiTextarea v-model="form.body" rows="6" :error="form.errors.body" required />
          </div>
          <label class="flex items-center gap-2">
            <input type="checkbox" v-model="form.is_active" class="rounded border-base-300" />
            <span class="text-sm text-base-content">Active</span>
          </label>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-300">
            <Link href="/notifications/templates" class="h-10 px-4 inline-flex items-center rounded-md bg-base-200 text-base-content border border-base-300 text-sm">Cancel</Link>
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </form>
      </UiCard>

      <UiCard title="Live preview">
        <p class="text-sm text-base-content/70 mb-2">Provide variable values as JSON.</p>
        <UiTextarea v-model="previewVarsText" rows="3" placeholder='{"patient_name": "Alice", "time": "10:30"}' />
        <UiButton class="mt-2" @click="runPreview">Render</UiButton>
        <div v-if="preview.body || preview.subject" class="mt-3 rounded-md border border-base-300 bg-base-200 p-3 text-sm text-base-content">
          <div v-if="preview.subject" class="font-medium">Subject: {{ preview.subject }}</div>
          <div class="whitespace-pre-wrap">{{ preview.body }}</div>
        </div>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
