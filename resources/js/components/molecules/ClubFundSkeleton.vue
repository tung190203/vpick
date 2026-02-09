<template>
  <div class="animate-pulse flex flex-col h-full">
    <!-- Header Section Skeleton -->
    <div class="bg-gray-200 rounded-[16px] p-8 min-h-[286px] flex flex-col justify-between">
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center space-x-3">
          <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
          <div class="h-8 w-32 bg-gray-300 rounded"></div>
        </div>
        <div class="w-12 h-12 bg-gray-300 rounded-[8px]"></div>
      </div>
      
      <div class="flex items-end justify-between">
        <div>
          <div class="h-4 w-32 bg-gray-300 rounded mb-2"></div>
          <div class="flex items-baseline space-x-2">
            <div class="h-16 w-64 bg-gray-300 rounded"></div>
            <div class="h-4 w-10 bg-gray-300 rounded"></div>
          </div>
        </div>
        
        <div class="flex items-center space-x-6">
          <div v-for="i in 2" :key="i" class="bg-gray-300 p-6 rounded-2xl min-w-[280px] h-24"></div>
        </div>
      </div>
    </div>

    <!-- Content Grid Skeleton -->
    <div class="grid grid-cols-12 gap-6 py-6 flex-1 overflow-hidden">
      <!-- Left Column Skeleton -->
      <div class="col-span-4 flex flex-col h-full overflow-hidden">
        <div class="bg-white rounded-2xl border border-gray-100 flex flex-col h-full p-6">
          <div class="h-6 w-40 bg-gray-200 rounded mb-6"></div>
          <div v-for="i in 2" :key="i" class="space-y-4 mb-8">
            <div class="flex justify-between">
              <div class="space-y-2">
                <div class="h-5 w-48 bg-gray-200 rounded"></div>
                <div class="h-4 w-32 bg-gray-100 rounded"></div>
              </div>
              <div class="h-5 w-20 bg-gray-200 rounded"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="h-10 bg-gray-100 rounded"></div>
              <div class="h-10 bg-gray-200 rounded"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column Skeleton -->
      <div class="col-span-8 flex flex-col h-full overflow-hidden">
        <div class="bg-white rounded-[24px] border border-gray-50 flex-1 flex flex-col p-6">
          <div class="h-6 w-40 bg-gray-200 rounded mb-6"></div>
          <div class="h-12 w-full bg-gray-100 rounded-md mb-6"></div>
          <div class="space-y-6">
            <div v-for="i in 5" :key="i" class="flex items-center justify-between pb-5 border-b border-gray-100">
              <div class="flex items-center space-x-4">
                <div class="space-y-2">
                  <div class="h-5 w-40 bg-gray-200 rounded"></div>
                  <div class="h-3 w-24 bg-gray-100 rounded"></div>
                </div>
              </div>
              <div class="h-5 w-20 bg-gray-200 rounded"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// No props needed for this specific skeleton
</script>
