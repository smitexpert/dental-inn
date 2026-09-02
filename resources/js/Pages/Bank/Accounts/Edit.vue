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
import Badge from '@/Components/ui/Badge.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const props = defineProps({ account: Object, currency: String })

const form = useForm({
  name: props.account.name,
  bank_name: props.account.bank_name,
  account_name: props.account.account_name,
  branch: props.account.branch,
  account_number: props.account.account_number,
  is_active: !!props.account.is_active,
})

function submit() {
  form.patch(route('bank.accounts.update', props.account.id))
}

const breadcrumbs = [
  { label: 'Bank', href: '/bank' },
  { label: 'Accounts', href: '/bank/accounts' },
  { label: props.account.name },
]
</script>

<template>
  <Head title="Edit account" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-5">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader
        :title="`Edit ${account.name}`"
        subtitle="Update account details and active status."
        icon="banknote"
        eyebrow="Edit account"
      >
        <template #actions>
          <Badge :variant="account.is_active ? 'success' : 'warning'" dot>
            {{ account.is_active ? 'Active' : 'Inactive' }}
          </Badge>
        </template>
      </PageHeader>

      <Form :processing="form.processing" @submit.prevent="submit">
        <Card title="Account details">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Name" :error="form.errors.name" required html-for="name">
              <Input id="name" v-model="form.name" required />
            </Field>
            <Field label="Type" html-for="type">
              <Input id="type" :value="account.type" disabled />
            </Field>
            <template v-if="account.type === 'bank'">
              <Field label="Bank name" :error="form.errors.bank_name" html-for="bank_name">
                <Input id="bank_name" v-model="form.bank_name" />
              </Field>
              <Field label="Account name" :error="form.errors.account_name" html-for="account_name">
                <Input id="account_name" v-model="form.account_name" />
              </Field>
              <Field label="Branch" html-for="branch">
                <Input id="branch" v-model="form.branch" />
              </Field>
              <Field label="Account number" :error="form.errors.account_number" html-for="account_number">
                <Input id="account_number" v-model="form.account_number" />
              </Field>
            </template>
            <Field :label="`Opening balance (${currency})`" html-for="opening_balance">
              <Input
                id="opening_balance"
                :model-value="account.opening_balance"
                disabled
              />
            </Field>
            <Field label="Active account">
              <Switch v-model="form.is_active" label="Visible and usable" />
            </Field>
          </div>
        </Card>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Link :href="route('bank.accounts.index')">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button
            type="submit"
            :loading="form.processing"
            icon-right="check"
          >
            Save changes
          </Button>
        </div>
      </Form>
    </div>
  </AuthenticatedLayout>
</template>
