<script setup>
import { ref } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import Avatar from '@/Components/ui/Avatar.vue'

const open = ref(false)
const user = usePage().props.auth.user

function toggle() { open.value = !open.value }
function close() { open.value = false }

function logout() {
  router.post('/logout')
}
</script>

<template>
  <div class="relative">
    <button type="button" class="flex items-center gap-2 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500" @click="toggle">
      <Avatar :src="user?.avatar_url" :name="user?.name" size="md" />
    </button>

    <div
      v-if="open"
      class="absolute right-0 mt-2 w-56 bg-surface border border-border rounded-md shadow-md py-1 z-40"
      @click="close"
    >
      <div class="px-3 py-2 border-b border-border">
        <p class="text-sm font-medium text-foreground">{{ user?.name }}</p>
        <p class="text-xs text-muted-foreground truncate">{{ user?.email }}</p>
      </div>
      <Link href="/profile" class="block px-3 py-2 text-sm text-foreground hover:bg-surface-muted">Profile</Link>
      <button type="button" @click="logout" class="w-full text-left px-3 py-2 text-sm text-foreground hover:bg-surface-muted">
        Sign out
      </button>
    </div>
  </div>
</template>
