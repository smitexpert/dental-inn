<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'

const props = defineProps({ channels: Array })

const form = useForm({
  key: '',
  channel: 'sms',
  subject: '',
  body: '',
  is_active: true,
  locale: 'en',
})

function submit() {
  form.post('/notifications/templates')
}
</script>

<template>
  <Head title="New notification template" />
  <AuthenticatedLayout>
    <div class="max-w-3xl">
      <UiCard title="New notification template">
        <form @submit.prevent="submit" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium mb-1 text-base-content">Key</label>
              <UiInput v-model="form.key" placeholder="e.g. appt-confirmation-sms" :error="form.errors.key" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 text-base-content">Channel</label>
              <UiSelect
                v-model="form.channel"
                :options="channels.map((c) => ({ label: c.label, value: c.value }))"
              />
              <p v-if="form.errors.channel" class="mt-1 text-xs text-error-600">{{ form.errors.channel }}</p>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-base-content">Subject (email only)</label>
            <UiInput v-model="form.subject" :error="form.errors.subject" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-base-content">Body</label>
            <UiTextarea v-model="form.body" rows="6" :error="form.errors.body" required />
            <p class="mt-1 text-xs text-base-content/70">
              Use double-curly-brace placeholders like <code>{{ '{' }} {{ '{' }} key {{ '}' }} {{ '}' }}</code>. Undefined variables cause send to fail with a warning.
            </p>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium mb-1 text-base-content">Locale</label>
              <UiInput v-model="form.locale" :error="form.errors.locale" required />
            </div>
            <label class="flex items-center gap-2 mt-7">
              <input type="checkbox" v-model="form.is_active" class="rounded border-base-300" />
              <span class="text-sm text-base-content">Active</span>
            </label>
          </div>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-300">
            <Link href="/notifications/templates" class="h-10 px-4 inline-flex items-center rounded-md bg-base-200 text-base-content border border-base-300 text-sm">Cancel</Link>
            <UiButton type="submit" :loading="form.processing">Create</UiButton>
          </div>
        </form>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
