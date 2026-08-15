<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

const props = defineProps({ accounts: Array, currency: String });

const form = useForm({
  from_account_id: props.accounts[0]?.id ?? '',
  to_account_id: '',
  amount: '',
  occurred_at: new Date().toISOString().slice(0, 16),
  reference: '',
  note: '',
});

const submit = () => form.post(route('bank.transfers.store'));
</script>

<template>
  <Head title="New transfer" />
  <AuthenticatedLayout>
    <template #header><h2 class="text-xl font-semibold">New transfer</h2></template>

    <form @submit.prevent="submit" class="p-6 space-y-3 max-w-xl">
      <div>
        <label class="label">From</label>
        <select v-model="form.from_account_id" class="select select-bordered w-full">
          <option v-for="a in accounts" :key="a.id" :value="a.id">
            {{ a.name }} ({{ a.balance }})
          </option>
        </select>
        <p v-if="form.errors.from_account_id" class="text-error text-sm">{{ form.errors.from_account_id }}</p>
      </div>
      <div>
        <label class="label">To</label>
        <select v-model="form.to_account_id" class="select select-bordered w-full">
          <option v-for="a in accounts" :key="a.id" :value="a.id" :disabled="a.id === form.from_account_id">
            {{ a.name }} ({{ a.balance }})
          </option>
        </select>
        <p v-if="form.errors.to_account_id" class="text-error text-sm">{{ form.errors.to_account_id }}</p>
      </div>
      <div>
        <label class="label">Amount ({{ currency }})</label>
        <input v-model="form.amount" type="number" step="0.01" min="0.01" class="input input-bordered w-full" />
        <p v-if="form.errors.amount" class="text-error text-sm">{{ form.errors.amount }}</p>
      </div>
      <div>
        <label class="label">Date</label>
        <input v-model="form.occurred_at" type="datetime-local" class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label">Reference</label>
        <input v-model="form.reference" class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label">Note</label>
        <textarea v-model="form.note" class="textarea textarea-bordered w-full" />
      </div>

      <div class="flex gap-2">
        <button class="btn btn-primary" :disabled="form.processing">Save</button>
        <Link :href="route('bank.transfers.index')" class="btn">Cancel</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
