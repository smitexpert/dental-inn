<script setup>
/**
 * NavIcon
 * -------
 * Renders one of the Lucide icons used by the sidebar nav. We import each
 * possible icon statically from `~icons/lucide/*` so `unplugin-icons`
 * bundles them all, and pick the right one at render time via a computed
 * `<component :is>` lookup. The map is a runtime guard; the static
 * imports are what actually drive bundle output.
 *
 * Props:
 *   - name: the short icon name from `useNav.js` (e.g. 'calendar-clock').
 *           Falls back to a generic square placeholder when missing.
 */
import { computed, markRaw } from 'vue'
import IconGauge from '~icons/lucide/gauge'
import IconCalendarClock from '~icons/lucide/calendar-clock'
import IconUserRound from '~icons/lucide/user-round'
import IconUsersRound from '~icons/lucide/users-round'
import IconBoxes from '~icons/lucide/boxes'
import IconReceiptText from '~icons/lucide/receipt-text'
import IconBanknote from '~icons/lucide/banknote'
import IconLineChart from '~icons/lucide/line-chart'
import IconBellRing from '~icons/lucide/bell-ring'
import IconSettings from '~icons/lucide/settings'
import IconUserCog from '~icons/lucide/user-cog'
import IconKey from '~icons/lucide/key'
import IconSquare from '~icons/lucide/square'

const props = defineProps({
  name: { type: String, required: true },
})

// `markRaw` keeps Vue from making the icon component reactive (icons are
// render-only and have no reactive state).
const registry = markRaw({
  gauge: IconGauge,
  'calendar-clock': IconCalendarClock,
  'user-round': IconUserRound,
  'users-round': IconUsersRound,
  boxes: IconBoxes,
  'receipt-text': IconReceiptText,
  banknote: IconBanknote,
  'line-chart': IconLineChart,
  'bell-ring': IconBellRing,
  settings: IconSettings,
  'user-cog': IconUserCog,
  key: IconKey,
})

const icon = computed(() => registry[props.name] ?? IconSquare)
</script>

<template>
  <component
    :is="icon"
    class="w-4 h-4 shrink-0"
  />
</template>