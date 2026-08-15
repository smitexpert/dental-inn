<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { ref, computed } from 'vue';

const props = defineProps({ accounts: Array, currency: String });

const selectedId = ref(props.accounts[0]?.id ?? null);
const selected = computed(() => props.accounts.find((a) => a.id === selectedId.value));

const form = useForm({ account_id: selectedId.value, counted_balance: 0 });

const choose = (id) => {
  selectedId.value = id;
  const acc = props.accounts.find((a) => a.id === id);
  form.account_id = id;
  form.counted_balance = Number(acc?.balance ?? 0);
};

const submit = () => form.post(route('bank.reconcile.run'));
</script>

<template>
  <Head title="Reconcile" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">Reconcile account</h2></template>

    <div class="p-6 space-y-4 max-w-xl">
      <select :value="selectedId" @change="choose($event.target.value)" class="select select-bordered w-full">
        <option v-for="a in accounts" :key="a.id" :value="a.id">
          {{ a.name }} — {{ a.balance }} {{ currency }}
        </option>
      </select>

      <div v-if="selected" class="text-sm space-y-1">
        <div>Last reconciled: {{ selected.last_reconciled_at || 'Never' }}</div>
        <div>Last reconciled balance: {{ selected.last_reconciled_balance || '—' }}</div>
      </div>

      <form @submit.prevent="submit" class="space-y-3">
        <div>
          <label class="label">Counted balance ({{ currency }})</label>
          <input v-model="form.counted_balance" type="number" step="0.01" min="0" class="input input-bordered w-full" />
          <p v-if="form.errors.counted_balance" class="text-error text-sm">{{ form.errors.counted_balance }}</p>
          <p v-if="form.errors.account_id" class="text-error text-sm">{{ form.errors.account_id }}</p>
        </div>
        <button class="btn btn-primary" :disabled="form.processing">Reconcile</button>
        <Link :href="route('bank.dashboard')" class="btn ml-2">Cancel</Link>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
