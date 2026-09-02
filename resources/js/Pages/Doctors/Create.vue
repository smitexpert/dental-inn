<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import Card from '@/Components/ui/Card.vue'
import Form from '@/Components/ui/Form/Form.vue'
import Field from '@/Components/ui/Form/Field.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import Switch from '@/Components/ui/Switch.vue'
import Textarea from '@/Components/ui/Textarea.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  is_active: true,
  specialization: '',
  license_number: '',
  bio: '',
  years_of_experience: null,
  consultation_fee: null,
  color: '#0EA5E9',
  is_available: true,
})

function submit() {
  form.post('/doctors')
}

const breadcrumbs = [
  { label: 'Doctors', href: '/doctors' },
  { label: 'New doctor' },
]
</script>

<template>
  <Head title="New doctor" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        title="New doctor"
        subtitle="Add a new doctor to the clinical team, including credentials and availability."
        icon="user-round"
        eyebrow="Add team member"
      />

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Personal details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Name" :error="form.errors.name" required html-for="doc-name">
              <Input
                id="doc-name"
                v-model="form.name"
                required
                placeholder="Full name"
              />
            </Field>
            <Field label="Email" :error="form.errors.email" required html-for="doc-email">
              <Input
                id="doc-email"
                v-model="form.email"
                type="email"
                required
                placeholder="doctor@clinic.com"
              />
            </Field>
            <Field label="Phone" :error="form.errors.phone" html-for="doc-phone">
              <Input
                id="doc-phone"
                v-model="form.phone"
                type="tel"
                placeholder="+880…"
              />
            </Field>
            <Field label="Calendar color" :error="form.errors.color" required html-for="doc-color">
              <div class="flex items-center gap-2">
                <input
                  id="doc-color"
                  v-model="form.color"
                  type="color"
                  required
                  class="h-10 w-14 rounded-lg border border-(--color-line-strong) bg-base-100 cursor-pointer"
                />
                <Input v-model="form.color" placeholder="#0EA5E9" />
              </div>
            </Field>
          </div>
        </Card>

        <Card title="Credentials">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Specialization" :error="form.errors.specialization" html-for="doc-spec">
              <Input
                id="doc-spec"
                v-model="form.specialization"
                placeholder="e.g. Endodontics"
              />
            </Field>
            <Field label="License number" :error="form.errors.license_number" html-for="doc-lic">
              <Input
                id="doc-lic"
                v-model="form.license_number"
                placeholder="BDS-…"
              />
            </Field>
            <Field
              label="Years of experience"
              :error="form.errors.years_of_experience"
              html-for="doc-yoe"
            >
              <Input
                id="doc-yoe"
                v-model="form.years_of_experience"
                type="number"
                min="0"
                max="65"
                placeholder="0"
              />
            </Field>
            <Field
              label="Consultation fee"
              :error="form.errors.consultation_fee"
              html-for="doc-fee"
            >
              <Input
                id="doc-fee"
                v-model="form.consultation_fee"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
              />
            </Field>
            <Field
              label="Bio"
              :error="form.errors.bio"
              html-for="doc-bio"
              class="sm:col-span-2"
            >
              <Textarea
                id="doc-bio"
                v-model="form.bio"
                :rows="3"
                placeholder="Brief professional background…"
              />
            </Field>
          </div>
        </Card>

        <Card title="Account access">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field
              label="Password"
              :error="form.errors.password"
              required
              html-for="doc-pw"
            >
              <Input
                id="doc-pw"
                v-model="form.password"
                type="password"
                required
                placeholder="Min. 8 characters"
              />
            </Field>
            <Field
              label="Confirm password"
              :error="form.errors.password_confirmation"
              required
              html-for="doc-pwc"
            >
              <Input
                id="doc-pwc"
                v-model="form.password_confirmation"
                type="password"
                required
              />
            </Field>
          </div>
        </Card>

        <Card title="Availability">
          <div class="space-y-4">
            <Field
              label="Active"
              description="Inactive doctors are hidden from booking and reminders."
            >
              <Switch v-model="form.is_active" label="Visible in app" />
            </Field>
            <Field
              label="Available for appointments"
              description="Toggle off to block new appointments without archiving the doctor."
            >
              <Switch v-model="form.is_available" label="Accepting appointments" />
            </Field>
          </div>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link href="/doctors">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :loading="form.processing" icon-right="check">
            Create doctor
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
