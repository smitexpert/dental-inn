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
const sidebarCollapsed = ref(false)
const isLg = ref(true)

onMounted(() => {
  isLg.value = window.innerWidth >= 1024
  const stored = localStorage.getItem('sidebar-collapsed')
  if (stored === 'true') sidebarCollapsed.value = true
  window.addEventListener('resize', () => {
    isLg.value = window.innerWidth >= 1024
  })
})

function toggleSidebar() {
  if (isLg.value) {
    sidebarCollapsed.value = !sidebarCollapsed.value
    localStorage.setItem('sidebar-collapsed', String(sidebarCollapsed.value))
  } else {
    sidebarOpen.value = !sidebarOpen.value
  }
}

function closeMobileSidebar() {
  if (!isLg.value) sidebarOpen.value = false
}

const settings = usePage().props.settings
</script>

<template>
  <div class="app-shell min-h-screen flex">
    <!-- Desktop sidebar -->
    <div v-if="isLg" class="shrink-0 sticky top-0 h-screen">
      <Sidebar :collapsed="sidebarCollapsed" />
    </div>

    <!-- Mobile drawer -->
    <Transition
      v-else
      enter-active-class="transition-opacity duration-150 ease-out"
      leave-active-class="transition-opacity duration-150 ease-out"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-40 flex"
        @click.self="closeMobileSidebar"
      >
        <div class="relative z-50 h-screen">
          <Sidebar :collapsed="false" />
        </div>
        <div class="flex-1 bg-neutral/40 backdrop-blur-sm" @click="closeMobileSidebar" />
      </div>
    </Transition>

    <div class="flex-1 flex flex-col min-w-0">
      <TopBar
        :collapsed="sidebarCollapsed"
        :is-mobile-open="sidebarOpen"
        @toggle-sidebar="toggleSidebar"
      />
      <slot name="pageHeader" />
      <main class="flex-1 px-6 overflow-x-hidden">
        <div class="max-w-7xl mx-auto">
          <slot />
        </div>
      </main>
    </div>

    <Toast />
    <ConfirmDialog />
  </div>
</template>
