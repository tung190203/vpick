<!-- components/PlayerCard.vue -->
<template>
    <div class="flex items-center justify-between p-2 border rounded mb-2 bg-white shadow-sm">
      <div class="flex items-center gap-2">
        <img v-if="player.avatar" :src="player.avatar" class="w-8 h-8 rounded-full object-cover" />
        <div v-else class="w-8 h-8 bg-gray-300 rounded-full"></div>
        <div>
          <div class="font-medium text-sm">{{ player.name || 'Chọn người chơi' }}</div>
          <div class="text-xs text-gray-500">PICKI {{ player.vndupr || '--' }}</div>
        </div>
      </div>
      <button class="text-sm text-primary border border-primary px-2 py-1 rounded" @click="onSelect">
        Chọn
      </button>
    </div>
  </template>
  
  <script setup>
  defineProps({
    player: Object,
    onSelect: Function
  })
  </script>
  