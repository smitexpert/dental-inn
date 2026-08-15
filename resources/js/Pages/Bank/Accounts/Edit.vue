<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

const props = defineProps({ account: Object, currency: String });

const form = useForm({
  name: props.account.name,
  bank_name: props.account.bank_name,
  account_name: props.account.account_name,
  branch: props.account.branch,
  account_number: props.account.account_number,
  is_active: !!props.account.is_active,
});

const submit = () => form.patch(route('bank.accounts.update', props.account.id));
</script>

<template>
  <Head title="Edit account" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Edit account</h2></template>

    <form @submit.prevent="submit" class="p-6 space-y-3 max-w-xl">
      <div>
        <label class="label">Name</label>
        <input v-model="form.name" class="input input-bordered w-full" />
      </div>
      <div v-if="account.type === 'bank'">
        <label class="label">Bank name</label>
        <input v-model="form.bank_name" class="input input-bordered w-full" />
      </div>
      <div v-if="account.type === 'bank'">
        <label class="label">Account name</label>
        <input v-model="form.account_name" class="input input-bordered w-full" />
      </div>
      <div v-if="account.type === 'bank'">
        <label class="label">Branch</label>
        <input v-model="form.branch" class="input input-bordered w-full" />
      </div>
      <div v-if="account.type === 'bank'">
        <label class="label">Account number</label>
        <input v-model="form.account_number" class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label">Opening balance</label>
        <input :value="account.opening_balance" disabled class="input input-bordered w-full" />
      </div>
      <div class="flex items-center gap-2">
        <input v-model="form.is_active" type="checkbox" class="checkbox" />
        <label>Active</label>
      </div>

      <div class="flex gap-2">
        <button class="btn btn-primary" :disabled="form.processing">Save</button>
        <Link :href="route('bank.accounts.index')" class="btn">Cancel</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
