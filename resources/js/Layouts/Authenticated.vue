<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Sidebar from '@/Components/layout/Sidebar.vue'
import TopBar from '@/Components/layout/TopBar.vue'
import Toast from '@/Components/ui/Toast.vue'
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue'
import { useColorMode } from '@/Composables/useColorMode.js'
import { useFlashMessages } from '@/Composables/useFlashMessages.js'

useColorMode()
useFlashMessages()

const sidebarOpen = ref(false)
const isLg = ref(true)

onMounted(() => {
  isLg.value = window.innerWidth >= 1024
  window.addEventListener('resize', () => {
    isLg.value = window.innerWidth >= 1024
  })
})
</script>

<template>
  <div class="min-h-screen flex bg-base-200 text-base-content">
    <!-- Sidebar: always visible on lg+, drawer on smaller -->
    <div v-if="isLg" class="shrink-0">
      <Sidebar />
    </div>
    <Transition
      v-else
      enter-active-class="transition-opacity"
      leave-active-class="transition-opacity"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div v-if="sidebarOpen" class="fixed inset-0 z-40 flex" @click.self="sidebarOpen = false">
        <div class="relative z-50">
          <Sidebar />
        </div>
        <div class="flex-1 bg-black/50" />
      </div>
    </Transition>

    <div class="flex-1 flex flex-col min-w-0">
      <TopBar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      <main class="flex-1 p-6 overflow-y-auto">
        <slot />
      </main>
    </div>

    <Toast />
    <ConfirmDialog />
  </div>
</template>
