<!-- components/PlayerSelectPopup.vue -->
<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
      <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-lg m-5">
        <button @click="onClose" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
          <XMarkIcon class="h-6 w-6" />
        </button>
        <h3 class="mb-4 text-lg font-semibold text-gray-800 text-center">Chọn người chơi</h3>
  
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
          <input v-model="search" type="text" placeholder="Tìm kiếm người chơi..."
            class="w-full rounded-md border py-2 pl-10 pr-4 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
        </div>
  
        <ul class="mt-4 max-h-60 overflow-y-auto divide-y divide-gray-100">
          <li v-for="p in filtered" :key="p.name"
            class="cursor-pointer flex items-center gap-3 px-4 py-2 hover:bg-red-50 rounded"
            @click="() => onSelect(p)">
            <img :src="p.avatar" class="w-8 h-8 rounded-full object-cover" alt=""/>
            <div class="flex flex-col">
              <span class="font-medium text-gray-700">{{ p.name }}</span>
              <span class="text-sm text-gray-500">VNDUPR {{ p.vndupr }}</span>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, watch } from 'vue'
  import { XMarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
  
  const props = defineProps({
    show: Boolean,
    players: Array,
    selectedPlayers: Array,
    onClose: Function,
    onSelect: Function
  })
  
  const search = ref('')
  const filtered = computed(() => {
    return props.players.filter(p =>
      (!props.selectedPlayers.includes(p.name)) &&
      p.name.toLowerCase().includes(search.value.toLowerCase())
    )
  })
  
  watch(() => props.show, () => {
    search.value = ''
  })
  </script>
  