<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Avatar from '@/Components/ui/Avatar.vue'
import Icon from '@/Components/ui/Icon.vue'
import { useConfirm } from '@/Composables/useConfirm.js'
import { ref, watch, computed } from 'vue'

const props = defineProps({
  users: { type: Object, required: true },
  roles: { type: Array, required: true },
  filters: { type: Object, required: true },
})

const confirm = useConfirm()
const page = usePage()
const currentUserId = computed(() => page.props.auth?.user?.id)

const q = ref(props.filters.q ?? '')
const role = ref(props.filters.role ?? null)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get(
      '/admin/users',
      { q: q.value, role: role.value },
      { preserveState: true, replace: true },
    )
  }, 300)
}
watch([q, role], applyFilters)

function toggleActive(user) {
  confirm.require({
    header: user.is_active ? 'Deactivate user?' : 'Reactivate user?',
    message: `${user.name}'s access will be ${user.is_active ? 'disabled' : 'enabled'}.`,
    acceptLabel: user.is_active ? 'Deactivate' : 'Reactivate',
    accept: () => router.post(`/admin/users/${user.id}/toggle-active`),
  })
}

function destroyUser(user) {
  confirm.require({
    header: 'Delete user?',
    message: `${user.name} will be permanently removed. This cannot be undone.`,
    acceptLabel: 'Delete',
    acceptClass: 'btn-error',
    accept: () => router.delete(`/admin/users/${user.id}`),
  })
}

function goPage(n) {
  router.get(
    `/admin/users?page=${n}`,
    { q: q.value, role: role.value },
    { preserveState: true },
  )
}

// Normalise a role entry — supports both `{ name: 'admin' }` (Spatie model
// serialised) and the simple `'admin'` string shape returned by UserResource.
function roleLabel(r) {
  if (r == null) return ''
  return typeof r === 'string' ? r : (r.name ?? '')
}
</script>

<template>
  <Head title="Users" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Users"
        subtitle="Manage staff accounts, roles and access."
        icon="users-round"
        eyebrow="Admin"
      >
        <template #actions>
          <Link href="/admin/roles">
            <Button variant="outline" icon="shield">Roles</Button>
          </Link>
          <Link href="/admin/users/create">
            <Button icon="plus">New user</Button>
          </Link>
        </template>
      </PageHeader>

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ users.total }} {{ users.total === 1 ? 'user' : 'users' }}
          </Badge>
        </template>
        <div class="md:col-span-2">
          <Input v-model="q" placeholder="Search by name or email" prefix-icon="search" />
        </div>
        <Select
          v-model="role"
          :options="[{ label: 'All roles', value: null }, ...roles.map((r) => ({ label: r, value: r }))]"
          placeholder="All roles"
        />
      </FilterBar>

      <Card :padded="false">
        <div v-if="users.data.length === 0">
          <EmptyState
            icon="users-round"
            title="No users found"
            description="Adjust your filters or add your first staff member."
          >
            <template #action>
              <Link href="/admin/users/create">
                <Button icon="plus">New user</Button>
              </Link>
            </template>
          </EmptyState>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="app-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Roles</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in users.data" :key="u.id">
                <td>
                  <div class="flex items-center gap-2.5">
                    <Avatar :name="u.name" size="sm" />
                    <div class="min-w-0">
                      <p class="font-medium">{{ u.name }}</p>
                      <p v-if="u.id === currentUserId" class="text-xs text-primary">
                        (you)
                      </p>
                    </div>
                  </div>
                </td>
                <td class="text-(--color-muted)">{{ u.email }}</td>
                <td>
                  <div class="flex flex-wrap gap-1">
                    <Badge
                      v-for="r in u.roles"
                      :key="roleLabel(r)"
                      variant="primary"
                    >
                      {{ roleLabel(r) }}
                    </Badge>
                  </div>
                </td>
                <td>
                  <Badge :variant="u.is_active ? 'success' : 'danger'" dot>
                    {{ u.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>
                <td class="text-right">
                  <div class="inline-flex items-center gap-1">
                    <Link
                      :href="`/admin/users/${u.id}/edit`"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-primary transition-colors"
                      :aria-label="`Edit ${u.name}`"
                      :title="`Edit ${u.name}`"
                    >
                      <Icon name="edit" size="sm" />
                    </Link>
                    <button
                      v-if="u.id !== currentUserId"
                      type="button"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-base-200 hover:text-base-content transition-colors"
                      :aria-label="u.is_active ? `Deactivate ${u.name}` : `Reactivate ${u.name}`"
                      :title="u.is_active ? 'Toggle active' : 'Reactivate'"
                      @click="toggleActive(u)"
                    >
                      <Icon
                        :name="u.is_active ? 'lock' : 'key'"
                        size="sm"
                      />
                    </button>
                    <button
                      v-if="u.id !== currentUserId"
                      type="button"
                      class="p-1.5 rounded-md text-(--color-muted) hover:bg-error/10 hover:text-error transition-colors"
                      :aria-label="`Delete ${u.name}`"
                      :title="`Delete ${u.name}`"
                      @click="destroyUser(u)"
                    >
                      <Icon name="trash" size="sm" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="users.current_page"
            :last-page="users.last_page"
            :total="users.total"
            :per-page="users.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
