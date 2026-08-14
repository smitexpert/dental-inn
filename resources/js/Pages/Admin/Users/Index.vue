<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import { ref, watch } from 'vue'

const props = defineProps({ users: Object, roles: Array, filters: Object })

const q = ref(props.filters.q ?? '')
const role = ref(props.filters.role ?? null)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get('/admin/users', { q: q.value, role: role.value }, { preserveState: true, replace: true })
  }, 300)
}
watch([q, role], applyFilters)

function toggleActive(user) {
  if (confirm(`Toggle active status for ${user.name}?`)) {
    router.post(`/admin/users/${user.id}/toggle-active`)
  }
}
function destroyUser(user) {
  if (confirm(`Permanently delete ${user.name}?`)) {
    router.delete(`/admin/users/${user.id}`)
  }
}
</script>

<template>
  <Head title="Users" />
  <AuthenticatedLayout>
    <UiCard title="Users">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-foreground">Users</h3>
          <Link href="/admin/users/create"><UiButton>New user</UiButton></Link>
        </div>
      </template>
      <div class="grid grid-cols-2 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search by name or email" />
        <UiSelect
          v-model="role"
          :options="[{ label: 'All roles', value: null }, ...roles.map((r) => ({ label: r, value: r }))]"
        />
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Email</th>
              <th class="text-left px-4 py-2">Role</th>
              <th class="text-left px-4 py-2">Status</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users.data" :key="u.id" class="border-b border-border last:border-b-0">
              <td class="px-4 py-2 text-foreground">{{ u.name }}</td>
              <td class="px-4 py-2 text-muted-foreground">{{ u.email }}</td>
              <td class="px-4 py-2">
                <UiBadge v-for="r in u.roles" :key="r" variant="primary" class="mr-1">{{ r }}</UiBadge>
              </td>
              <td class="px-4 py-2">
                <UiBadge :variant="u.is_active ? 'success' : 'danger'">{{
                  u.is_active ? 'Active' : 'Inactive'
                }}</UiBadge>
              </td>
              <td class="px-4 py-2 text-right">
                <Link
                  :href="`/admin/users/${u.id}/edit`"
                  class="text-primary-600 hover:text-primary-700 mr-3 text-sm"
                  >Edit</Link
                >
                <button
                  v-if="u.id !== $page.props.auth.user.id"
                  @click="toggleActive(u)"
                  class="text-muted-foreground hover:text-foreground mr-3 text-sm"
                >
                  Toggle
                </button>
                <button
                  v-if="u.id !== $page.props.auth.user.id"
                  @click="destroyUser(u)"
                  class="text-danger hover:text-danger/80 text-sm"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="users.data.length === 0">
              <td colspan="5" class="text-center text-muted-foreground py-6">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-if="users.last_page > 1"
        class="mt-3 flex items-center justify-between text-sm text-muted-foreground"
      >
        <span>Page {{ users.current_page }} of {{ users.last_page }}</span>
        <div class="flex gap-1">
          <template v-for="link in users.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              class="px-2 py-1 rounded hover:bg-surface-muted"
            ><!-- eslint-disable-next-line vue/no-v-text-v-html-on-component --><span v-html="link.label" /></Link>
          </template>
        </div>
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
