<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

defineProps({ transfer: Object, movements: Array });
</script>

<template>
  <Head :title="`Transfer #${transfer.id}`" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Transfer #{{ transfer.id }}</h2></template>

    <div class="p-6 space-y-4">
      <div class="text-sm space-y-1">
        <div><strong>From:</strong> {{ transfer.from_account?.name }}</div>
        <div><strong>To:</strong> {{ transfer.to_account?.name }}</div>
        <div><strong>Amount:</strong> {{ transfer.amount }}</div>
        <div><strong>Date:</strong> {{ transfer.occurred_at }}</div>
        <div><strong>Reference:</strong> {{ transfer.reference || '—' }}</div>
        <div><strong>Group:</strong> <code>{{ transfer.transfer_group_id }}</code></div>
      </div>

      <h3 class="font-semibold">Movements</h3>
      <div class="overflow-x-auto">
        <table class="table table-zebra">
          <thead>
            <tr><th>Account</th><th>Type</th><th class="text-right">Amount</th></tr>
          </thead>
          <tbody>
            <tr v-for="m in movements" :key="m.id">
              <td>{{ m.account?.name }}</td>
              <td>{{ m.type }}</td>
              <td class="text-right">{{ m.amount }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <Link :href="route('bank.transfers.index')" class="btn">Back</Link>
    </div>
  </AuthenticatedLayout>
</template>
