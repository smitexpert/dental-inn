<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'

const initial = usePage().props.settings
const form = useForm({
  clinic_name: initial.clinic_name,
  clinic_tagline: initial.clinic_tagline,
  address_line_1: initial.address_line_1,
  address_line_2: initial.address_line_2,
  city: initial.city,
  state: initial.state,
  postal_code: initial.postal_code,
  country: initial.country,
  phone: initial.phone,
  email: initial.email,
  currency_code: initial.currency_code,
  currency_symbol: initial.currency_symbol,
  currency_decimals: initial.currency_decimals,
  currency_position: initial.currency_position,
  timezone: initial.timezone,
  date_format: initial.date_format,
  time_format: initial.time_format,
  fiscal_year_start: initial.fiscal_year_start,
  default_tax_rate: initial.default_tax_rate,
  invoice_prefix: initial.invoice_prefix,
  next_invoice_number: initial.next_invoice_number,
  appointment_slot_minutes: initial.appointment_slot_minutes,
})

function submit() {
  form.patch('/settings')
}
</script>

<template>
  <Head title="Clinic settings" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-6">
      <UiCard title="Clinic information">
        <UiForm @submit="submit">
          <div class="grid grid-cols-2 gap-4">
            <UiField label="Clinic name" :error="form.errors.clinic_name" required>
              <UiInput v-model="form.clinic_name" required />
            </UiField>
            <UiField label="Tagline" :error="form.errors.clinic_tagline">
              <UiInput v-model="form.clinic_tagline" />
            </UiField>
            <UiField label="Phone" :error="form.errors.phone">
              <UiInput v-model="form.phone" type="tel" />
            </UiField>
            <UiField label="Email" :error="form.errors.email">
              <UiInput v-model="form.email" type="email" />
            </UiField>
          </div>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>

      <UiCard title="Address">
        <UiForm @submit="submit">
          <UiField label="Address line 1"><UiInput v-model="form.address_line_1" /></UiField>
          <UiField label="Address line 2"><UiInput v-model="form.address_line_2" /></UiField>
          <div class="grid grid-cols-3 gap-4">
            <UiField label="City"><UiInput v-model="form.city" /></UiField>
            <UiField label="State"><UiInput v-model="form.state" /></UiField>
            <UiField label="Postal code"><UiInput v-model="form.postal_code" /></UiField>
          </div>
          <UiField label="Country (ISO 3166-1 alpha-2)" required>
            <UiInput v-model="form.country" maxlength="2" required />
          </UiField>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>

      <UiCard title="Currency">
        <UiForm @submit="submit">
          <div class="grid grid-cols-2 gap-4">
            <UiField label="Code" required
              ><UiInput v-model="form.currency_code" maxlength="3" required
            /></UiField>
            <UiField label="Symbol" required><UiInput v-model="form.currency_symbol" required /></UiField>
            <UiField label="Decimals" required>
              <UiSelect
                v-model="form.currency_decimals"
                :options="[
                  { label: '0', value: 0 },
                  { label: '2', value: 2 },
                  { label: '3', value: 3 },
                  { label: '4', value: 4 },
                ]"
              />
            </UiField>
            <UiField label="Position" required>
              <UiSelect
                v-model="form.currency_position"
                :options="[
                  { label: 'Before (৳100)', value: 'before' },
                  { label: 'After (100৳)', value: 'after' },
                ]"
              />
            </UiField>
            <UiField label="Default tax rate (%)" :error="form.errors.default_tax_rate">
              <UiInput v-model="form.default_tax_rate" type="number" step="0.01" min="0" max="100" />
            </UiField>
          </div>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
