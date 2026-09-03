<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeaderSticky from '@/Components/ui/PageHeaderSticky.vue'
import KpiStrip from '@/Components/ui/KpiStrip.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Input from '@/Components/ui/Input.vue'
import Select from '@/Components/ui/Select.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import ResponsiveTable from '@/Components/ui/ResponsiveTable.vue'
import Avatar from '@/Components/ui/Avatar.vue'
import Icon from '@/Components/ui/Icon.vue'
import { useConfirm } from '@/Composables/useConfirm.js'
import { ref, watch, computed } from 'vue'

const props = defineProps({
  users: { type: Object, required: true },
  roles: { type: Array, required: true },
  filters: { type: Object, required: true },
  kpis: { type: Array, default: () => [] },
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

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'roles', label: 'Roles' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]

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
    <template #pageHeader>
      <PageHeaderSticky
        title="Users"
        :breadcrumb="[{ label: 'Admin' }, { label: 'Users' }]"
        meta="Manage staff accounts, roles and access."
      >
        <template #actions>
          <Link href="/admin/roles">
            <Button variant="outline" icon="shield">Roles</Button>
          </Link>
          <Link href="/admin/users/create">
            <Button icon="plus">New user</Button>
          </Link>
        </template>
      </PageHeaderSticky>
    </template>

    <div class="space-y-5 pt-5">
      <KpiStrip :tiles="kpis" />

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
        <EmptyState
          v-if="users.data.length === 0"
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

        <ResponsiveTable v-else :columns="columns" :rows="users.data">
          <template #cell:name="{ row }">
            <div class="flex items-center gap-2.5">
              <Avatar :name="row.name" size="sm" />
              <div class="min-w-0">
                <p class="font-medium">{{ row.name }}</p>
                <p v-if="row.id === currentUserId" class="text-xs text-primary">
                  (you)
                </p>
              </div>
            </div>
          </template>
          <template #cell:email="{ row }">
            <span class="text-muted">{{ row.email }}</span>
          </template>
          <template #cell:roles="{ row }">
            <div class="flex flex-wrap gap-1">
              <Badge
                v-for="r in row.roles"
                :key="roleLabel(r)"
                variant="primary"
              >
                {{ roleLabel(r) }}
              </Badge>
            </div>
          </template>
          <template #cell:status="{ row }">
            <Badge :variant="row.is_active ? 'success' : 'danger'" dot>
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </Badge>
          </template>
          <template #cell:actions="{ row }">
            <div class="inline-flex items-center gap-1 justify-end">
              <Link
                :href="`/admin/users/${row.id}/edit`"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`Edit ${row.name}`"
                :title="`Edit ${row.name}`"
              >
                <Icon name="edit" size="sm" />
              </Link>
              <button
                v-if="row.id !== currentUserId"
                type="button"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-base-content transition-colors"
                :aria-label="row.is_active ? `Deactivate ${row.name}` : `Reactivate ${row.name}`"
                :title="row.is_active ? 'Toggle active' : 'Reactivate'"
                @click="toggleActive(row)"
              >
                <Icon
                  :name="row.is_active ? 'lock' : 'key'"
                  size="sm"
                />
              </button>
              <button
                v-if="row.id !== currentUserId"
                type="button"
                class="p-1.5 rounded-md text-muted hover:bg-error/10 hover:text-error transition-colors"
                :aria-label="`Delete ${row.name}`"
                :title="`Delete ${row.name}`"
                @click="destroyUser(row)"
              >
                <Icon name="trash" size="sm" />
              </button>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="flex items-center gap-2.5">
                <Avatar :name="row.name" size="sm" />
                <div class="min-w-0">
                  <p class="font-medium">{{ row.name }}</p>
                  <p class="text-xs text-muted">{{ row.email }}</p>
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-1">
                <Badge
                  v-for="r in row.roles"
                  :key="roleLabel(r)"
                  variant="primary"
                >
                  {{ roleLabel(r) }}
                </Badge>
                <Badge :variant="row.is_active ? 'success' : 'danger'" dot>
                  {{ row.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </div>
            </div>
          </template>
        </ResponsiveTable>

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
