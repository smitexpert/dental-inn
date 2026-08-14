<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiCheckbox from '@/Components/ui/Checkbox.vue'
import UiButton from '@/Components/ui/Button.vue'
import { computed } from 'vue'

const props = defineProps({
  account: { type: Object, required: true },
  parents: { type: Array, required: true },
})

const form = useForm({
  code: props.account.code,
  name: props.account.name,
  type: props.account.type,
  parent_id: props.account.parent_id,
  is_active: props.account.is_active,
})

const typeOptions = [
  { label: 'Asset', value: 'asset' },
  { label: 'Liability', value: 'liability' },
  { label: 'Equity', value: 'equity' },
  { label: 'Revenue', value: 'revenue' },
  { label: 'Expense', value: 'expense' },
]

const parentOptions = computed(() => [
  { label: '— No parent —', value: null },
  ...props.parents.map((p) => ({ label: `${p.code} — ${p.name}`, value: p.id })),
])

function submit() {
  form.patch(`/billing/accounts/${props.account.id}`, { preserveScroll: true })
}
</script>

<template>
  <Head title="Edit account" />
  <AuthenticatedLayout>
    <div class="max-w-2xl">
      <UiCard title="Edit account">
        <UiForm @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-4">
            <UiField label="Code" :error="form.errors.code" required>
              <UiInput v-model="form.code" maxlength="10" required />
            </UiField>
            <UiField label="Type" :error="form.errors.type" required>
              <UiSelect v-model="form.type" :options="typeOptions" required />
            </UiField>
          </div>
          <UiField label="Name" :error="form.errors.name" required>
            <UiInput v-model="form.name" required />
          </UiField>
          <UiField label="Parent account" :error="form.errors.parent_id">
            <UiSelect v-model="form.parent_id" :options="parentOptions" />
          </UiField>
          <UiField>
            <label class="flex items-center gap-2">
              <UiCheckbox v-model="form.is_active" :binary="true" />
              <span class="text-sm text-base-content">Active</span>
            </label>
          </UiField>
          <div class="flex justify-end gap-2 pt-2">
            <UiButton variant="ghost" :href="`/billing/accounts/${props.account.id}`">Cancel</UiButton>
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
