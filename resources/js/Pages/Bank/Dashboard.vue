<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

defineProps({ accounts: Object, currency: String });
</script>

<template>
  <Head title="Bank & Cash" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold">Bank & Cash</h2>
    </template>

    <div class="p-6 space-y-4">
      <p class="text-sm text-base-content/70">
        Total in {{ currency }}. Active accounts: {{ accounts.total }}.
      </p>

      <div class="overflow-x-auto">
        <table class="table table-zebra">
          <thead>
            <tr>
              <th>Name</th>
              <th>Type</th>
              <th>Bank</th>
              <th class="text-right">Balance</th>
              <th>Last reconciled</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in accounts.data" :key="a.id">
              <td>{{ a.name }}</td>
              <td>{{ a.type }}</td>
              <td>{{ a.bank_name || '—' }}</td>
              <td class="text-right">{{ a.balance }}</td>
              <td>{{ a.last_reconciled_at || 'Never' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex gap-2">
        <Link :href="route('bank.accounts.create')" class="btn btn-primary">New account</Link>
        <Link :href="route('bank.transfers.create')" class="btn btn-secondary">New transfer</Link>
        <Link :href="route('bank.reconcile.index')" class="btn">Reconcile</Link>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
