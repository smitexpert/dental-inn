<script setup>
/**
 * KpiStrip
 * --------
 * Responsive grid that wraps an array of tile configs and renders one
 * `<KpiTile>` per item. The strip uses the `.kpi-strip` utility from
 * app.css which sets the 1/2/3/4 column responsive breakpoints.
 */
import KpiTile from './KpiTile.vue'

defineProps({
  tiles: { type: Array, required: true },
})
</script>

<template>
  <section class="kpi-strip" aria-label="Key performance indicators">
    <KpiTile
      v-for="tile in tiles"
      :key="tile.id ?? tile.label"
      v-bind="tile"
    />
  </section>
</template>
