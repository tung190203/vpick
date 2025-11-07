<template>
    <div class="bg-white rounded-[8px] p-5 sticky top-4 space-y-6 h-fit shadow-md">
      <!-- Top Icon -->
      <div class="flex items-center justify-center mt-5 rounded-full bg-[#FFF5F5] w-20 h-20 mx-auto">
        <UserGroupIcon class="w-10 h-10 text-[#D72D36]" />
      </div>
  
      <!-- Title & Description -->
      <div class="flex flex-col items-center p-5 space-y-2">
        <h3 class="text-gray-800 font-medium text-center text-[20px]">Chia sẻ thông tin</h3>
        <p class="text-[#3E414C] text-sm text-center">
            {{ subtitle }}
        </p>
      </div>
  
      <!-- Buttons -->
      <div class="flex flex-col items-center space-y-3 w-full">
        <button
          v-for="(btn, index) in buttons"
          :key="index"
          @click="btn.onClick ? btn.onClick() : null"
          class="flex items-center justify-center gap-2 w-full bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-2 py-2 rounded transition"
        >
          {{ btn.label }}
          <component :is="btn.icon" class="w-4 h-4 inline-block ml-1" />
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
  import { UserGroupIcon, LinkIcon, QrCodeIcon, UsersIcon, UserGroupIcon as UserMultiple, ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline'
  
  const props = defineProps({
    buttons: {
      type: Array,
      required: true,
      default: () => []
    },
    subtitle: {
      type: String,
      required: false,
      default: ''
    }
  })
  </script>
  