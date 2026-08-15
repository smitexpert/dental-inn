<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

const props = defineProps({ accounts: Object, filters: Object });

const form = useForm({
  q: props.filters?.q ?? '',
  type: props.filters?.type ?? '',
});
const submit = () => form.get(route('bank.accounts.index'), { preserveState: true });
</script>

<template>
  <Head title="Bank accounts" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Bank accounts</h2></template>

    <div class="p-6 space-y-4">
      <form @submit.prevent="submit" class="flex flex-wrap gap-2">
        <input v-model="form.q" placeholder="Search…" class="input input-bordered" />
        <select v-model="form.type" class="select select-bordered">
          <option value="">All types</option>
          <option value="cash">Cash</option>
          <option value="bank">Bank</option>
          <option value="external">External</option>
        </select>
        <button class="btn">Filter</button>
      </form>

      <div class="overflow-x-auto">
        <table class="table table-zebra">
          <thead>
            <tr>
              <th>Name</th>
              <th>Type</th>
              <th>Bank</th>
              <th>Account #</th>
              <th class="text-right">Balance</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in accounts.data" :key="a.id">
              <td>{{ a.name }}</td>
              <td>{{ a.type }}</td>
              <td>{{ a.bank_name || '—' }}</td>
              <td>{{ a.account_number || '—' }}</td>
              <td class="text-right">{{ a.balance }}</td>
              <td><Link :href="route('bank.accounts.edit', a.id)" class="btn btn-xs">Edit</Link></td>
            </tr>
          </tbody>
        </table>
      </div>

      <Link :href="route('bank.accounts.create')" class="btn btn-primary">New account</Link>
    </div>
  </AuthenticatedLayout>
</template>
