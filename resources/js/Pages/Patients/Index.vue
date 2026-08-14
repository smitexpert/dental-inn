<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import { ref, watch } from 'vue'

const props = defineProps({ patients: Object, filters: Object })

const q = ref(props.filters.q ?? '')
const isActive = ref(
  props.filters.is_active === '1' || props.filters.is_active === 1 || props.filters.is_active === true
    ? '1'
    : props.filters.is_active === '0' || props.filters.is_active === 0 || props.filters.is_active === false
      ? '0'
      : '',
)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/patients',
      { q: q.value, is_active: isActive.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, isActive], applyFilters)
</script>

<template>
  <Head title="Patients" />
  <AuthenticatedLayout>
    <UiCard title="Patients">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Patients</h3>
          <Link href="/patients/create"><UiButton>New patient</UiButton></Link>
        </div>
      </template>
      <div class="grid grid-cols-3 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search by name, phone, email, or code" />
        <UiSelect
          v-model="isActive"
          :options="[
            { label: 'All', value: '' },
            { label: 'Active only', value: '1' },
            { label: 'Inactive only', value: '0' },
          ]"
        />
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-base-200 text-base-content/70">
            <tr>
              <th class="text-left px-4 py-2">Code</th>
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Phone</th>
              <th class="text-left px-4 py-2">Email</th>
              <th class="text-left px-4 py-2">Age</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in patients.data" :key="p.id" class="border-b border-base-300 last:border-b-0">
              <td class="px-4 py-2 text-base-content/70 font-mono text-xs">{{ p.patient_code }}</td>
              <td class="px-4 py-2 text-base-content font-medium">{{ p.name }}</td>
              <td class="px-4 py-2 text-base-content/70">{{ p.phone || '—' }}</td>
              <td class="px-4 py-2 text-base-content/70">{{ p.email || '—' }}</td>
              <td class="px-4 py-2 text-base-content/70">{{ p.age ?? '—' }}</td>
              <td class="px-4 py-2 text-right">
                <Link :href="`/patients/${p.id}`" class="text-primary hover:text-primary mr-3 text-sm">View</Link>
                <Link :href="`/patients/${p.id}/edit`" class="text-primary hover:text-primary text-sm">Edit</Link>
              </td>
            </tr>
            <tr v-if="patients.data.length === 0">
              <td colspan="6" class="text-center text-base-content/70 py-6">No patients found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-if="patients.last_page > 1"
        class="mt-3 flex items-center justify-between text-sm text-base-content/70"
      >
        <span>Page {{ patients.current_page }} of {{ patients.last_page }}</span>
        <div class="flex gap-1">
          <template v-for="link in patients.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              class="px-2 py-1 rounded hover:bg-base-200"
              ><!-- eslint-disable-next-line vue/no-v-text-v-html-on-component --><span v-html="link.label" /></Link>
          </template>
        </div>
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
