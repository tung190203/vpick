<template>
  <div 
    class="flex items-start p-4 rounded-2xl border-l-[2px] mb-4 transition shadow-sm cursor-pointer"
    :class="[currentColors.cardBg, currentColors.border]"
  >
    <!-- Icon Section -->
    <div 
      class="flex items-center justify-center min-w-[56px] h-[56px] rounded-md text-white mr-4 mt-1"
      :class="currentColors.iconBg"
    >
      <component :is="currentIcon" class="w-6 h-6" />
    </div>

    <!-- Info Section -->
    <div class="flex flex-col space-y-2 flex-grow">
      <h3 class="text-xl font-semibold leading-tight" :class="currentColors.title">{{data.title }}</h3>
      <p class="leading-relaxed" :class="currentColors.content">
        {{ data.content }}
      </p>
      <div class="flex items-center text-xs space-x-1" :class="currentColors.subText">
        <span class="font-medium">Đăng bởi {{ data?.creator?.full_name }}</span>
        <span class="w-1 h-1 rounded-full" :class="currentBg"></span>
        <span>{{ getJoinedDate(data?.sent_at) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { getJoinedDate } from '@/composables/formatDatetime.js'
import { NOTIFICATION_COLOR_MAP, NOTIFICATION_ICON_MAP, NOTIFICATION_BG_MAP } from '@/data/club/index.js'

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
})

const colorMap = NOTIFICATION_COLOR_MAP;

const iconMap = NOTIFICATION_ICON_MAP;

const bgMap = NOTIFICATION_BG_MAP;

const currentColors = computed(() => {
  return colorMap[props.data.club_notification_type_id] || colorMap[1]
})

const currentIcon = computed(() => {
  return iconMap[props.data.club_notification_type_id] || iconMap[1]
})

const currentBg = computed(() => {
  return bgMap[props.data.club_notification_type_id] || bgMap[1]
})
</script>

