<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiInput from '@/Components/ui/Input.vue'
import { ref, watch } from 'vue'

const props = defineProps({ roles: Object, filters: Object })

const q = ref(props.filters.q ?? '')

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get('/admin/roles', { q: q.value }, { preserveState: true, replace: true })
  }, 300)
}
watch(q, applyFilters)

function destroyRole(role) {
  if (confirm(`Permanently delete role "${role.name}"?`)) {
    router.delete(`/admin/roles/${role.id}`)
  }
}
</script>

<template>
  <Head title="Roles" />
  <AuthenticatedLayout>
    <UiCard title="Roles">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-base-content">Roles</h3>
          <Link href="/admin/roles/create"><UiButton>New role</UiButton></Link>
        </div>
      </template>
      <div class="mb-4">
        <UiInput v-model="q" placeholder="Search by name" />
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-base-200 text-base-content/70">
            <tr>
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Permissions</th>
              <th class="text-left px-4 py-2">Users</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in roles.data" :key="r.id" class="border-b border-base-300 last:border-b-0">
              <td class="px-4 py-2 text-base-content font-medium">{{ r.name }}</td>
              <td class="px-4 py-2 text-base-content/70">{{ r.permissions_count }}</td>
              <td class="px-4 py-2 text-base-content/70">{{ r.users_count }}</td>
              <td class="px-4 py-2 text-right">
                <Link
                  :href="`/admin/roles/${r.id}/edit`"
                  class="text-primary hover:text-primary mr-3 text-sm"
                  >Edit</Link
                >
                <button
                  @click="destroyRole(r)"
                  class="text-error hover:text-error/80 text-sm"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="roles.data.length === 0">
              <td colspan="4" class="text-center text-base-content/70 py-6">No roles found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-if="roles.last_page > 1"
        class="mt-3 flex items-center justify-between text-sm text-base-content/70"
      >
        <span>Page {{ roles.current_page }} of {{ roles.last_page }}</span>
        <div class="flex gap-1">
          <template v-for="link in roles.links" :key="link.label">
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