<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import PageHeader from '@/Components/ui/PageHeader.vue'
import KpiStrip from '@/Components/ui/KpiStrip.vue'
import FilterBar from '@/Components/ui/FilterBar.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Input from '@/Components/ui/Input.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import ResponsiveTable from '@/Components/ui/ResponsiveTable.vue'
import Icon from '@/Components/ui/Icon.vue'
import { useConfirm } from '@/Composables/useConfirm.js'
import { ref, watch } from 'vue'

const props = defineProps({
  roles: Object,
  filters: Object,
  kpis: { type: Array, default: () => [] },
})
const confirm = useConfirm()

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
  confirm.require({
    header: 'Delete role?',
    message: `Role "${role.name}" will be permanently removed. Users with this role will lose access.`,
    acceptLabel: 'Delete',
    acceptClass: 'btn-error',
    accept: () => router.delete(`/admin/roles/${role.id}`),
  })
}

function goPage(n) {
  router.get(`/admin/roles?page=${n}`, { q: q.value }, { preserveState: true })
}

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'permissions_count', label: 'Permissions' },
  { key: 'users_count', label: 'Users' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <Head title="Roles" />
  <AuthenticatedLayout>
    <div class="space-y-5">
      <PageHeader
        title="Roles"
        subtitle="Group permissions and assign them to staff."
        icon="shield"
        eyebrow="Admin"
      >
        <template #actions>
          <Link href="/admin/users">
            <Button variant="outline" icon="users-round">Users</Button>
          </Link>
          <Link href="/admin/roles/create">
            <Button icon="plus">New role</Button>
          </Link>
        </template>
      </PageHeader>
      <KpiStrip :tiles="kpis" />

      <FilterBar title="Search">
        <template #aside>
          <Badge variant="primary">
            {{ roles.total }} {{ roles.total === 1 ? 'role' : 'roles' }}
          </Badge>
        </template>
        <div class="md:col-span-3">
          <Input v-model="q" placeholder="Search by name" prefix-icon="search" />
        </div>
      </FilterBar>

      <Card :padded="false">
        <EmptyState
          v-if="roles.data.length === 0"
          icon="shield"
          title="No roles found"
          description="Create your first role to start grouping permissions."
        >
          <template #action>
            <Link href="/admin/roles/create">
              <Button icon="plus">New role</Button>
            </Link>
          </template>
        </EmptyState>

        <ResponsiveTable v-else :columns="columns" :rows="roles.data">
          <template #cell:name="{ row }">
            <div class="flex items-center gap-2.5">
              <span
                class="h-9 w-9 rounded-lg bg-primary/10 text-primary inline-flex items-center justify-center shrink-0"
              >
                <Icon name="shield" size="sm" />
              </span>
              <span class="font-medium">{{ row.name }}</span>
            </div>
          </template>
          <template #cell:permissions_count="{ row }">
            <div class="text-right">
              <Badge variant="info">
                {{ row.permissions_count }} {{ row.permissions_count === 1 ? 'permission' : 'permissions' }}
              </Badge>
            </div>
          </template>
          <template #cell:users_count="{ row }">
            <div class="text-right">
              <Badge variant="neutral">
                {{ row.users_count }} {{ row.users_count === 1 ? 'user' : 'users' }}
              </Badge>
            </div>
          </template>
          <template #cell:actions="{ row }">
            <div class="inline-flex items-center gap-1 justify-end">
              <Link
                :href="`/admin/roles/${row.id}/edit`"
                class="p-1.5 rounded-md text-muted hover:bg-base-200 hover:text-primary transition-colors"
                :aria-label="`Edit ${row.name}`"
                :title="`Edit ${row.name}`"
              >
                <Icon name="edit" size="sm" />
              </Link>
              <button
                type="button"
                class="p-1.5 rounded-md text-muted hover:bg-error/10 hover:text-error transition-colors"
                :aria-label="`Delete ${row.name}`"
                :title="`Delete ${row.name}`"
                @click="destroyRole(row)"
              >
                <Icon name="trash" size="sm" />
              </button>
            </div>
          </template>
          <template #card="{ row }">
            <div class="app-card p-4 space-y-2">
              <div class="flex items-center gap-2.5">
                <span
                  class="h-9 w-9 rounded-lg bg-primary/10 text-primary inline-flex items-center justify-center shrink-0"
                >
                  <Icon name="shield" size="sm" />
                </span>
                <span class="font-medium">{{ row.name }}</span>
              </div>
              <div class="flex flex-wrap items-center gap-1">
                <Badge variant="info">
                  {{ row.permissions_count }} permissions
                </Badge>
                <Badge variant="neutral">
                  {{ row.users_count }} users
                </Badge>
              </div>
            </div>
          </template>
        </ResponsiveTable>

        <div class="px-5 pb-4">
          <Pagination
            :current-page="roles.current_page"
            :last-page="roles.last_page"
            :total="roles.total"
            :per-page="roles.per_page"
            @page="goPage"
          />
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
