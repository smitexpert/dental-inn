<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Icon from '@/Components/ui/Icon.vue'

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

function goPage(n) {
  router.get(
    `/doctors?page=${n}`,
    { q: q.value, specialization: specialization.value },
    { preserveState: true },
  )
}
</script>

<template>
  <Head title="Doctors" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Doctors"
        subtitle="Manage the clinical team, schedules and availability."
        icon="user-round"
        eyebrow="Team"
      >
        <template #actions>
          <Link href="/doctors/create"><Button icon="plus">New doctor</Button></Link>
        </template>
      </PageHeader>

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ doctors.total }} {{ doctors.total === 1 ? 'doctor' : 'doctors' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <div class="relative">
            <span
              class="absolute left-3 top-1/2 -translate-y-1/2 text-(--color-muted) pointer-events-none"
            >
              <Icon name="search" size="sm" />
            </span>
            <input
              v-model="q"
              type="search"
              placeholder="Search by name, email, or specialization"
              class="input-shell h-10 pl-9"
            />
          </div>
        </div>
        <select
          v-model="specialization"
          class="input-shell h-10"
          @change="applyFilters"
        >
          <option :value="null">All specializations</option>
          <option v-for="s in specializations" :key="s" :value="s">{{ s }}</option>
        </select>
      </FilterBar>

      <Card :padded="false">
        <div class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Doctor</th>
                <th>Specialization</th>
                <th>License</th>
                <th class="w-28">Fee</th>
                <th class="w-40">Status</th>
                <th class="w-32 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in doctors.data" :key="d.id">
                <td>
                  <div class="flex items-center gap-2.5">
                    <span
                      class="h-8 w-8 rounded-full inline-flex items-center justify-center text-xs font-semibold shrink-0 ring-2 ring-base-100"
                      :style="{ backgroundColor: d.color, color: '#fff' }"
                    >
                      {{ d.name?.split(' ').map((s) => s[0]).slice(0, 2).join('').toUpperCase() }}
                    </span>
                    <div class="min-w-0">
                      <div class="font-medium">{{ d.name }}</div>
                      <div class="text-xs text-(--color-muted)">{{ d.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-(--color-muted)">{{ d.specialization || '—' }}</td>
                <td class="text-(--color-muted) font-mono text-xs">{{ d.license_number || '—' }}</td>
                <td class="text-(--color-muted)">
                  {{ d.consultation_fee != null ? '$' + Number(d.consultation_fee).toFixed(2) : '—' }}
                </td>
                <td>
                  <Badge :variant="d.is_active ? 'success' : 'warning'" dot>
                    {{ d.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>
                <td class="text-right">
                  <div class="inline-flex items-center gap-1">
                    <Link
                      :href="`/doctors/${d.id}`"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`View ${d.name}`"
                      :title="`View ${d.name}`"
                    >
                      <Icon name="eye" size="sm" />
                    </Link>
                    <Link
                      :href="`/doctors/${d.id}/edit`"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`Edit ${d.name}`"
                      :title="`Edit ${d.name}`"
                    >
                      <Icon name="edit" size="sm" />
                    </Link>
                    <button
                      type="button"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`Toggle availability for ${d.name}`"
                      :title="`Toggle availability for ${d.name}`"
                      @click="toggleAvailable(d)"
                    >
                      <Icon :name="d.is_available ? 'user-x' : 'user-check'" size="sm" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <EmptyState
            v-if="doctors.data.length === 0"
            icon="user-round"
            title="No doctors found"
            description="Add your first doctor to start managing schedules and appointments."
          >
            <template #action>
              <Link href="/doctors/create"><Button icon="plus">New doctor</Button></Link>
            </template>
          </EmptyState>
        </div>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="doctors.current_page"
            :last-page="doctors.last_page"
            :total="doctors.total"
            :per-page="doctors.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
