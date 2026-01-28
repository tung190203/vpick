<template>
  <div 
    class="flex items-start p-4 bg-[#f6e4c8] rounded-2xl border-l-[2px] border-[#D99A43] mb-4 transition shadow-sm cursor-pointer"
  >
    <!-- Icon Section -->
    <div 
      class="flex items-center justify-center min-w-[56px] h-[56px] rounded-md bg-[#E0A243] text-white mr-4 mt-1"
    >
      <component :is="NotificationsIcon" class="w-6 h-6" />
    </div>

    <!-- Info Section -->
    <div class="flex flex-col space-y-2 flex-grow">
      <h3 class="text-xl font-semibold text-[#804D00] leading-tight">{{ title }}</h3>
      <p class="text-[#804D00] leading-relaxed">
        {{ content }}
      </p>
      <div class="flex items-center text-xs text-[#838799]">
        <span class="text-[#995C00] font-medium">{{ author }}</span>
        <span class="mx-2">•</span>
        <span class="text-[#995C00]">{{ timeAgo }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import NotificationsIcon from "@/assets/images/notifications.svg";

defineProps({
  title: {
    type: String,
    required: true
  },
  content: {
    type: String,
    required: true
  },
  author: {
    type: String,
    default: 'Admin'
  },
  timeAgo: {
    type: String,
    default: ''
  }
})
</script>

