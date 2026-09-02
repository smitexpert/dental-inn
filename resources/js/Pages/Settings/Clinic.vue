<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import Button from '@/Components/ui/Button.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

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

const breadcrumbs = [
  { label: 'Settings', href: '/settings' },
  { label: 'Clinic' },
]
</script>

<template>
  <Head title="Clinic settings" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="Clinic settings"
        subtitle="Clinic details, address, currency and invoice defaults."
        icon="building"
        eyebrow="Settings"
      />

      <Form :processing="form.processing" @submit="submit">
        <Card title="Clinic information" subtitle="Public-facing clinic identity.">
          <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Field label="Clinic name" :error="form.errors.clinic_name" required>
                <Input v-model="form.clinic_name" required />
              </Field>
              <Field label="Tagline" :error="form.errors.clinic_tagline">
                <Input v-model="form.clinic_tagline" />
              </Field>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Field label="Phone" :error="form.errors.phone">
                <Input v-model="form.phone" type="tel" />
              </Field>
              <Field label="Email" :error="form.errors.email">
                <Input v-model="form.email" type="email" />
              </Field>
            </div>
          </div>
        </Card>

        <Card title="Address" subtitle="Where patients and invoices are sent from.">
          <div class="space-y-4">
            <Field label="Address line 1">
              <Input v-model="form.address_line_1" />
            </Field>
            <Field label="Address line 2">
              <Input v-model="form.address_line_2" />
            </Field>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <Field label="City">
                <Input v-model="form.city" />
              </Field>
              <Field label="State">
                <Input v-model="form.state" />
              </Field>
              <Field label="Postal code">
                <Input v-model="form.postal_code" />
              </Field>
            </div>
            <Field label="Country (ISO 3166-1 alpha-2)" required>
              <Input v-model="form.country" maxlength="2" required />
            </Field>
          </div>
        </Card>

        <Card title="Currency" subtitle="How amounts and invoices are formatted.">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Code" required>
              <Input v-model="form.currency_code" maxlength="3" required />
            </Field>
            <Field label="Symbol" required>
              <Input v-model="form.currency_symbol" required />
            </Field>
            <Field label="Decimals" required>
              <Select
                v-model="form.currency_decimals"
                :options="[
                  { label: '0', value: 0 },
                  { label: '2', value: 2 },
                  { label: '3', value: 3 },
                  { label: '4', value: 4 },
                ]"
              />
            </Field>
            <Field label="Position" required>
              <Select
                v-model="form.currency_position"
                :options="[
                  { label: 'Before (৳100)', value: 'before' },
                  { label: 'After (100৳)', value: 'after' },
                ]"
              />
            </Field>
            <Field label="Default tax rate (%)" :error="form.errors.default_tax_rate">
              <Input v-model="form.default_tax_rate" type="number" step="0.01" min="0" max="100" />
            </Field>
            <Field label="Timezone">
              <Input v-model="form.timezone" placeholder="e.g. Asia/Dhaka" />
            </Field>
          </div>
        </Card>

        <Card title="Invoices & scheduling" subtitle="Numbering and appointment defaults.">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Invoice prefix" :error="form.errors.invoice_prefix">
              <Input v-model="form.invoice_prefix" />
            </Field>
            <Field label="Next invoice number" :error="form.errors.next_invoice_number">
              <Input v-model="form.next_invoice_number" type="number" min="1" />
            </Field>
            <Field label="Appointment slot (minutes)">
              <Input v-model="form.appointment_slot_minutes" type="number" min="5" max="240" />
            </Field>
            <Field label="Fiscal year start (MM-DD)">
              <Input v-model="form.fiscal_year_start" placeholder="01-01" />
            </Field>
            <Field label="Date format">
              <Input v-model="form.date_format" placeholder="Y-m-d" />
            </Field>
            <Field label="Time format">
              <Input v-model="form.time_format" placeholder="H:i" />
            </Field>
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
            Save settings
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
