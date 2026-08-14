<script setup>
import { ref } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import Avatar from '@/Components/ui/Avatar.vue'

const open = ref(false)
const user = usePage().props.auth.user

function toggle() {
  open.value = !open.value
}
function close() {
  open.value = false
}

function logout() {
  router.post('/logout')
}
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="flex items-center gap-2 rounded-full focus:outline-none focus:ring-2 focus:ring-primary"
      @click="toggle"
    >
      <Avatar :src="user?.avatar_url" :name="user?.name" size="md" />
    </button>

    <div
      v-if="open"
      class="absolute right-0 mt-2 w-56 bg-base-100 border border-base-300 rounded-md shadow-md py-1 z-40"
      @click="close"
    >
      <div class="px-3 py-2 border-b border-base-300">
        <p class="text-sm font-medium text-base-content">{{ user?.name }}</p>
        <p class="text-xs text-base-content/70 truncate">{{ user?.email }}</p>
      </div>
      <Link href="/profile" class="block px-3 py-2 text-sm text-base-content hover:bg-base-200"
        >Profile</Link
      >
      <button
        type="button"
        @click="logout"
        class="w-full text-left px-3 py-2 text-sm text-base-content hover:bg-base-200"
      >
        Sign out
      </button>
    </div>
  </div>
</template>
