<template>
  <div class="fixed bottom-3 right-3 flex flex-col items-center space-y-3 z-[9998]">
    <div class="relative cursor-pointer group">
      <img
        src="https://i.pravatar.cc/100?img=1"
        alt="avatar1"
        class="w-12 h-12 rounded-full border-white shadow-md"
      />

      <!-- Icon X: hover mới hiện -->
      <button
        class="absolute -top-1.5 -right-1.5 
               bg-gray-300 hover:bg-gray-400 text-white
               rounded-full w-5 h-5 flex items-center justify-center text-xs shadow
               opacity-0 scale-75
               group-hover:opacity-100 group-hover:scale-100
               transition-all duration-200"
      >
        <XMarkIcon class="w-3 h-3" />
      </button>

      <!-- Badge online -->
      <span class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border border-white rounded-full"></span>
    </div>
  </div>
</template>
<script setup>
import { XMarkIcon } from "@heroicons/vue/24/outline";
</script>