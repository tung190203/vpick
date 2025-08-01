<!-- components/TeamBlock.vue -->
<template>
  <div>
    <div class="bg-red-100 rounded-md p-3 mb-2 font-semibold text-red-700">{{ title }}</div>
    <PlayerCard
      v-for="(player, index) in team"
      :key="keyPrefix + '-' + index"
      :player="player"
      :onSelect="() => onPlayerSelect(index)"
    />
  </div>
</template>

<script setup>
import PlayerCard from './PlayerCard.vue'

defineProps({
  title: String,
  team: Array,
  keyPrefix: String,
  onPlayerSelect: Function
})
</script>
