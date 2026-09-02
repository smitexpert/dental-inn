<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Badge from '@/Components/ui/Badge.vue'
import Icon from '@/Components/ui/Icon.vue'

const props = defineProps({
  href: { type: String, required: true },
  label: { type: String, required: true },
  icon: { type: String, default: null },
  active: { type: Boolean, default: false },
  comingSoon: { type: Boolean, default: false },
  badge: { type: [Number, String, null], default: null },
  children: { type: Array, default: () => [] },
  collapsed: { type: Boolean, default: false },
})

const open = ref(props.active)
watch(
  () => props.active,
  (v) => {
    if (v) open.value = true
  },
)

const wrap = computed(() => [
  'nav-item w-full',
  props.comingSoon && 'cursor-not-allowed opacity-70',
  props.active && !props.comingSoon && 'nav-item-active',
])

const page = usePage()
const childLink = (href) =>
  page.url === href ||
  (page.url.startsWith(href) && href !== '/inventory/products')
</script>

<template>
  <div v-if="!comingSoon && children.length > 0">
    <button
      type="button"
      :class="wrap"
      :aria-expanded="open"
      :title="collapsed ? label : null"
      @click="open = !open"
    >
      <Icon v-if="icon" :name="icon" size="md" />
      <span v-if="!collapsed" class="flex-1 truncate text-left">{{ label }}</span>
      <Badge v-if="badge && !collapsed" variant="warning">{{ badge }}</Badge>
      <Icon
        v-if="!collapsed"
        :name="open ? 'chevron-down' : 'chevron-right'"
        size="sm"
        class="opacity-60"
      />
    </button>
    <ul
      v-if="open && !collapsed"
      class="ml-4 mt-0.5 space-y-0.5 border-l border-(--color-line) pl-2"
    >
      <li v-for="child in children" :key="child.href">
        <Link
          :href="child.href"
          :class="[
            'nav-child',
            childLink(child.href) && 'nav-child-active',
          ]"
        >
          <span class="flex-1 truncate">{{ child.label }}</span>
        </Link>
      </li>
    </ul>
  </div>
  <Link v-else-if="!comingSoon" :href="href" :class="wrap" :title="collapsed ? label : null">
    <Icon v-if="icon" :name="icon" size="md" />
    <span v-if="!collapsed" class="flex-1 truncate text-left">{{ label }}</span>
    <Badge v-if="badge && !collapsed" variant="warning">{{ badge }}</Badge>
  </Link>
  <span v-else :class="wrap" :title="collapsed ? label : null">
    <Icon v-if="icon" :name="icon" size="md" />
    <span v-if="!collapsed" class="flex-1 truncate text-left">{{ label }}</span>
    <Badge v-if="!collapsed" variant="neutral">Soon</Badge>
  </span>
</template>
