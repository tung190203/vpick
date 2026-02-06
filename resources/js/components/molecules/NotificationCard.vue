<template>
  <div 
    class="relative flex items-start p-4 rounded-2xl border-l-[2px] mb-4 transition shadow-sm cursor-pointer"
    :class="[currentColors.cardBg, currentColors.border]"
  >
    <!-- Pin Button - Floating at top right corner -->
    <button 
      v-if="data.is_pinned" 
      @click.stop="$emit('unpin', data.id)" 
      class="absolute -top-2 -right-2 transition-all transform hover:scale-110 group z-10"
      title="Bỏ ghim"
    >
      <PinIcon class="w-5 h-5 transform rotate-45 transition-transform group-hover:rotate-12" :class="currentColors.title" />
    </button>

    <!-- Icon Section -->
    <div 
      class="flex items-center justify-center min-w-[56px] h-[56px] rounded-md text-white mr-4 mt-1"
      :class="currentColors.iconBg"
    >
      <component :is="currentIcon" class="w-6 h-6" />
    </div>

    <!-- Info Section -->
    <div class="flex flex-col space-y-2 flex-grow">
      <div class="flex items-start justify-between">
        <h3 class="text-xl font-semibold leading-tight pr-8" :class="currentColors.title">{{ data.title }}</h3>
      </div>
      <p class="leading-relaxed" :class="currentColors.content">
        {{ data.content }}
      </p>
      <div class="flex items-center text-xs space-x-1" :class="currentColors.subText">
        <span class="font-medium">Đăng bởi {{ data?.creator?.full_name }}</span>
        <span class="w-1 h-1 rounded-full" :class="currentBg"></span>
        <span>{{ getJoinedDate(data?.created_at || data?.sent_at) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { getJoinedDate } from '@/composables/formatDatetime.js'
import { NOTIFICATION_COLOR_MAP, NOTIFICATION_ICON_MAP, NOTIFICATION_BG_MAP } from '@/data/club/index.js'
import PinIcon from '@/assets/images/pin_icon.svg'

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['unpin'])

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