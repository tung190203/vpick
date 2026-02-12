<template>
  <div v-if="show" class="animate-pulse">
    <!-- Header Skeleton -->
    <div class="bg-gray-200 rounded-[8px] px-6 pt-4 pb-6 min-h-[290px] flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="bg-gray-300 p-5 rounded-full w-10 h-10"></div>
          <div class="flex flex-col space-y-2">
            <div class="h-8 w-48 bg-gray-300 rounded"></div>
            <div class="h-4 w-24 bg-gray-300 rounded"></div>
          </div>
        </div>
        <div class="flex space-x-2">
          <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
          <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
          <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
        </div>
      </div>

      <!-- Stats Skeleton -->
      <div class="grid grid-cols-3 gap-4 mt-8">
        <div
          v-for="i in stats"
          :key="i"
          class="bg-gray-300/50 rounded-2xl p-6 h-28"
        />
      </div>
    </div>

    <!-- Body -->
    <div class="grid grid-cols-12 gap-4 py-4">
      <div class="col-span-8 space-y-6">
        <div class="h-8 w-40 bg-gray-200 rounded"></div>
        <div v-for="i in 2" :key="i" class="h-24 bg-gray-100 rounded-xl"></div>

        <div class="h-8 w-40 bg-gray-200 rounded mt-8"></div>
        <div v-for="i in 3" :key="i" class="h-20 bg-gray-100 rounded-xl"></div>
      </div>

      <div class="col-span-4 space-y-4">
        <div class="bg-gray-100 rounded-2xl h-32 w-full"></div>
        <div class="bg-gray-100 rounded-2xl h-32 w-full"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: true,
  },
  stats: {
    type: Number,
    default: 3,
  },
})
</script>
