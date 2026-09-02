<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import Avatar from '@/Components/ui/Avatar.vue'
import Icon from '@/Components/ui/Icon.vue'

const open = ref(false)
const user = usePage().props.auth.user

function toggle() {
  open.value = !open.value
}
function close() {
  open.value = false
}

function onDocClick(e) {
  if (!e.target.closest('[data-user-menu]')) close()
}

onMounted(() => document.addEventListener('click', onDocClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocClick))

function logout() {
  router.post('/logout')
}
</script>

<template>
  <div class="relative" data-user-menu>
    <button
      type="button"
      class="flex items-center gap-2 rounded-full app-focus-ring"
      :aria-expanded="open"
      aria-haspopup="menu"
      @click="toggle"
    >
      <Avatar :src="user?.avatar_url" :name="user?.name" size="md" />
    </button>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      leave-active-class="transition duration-100 ease-in"
      enter-from-class="opacity-0 -translate-y-1"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="absolute right-0 mt-2 w-60 app-card py-1 z-40 overflow-hidden"
        role="menu"
        @click="close"
      >
        <div class="px-3 py-3 app-divider">
          <p class="text-sm font-semibold text-base-content truncate">{{ user?.name }}</p>
          <p class="text-xs text-(--color-muted) truncate">{{ user?.email }}</p>
        </div>
        <Link
          href="/profile"
          class="flex items-center gap-2 px-3 py-2 text-sm text-base-content hover:bg-base-200 transition-colors"
          role="menuitem"
        >
          <Icon name="user" size="sm" class="text-(--color-muted)" />
          Profile
        </Link>
        <button
          type="button"
          class="w-full flex items-center gap-2 px-3 py-2 text-sm text-error hover:bg-error/10 transition-colors"
          role="menuitem"
          @click="logout"
        >
          <Icon name="log-out" size="sm" />
          Sign out
        </button>
      </div>
    </Transition>
  </div>
</template>
