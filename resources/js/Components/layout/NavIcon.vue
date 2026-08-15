<script setup>
/**
 * NavIcon
 * -------
 * Inline SVG icons for the sidebar nav. Each SVG is the body copied
 * directly from https://icones.js.org (Lucide collection) — no icon
 * library or runtime registry is involved. `name` is the short icon
 * name from `useNav.js`.
 *
 * Width/height/colour come from the `class` attribute below, which
 * Lucide's `stroke="currentColor"` already wires up.
 *
 * To add or change an icon: open https://icones.js.org/?q=NAME, pick
 * the icon, copy the inner SVG markup (the `<path>` / `<g>` / etc.),
 * and paste it into a new `<svg v-if="name === '...'">` block.
 */
import { computed } from 'vue'

const props = defineProps({
  name: { type: String, required: true },
})

// All known short names — anything not in this set falls back to the
// generic square below. Kept as a runtime guard so the template's
// `v-else` branch handles new icons gracefully.
const knownNames = new Set([
  'gauge',
  'calendar-clock',
  'user-round',
  'users-round',
  'boxes',
  'receipt-text',
  'banknote',
  'line-chart',
  'bell-ring',
  'settings',
  'user-cog',
  'key',
])

const known = computed(() => knownNames.has(props.name))
</script>

<template>
  <svg
    v-if="name === 'gauge'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="m12 14l4-4M3.34 19a10 10 0 1 1 17.32 0" /></svg>

  <svg
    v-else-if="name === 'calendar-clock'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="M16 14v2.2l1.6 1M16 2v3m5 2.338V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h2.338M3 9h5.859M8 2v3" /><circle cx="16" cy="16" r="6" /></svg>

  <svg
    v-else-if="name === 'user-round'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><circle cx="12" cy="8" r="5" /><path d="M20 21a8 8 0 0 0-16 0" /></svg>

  <svg
    v-else-if="name === 'users-round'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="M18 21a8 8 0 0 0-16 0" /><circle cx="10" cy="8" r="5" /><path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3" /></svg>

  <svg
    v-else-if="name === 'boxes'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3zM7 16.5l-4.74-2.85M7 16.5l5-3m-5 3v5.17m5-8.17V19l3.97 2.38a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5zm5 3l-5-3m5 3l4.74-2.85M17 16.5v5.17" /><path d="M7.97 4.42A2 2 0 0 0 7 6.13v4.37l5 3l5-3V6.13a2 2 0 0 0-.97-1.71l-3-1.8a2 2 0 0 0-2.06 0zM12 8L7.26 5.15M12 8l4.74-2.85M12 13.5V8" /></svg>

  <svg
    v-else-if="name === 'receipt-text'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="M13 16H8m6-8H8m8 4H8M4 3a1 1 0 0 1 1-1a1.3 1.3 0 0 1 .7.2l.933.6a1.3 1.3 0 0 0 1.4 0l.934-.6a1.3 1.3 0 0 1 1.4 0l.933.6a1.3 1.3 0 0 0 1.4 0l.933-.6a1.3 1.3 0 0 1 1.4 0l.934.6a1.3 1.3 0 0 0 1.4 0l.933-.6A1.3 1.3 0 0 1 19 2a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1a1.3 1.3 0 0 1-.7-.2l-.933-.6a1.3 1.3 0 0 0-1.4 0l-.934.6a1.3 1.3 0 0 1-1.4 0l-.933-.6a1.3 1.3 0 0 0-1.4 0l-.933.6a1.3 1.3 0 0 1-1.4 0l-.934-.6a1.3 1.3 0 0 0-1.4 0l-.933.6a1.3 1.3 0 0 1-.7.2a1 1 0 0 1-1-1z" /></svg>

  <svg
    v-else-if="name === 'banknote'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><rect width="20" height="12" x="2" y="6" rx="2" /><circle cx="12" cy="12" r="2" /><path d="M6 12h.01M18 12h.01" /></svg>

  <svg
    v-else-if="name === 'line-chart'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="M3 3v18h18" /><path d="m19 9l-5 5l-4-4l-3 3" /></svg>

  <svg
    v-else-if="name === 'bell-ring'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="M10.268 21a2 2 0 0 0 3.464 0M22 8c0-2.3-.8-4.3-2-6M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326M4 2C2.8 3.7 2 5.7 2 8" /></svg>

  <svg
    v-else-if="name === 'settings'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0a2.34 2.34 0 0 0 3.319 1.915a2.34 2.34 0 0 1 2.33 4.033a2.34 2.34 0 0 0 0 3.831a2.34 2.34 0 0 1-2.33 4.033a2.34 2.34 0 0 0-3.319 1.915a2.34 2.34 0 0 1-4.659 0a2.34 2.34 0 0 0-3.32-1.915a2.34 2.34 0 0 1-2.33-4.033a2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915" /><circle cx="12" cy="12" r="3" /></svg>

  <svg
    v-else-if="name === 'user-cog'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="M10 15H6a4 4 0 0 0-4 4v2m12.305-4.47l.923-.382m0-2.296l-.923-.383m2.547-1.241l-.383-.923m.383 6.467l-.383.924m2.679-6.468l.383-.923m-.001 7.391l-.382-.924m1.624-3.92l.924-.383m-.924 2.679l.924.383" /><circle cx="18" cy="15" r="3" /><circle cx="9" cy="7" r="4" /></svg>

  <svg
    v-else-if="name === 'key'"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><path d="m15.5 7.5l2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4m2-2l-9.6 9.6" /><circle cx="7.5" cy="15.5" r="5.5" /></svg>

  <svg
    v-else
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="w-4 h-4 shrink-0"
    role="img"
    aria-hidden="true"
  ><rect width="18" height="18" x="3" y="3" rx="2" /></svg>
</template>