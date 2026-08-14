<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiSwitch from '@/Components/ui/Switch.vue'
import { ref, watch } from 'vue'

const props = defineProps({ doctors: Object, specializations: Array, filters: Object })

const q = ref(props.filters.q ?? '')
const specialization = ref(props.filters.specialization ?? null)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/doctors',
      { q: q.value, specialization: specialization.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, specialization], applyFilters)

function toggleAvailable(doctor) {
  router.post(`/doctors/${doctor.id}/toggle-availability`)
}

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
</script>

<template>
  <Head title="Doctors" />
  <AuthenticatedLayout>
    <UiCard title="Doctors">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Doctors</h3>
          <Link href="/doctors/create"><UiButton>New doctor</UiButton></Link>
        </div>
      </template>
      <div class="grid grid-cols-2 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search by name, email, or specialization" />
        <UiSelect
          v-model="specialization"
          :options="[
            { label: 'All specializations', value: null },
            ...specializations.map((s) => ({ label: s, value: s })),
          ]"
        />
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-base-200 text-base-content/70">
            <tr>
              <th class="text-left px-4 py-2">Doctor</th>
              <th class="text-left px-4 py-2">Specialization</th>
              <th class="text-left px-4 py-2">License</th>
              <th class="text-left px-4 py-2">Fee</th>
              <th class="text-left px-4 py-2">Status</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in doctors.data" :key="d.id" class="border-b border-base-300 last:border-b-0">
              <td class="px-4 py-2 text-base-content">
                <div class="flex items-center gap-2">
                  <span class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: d.color }" />
                  <span class="font-medium">{{ d.name }}</span>
                  <span class="text-base-content/70 text-xs">{{ d.email }}</span>
                </div>
              </td>
              <td class="px-4 py-2 text-base-content/70">{{ d.specialization || '—' }}</td>
              <td class="px-4 py-2 text-base-content/70">{{ d.license_number || '—' }}</td>
              <td class="px-4 py-2 text-base-content/70">
                {{ d.consultation_fee != null ? '$' + Number(d.consultation_fee).toFixed(2) : '—' }}
              </td>
              <td class="px-4 py-2">
                <div class="flex items-center gap-2">
                  <UiBadge :variant="d.is_available ? 'success' : 'danger'">{{
                    d.is_available ? 'Available' : 'Unavailable'
                  }}</UiBadge>
                  <UiBadge v-if="!d.is_active" variant="warning">Inactive</UiBadge>
                </div>
              </td>
              <td class="px-4 py-2 text-right">
                <Link :href="`/doctors/${d.id}`" class="text-primary hover:text-primary mr-3 text-sm">View</Link>
                <Link :href="`/doctors/${d.id}/edit`" class="text-primary hover:text-primary mr-3 text-sm">Edit</Link>
                <button @click="toggleAvailable(d)" class="text-base-content/70 hover:text-base-content text-sm">
                  {{ d.is_available ? 'Disable' : 'Enable' }}
                </button>
              </td>
            </tr>
            <tr v-if="doctors.data.length === 0">
              <td colspan="6" class="text-center text-base-content/70 py-6">No doctors found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-if="doctors.last_page > 1"
        class="mt-3 flex items-center justify-between text-sm text-base-content/70"
      >
        <span>Page {{ doctors.current_page }} of {{ doctors.last_page }}</span>
        <div class="flex gap-1">
          <template v-for="link in doctors.links" :key="link.label">
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
