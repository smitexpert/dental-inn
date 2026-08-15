<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

defineProps({ currency: String });

const form = useForm({
  name: '',
  type: 'bank',
  bank_name: '',
  account_name: '',
  branch: '',
  account_number: '',
  opening_balance: 0,
  is_active: true,
});

const submit = () => form.post(route('bank.accounts.store'));
</script>

<template>
  <Head title="New account" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">New account</h2></template>

    <form @submit.prevent="submit" class="p-6 space-y-3 max-w-xl">
      <div>
        <label class="label">Name</label>
        <input v-model="form.name" class="input input-bordered w-full" />
        <p v-if="form.errors.name" class="text-error text-sm">{{ form.errors.name }}</p>
      </div>
      <div>
        <label class="label">Type</label>
        <select v-model="form.type" class="select select-bordered w-full">
          <option value="cash">Cash</option>
          <option value="bank">Bank</option>
          <option value="external">External</option>
        </select>
      </div>
      <template v-if="form.type === 'bank'">
        <div>
          <label class="label">Bank name</label>
          <input v-model="form.bank_name" class="input input-bordered w-full" />
          <p v-if="form.errors.bank_name" class="text-error text-sm">{{ form.errors.bank_name }}</p>
        </div>
        <div>
          <label class="label">Account name</label>
          <input v-model="form.account_name" class="input input-bordered w-full" />
          <p v-if="form.errors.account_name" class="text-error text-sm">{{ form.errors.account_name }}</p>
        </div>
        <div>
          <label class="label">Branch</label>
          <input v-model="form.branch" class="input input-bordered w-full" />
        </div>
        <div>
          <label class="label">Account number</label>
          <input v-model="form.account_number" class="input input-bordered w-full" />
          <p v-if="form.errors.account_number" class="text-error text-sm">{{ form.errors.account_number }}</p>
        </div>
      </template>
      <div>
        <label class="label">Opening balance ({{ currency }})</label>
        <input v-model="form.opening_balance" type="number" step="0.01" min="0" class="input input-bordered w-full" />
        <p v-if="form.errors.opening_balance" class="text-error text-sm">{{ form.errors.opening_balance }}</p>
      </div>

      <div class="flex gap-2">
        <button class="btn btn-primary" :disabled="form.processing">Save</button>
        <Link :href="route('bank.accounts.index')" class="btn">Cancel</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
