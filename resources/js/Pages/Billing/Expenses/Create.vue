<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiTextarea from '@/Components/ui/Textarea.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiFormError from '@/Components/ui/Form/FormError.vue'
import UiFormActions from '@/Components/ui/Form/FormActions.vue'

const props = defineProps({ categories: Array, methods: Array })

const form = useForm({
  expense_date: new Date().toISOString().slice(0, 10),
  category_id: props.categories[0]?.id ?? '',
  amount: '',
  currency_code: 'BDT',
  exchange_rate: '1.0',
  payment_method: 'cash',
  vendor: '',
  description: '',
})

function submit() {
  form.post('/billing/expenses', { forceFormData: true })
}
</script>

<template>
  <Head title="New expense" />
  <AuthenticatedLayout>
    <UiCard title="Submit expense">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-foreground">Submit expense</h3>
          <Link href="/billing/expenses"><UiButton variant="ghost">Back</UiButton></Link>
        </div>
      </template>

      <UiForm @submit="submit">
        <UiFormError :errors="form.errors" />
        <div class="grid grid-cols-2 gap-4">
          <UiField label="Date" required><UiInput v-model="form.expense_date" type="date" /></UiField>
          <UiField label="Category" required>
            <UiSelect v-model="form.category_id" :options="categories.map((c) => ({ label: c.name, value: c.id }))" />
          </UiField>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <UiField label="Amount" required><UiInput v-model="form.amount" type="number" min="0.01" step="0.01" /></UiField>
          <UiField label="Currency"><UiInput v-model="form.currency_code" /></UiField>
          <UiField label="Payment method">
            <UiSelect v-model="form.payment_method" :options="methods.map((m) => ({ label: m.label, value: m.value }))" />
          </UiField>
        </div>
        <UiField label="Vendor"><UiInput v-model="form.vendor" /></UiField>
        <UiField label="Description"><UiTextarea v-model="form.description" :rows="3" /></UiField>
        <UiField label="Receipt (optional)"><input type="file" name="receipt" @change="(e) => form.receipt = e.target.files[0]" class="text-sm" /></UiField>

        <UiFormActions :processing="form.processing" submit-label="Submit" :show-cancel="true" cancel-href="/billing/expenses" />
      </UiForm>
    </UiCard>
  </AuthenticatedLayout>
</template>
