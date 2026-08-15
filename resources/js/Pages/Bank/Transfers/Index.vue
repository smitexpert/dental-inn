<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

const props = defineProps({ transfers: Object, accounts: Array, filters: Object });

const form = useForm({
  date_from: props.filters?.date_from ?? '',
  date_to: props.filters?.date_to ?? '',
  q: props.filters?.q ?? '',
});
const submit = () => form.get(route('bank.transfers.index'), { preserveState: true });
</script>

<template>
  <Head title="Transfers" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Transfers</h2></template>

    <div class="p-6 space-y-4">
      <form @submit.prevent="submit" class="flex flex-wrap gap-2">
        <input v-model="form.date_from" type="date" class="input input-bordered" />
        <input v-model="form.date_to" type="date" class="input input-bordered" />
        <input v-model="form.q" placeholder="Search reference / note…" class="input input-bordered" />
        <button class="btn">Filter</button>
      </form>

      <div class="overflow-x-auto">
        <table class="table table-zebra">
          <thead>
            <tr>
              <th>Date</th>
              <th>From</th>
              <th>To</th>
              <th class="text-right">Amount</th>
              <th>Reference</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in transfers.data" :key="t.id">
              <td>{{ t.occurred_at }}</td>
              <td>{{ t.from_account?.name }}</td>
              <td>{{ t.to_account?.name }}</td>
              <td class="text-right">{{ t.amount }}</td>
              <td>{{ t.reference || '—' }}</td>
              <td><Link :href="route('bank.transfers.show', t.id)" class="btn btn-xs">View</Link></td>
            </tr>
          </tbody>
        </table>
      </div>

      <Link :href="route('bank.transfers.create')" class="btn btn-primary">New transfer</Link>
    </div>
  </AuthenticatedLayout>
</template>
