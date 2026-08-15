<script setup>
import { Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Badge from '@/Components/ui/Badge.vue'
import NavIcon from './NavIcon.vue'

const props = defineProps({
  href: { type: String, required: true },
  label: { type: String, required: true },
  icon: { type: String, default: null },
  active: { type: Boolean, default: false },
  comingSoon: { type: Boolean, default: false },
  badge: { type: [Number, String, null], default: null },
  children: { type: Array, default: () => [] },
})

const open = ref(props.active)

watch(
  () => props.active,
  (v) => {
    if (v) open.value = true
  },
)

const classes = computed(() => [
  'flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors',
  props.comingSoon
    ? 'text-base-content/70 cursor-not-allowed'
    : props.active
      ? 'bg-primary/10 text-primary font-medium'
      : 'text-base-content hover:bg-base-200',
])

// Hide text, badges, and chevrons in the icon-only (collapsed) state.
const labelClass = 'flex-1 truncate text-left is-drawer-close:hidden'
const metaClass = 'is-drawer-close:hidden'

// `icon` arrives from `useNav.js` as a short name (e.g. 'calendar-clock')
// and is passed straight through to NavIcon.
const iconName = computed(() => props.icon ?? '')
</script>

<template>
  <div v-if="!comingSoon && children.length > 0">
    <button
      type="button"
      :class="classes"
      :aria-expanded="open"
      @click="open = !open"
    >
      <NavIcon v-if="icon" :name="iconName" />
      <span :class="labelClass">{{ label }}</span>
      <Badge v-if="badge" variant="warning" :class="metaClass">{{ badge }}</Badge>
      <span :class="['text-xs', metaClass]">{{ open ? '▾' : '▸' }}</span>
    </button>
    <ul
      v-if="open"
      class="ml-4 mt-0.5 space-y-0.5 border-l border-base-300 pl-2 is-drawer-close:hidden"
    >
      <li v-for="child in children" :key="child.href">
        <Link
          :href="child.href"
          :class="[
            'flex items-center gap-2 px-3 py-1.5 rounded-md text-sm transition-colors',
            $page.url === child.href || ($page.url.startsWith(child.href) && child.href !== '/inventory/products')
              ? 'bg-primary/10 text-primary font-medium'
              : 'text-base-content/70 hover:bg-base-200 hover:text-base-content',
          ]"
        >
          <span class="flex-1 truncate">{{ child.label }}</span>
        </Link>
      </li>
    </ul>
  </div>
  <Link v-else-if="!comingSoon" :href="href" :class="classes">
    <NavIcon v-if="icon" :name="iconName" />
    <span :class="labelClass">{{ label }}</span>
    <Badge v-if="badge" variant="warning" :class="metaClass">{{ badge }}</Badge>
  </Link>
  <span v-else :class="classes">
    <NavIcon v-if="icon" :name="iconName" />
    <span :class="labelClass">{{ label }}</span>
    <Badge variant="neutral" :class="metaClass">Soon</Badge>
  </span>
</template>
