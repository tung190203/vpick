<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div 
      v-if="modelValue" 
      class="fixed inset-0 z-[10001] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md transform-gpu" 
      @click.self="close"
    >
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 scale-95 translateY(20px)"
        enter-to-class="opacity-100 scale-100 translateY(0)"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100 translateY(0)"
        leave-to-class="opacity-0 scale-95 translateY(20px)"
      >
        <div 
          v-if="modelValue && notification"
          class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative overflow-hidden flex flex-col max-h-[90vh]"
        >
          <!-- Header Strip with Icon -->
          <div :class="['h-24 flex items-center px-8 relative overflow-hidden', currentColors.cardBg]">
             <div class="absolute inset-0 opacity-10 pointer-events-none">
                <div class="absolute inset-0 bg-repeat bg-center opacity-10"
                    style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;20&quot; height=&quot;20&quot; viewBox=&quot;0 0 20 20&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cpath d=&quot;M0 0h10v10H0zM10 10h10v10H10z&quot; fill=&quot;%23000000&quot; fill-opacity=&quot;1&quot;/%3E%3C/svg%3E')">
                </div>
            </div>
            
            <div 
              class="w-12 h-12 rounded-xl flex items-center justify-center shadow-md relative z-10"
              :class="currentColors.iconBg"
            >
              <component :is="currentIcon" class="w-6 h-6 text-white" />
            </div>
            <div class="ml-5 relative z-10">
              <span class="text-[10px] font-bold uppercase tracking-widest opacity-60" :class="currentColors.title">Thông báo</span>
              <h2 class="text-xl font-bold leading-tight" :class="currentColors.title">{{ notification.title }}</h2>
            </div>
            
            <button @click="close" class="absolute top-4 right-4 p-2 rounded-full hover:bg-black/5 transition-colors text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-6 h-6" stroke-width="2" />
            </button>
          </div>

          <!-- Content Area -->
          <div class="p-8 overflow-y-auto custom-scrollbar flex-1">
            <!-- Author Info -->
            <div class="flex items-center space-x-3 mb-6">
              <img :src="notification.creator?.avatar_url || 'https://ui-avatars.com/api/?name=' + notification.creator?.full_name" class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm" />
              <div>
                <p class="font-bold text-[#3E414C] text-[15px]">{{ notification.creator?.full_name }}</p>
                <div class="flex items-center text-[12px] text-[#838799] space-x-2">
                  <span>{{ getJoinedDate(notification.sent_at || notification.created_at) }}</span>
                  <span v-if="notification.is_pinned" class="flex items-center text-[#D72D36] font-semibold">
                    <span class="mx-1">•</span>
                    <PinIcon class="w-3.5 h-3.5 mr-1" />
                    Đã ghim
                  </span>
                </div>
              </div>
            </div>

            <!-- Body Text -->
            <div class="prose prose-slate max-w-none mb-8 text-[#3E414C] text-[14px] leading-relaxed whitespace-pre-wrap">
              {{ notification.content }}
            </div>

            <!-- Attachment -->
            <div v-if="notification.attachment_url" class="mt-6 border-t border-gray-100 pt-6">
              <h3 class="text-base font-bold text-[#3E414C] mb-3 flex items-center">
                <PaperClipIcon class="w-4 h-4 mr-2 text-[#D72D36]" />
                Đính kèm
              </h3>
              <div class="group relative rounded-xl overflow-hidden border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                <img v-if="isImage(notification.attachment_url)" :src="notification.attachment_url" class="w-full h-auto max-h-[300px] object-contain bg-gray-50" />
                <div v-else class="p-6 bg-gray-50 flex items-center">
                  <DocumentIcon class="w-8 h-8 text-gray-400 mr-4" />
                  <span class="text-gray-600 font-medium truncate">{{ getFileName(notification.attachment_url) }}</span>
                  <a :href="notification.attachment_url" download target="_blank" class="ml-auto bg-white border border-gray-200 px-4 py-2 rounded-xl text-sm font-bold text-[#3E414C] hover:bg-gray-100 transition-colors">
                    Tải về
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Actions -->
          <div v-if="isAdmin" class="p-5 bg-gray-50 border-t border-gray-100 flex justify-end space-x-3">
            <button 
              @click="$emit('unpin', notification.id)" 
              v-if="notification.is_pinned"
              class="flex items-center px-5 py-2 rounded-lg border border-gray-200 text-[#3E414C] text-sm font-semibold hover:bg-white transition-colors"
            >
              <PinIcon class="w-4 h-4 mr-2" />
              Bỏ ghim
            </button>
            <button 
              @click="$emit('pin', notification.id)" 
              v-else
              class="flex items-center px-5 py-2 rounded-lg border border-gray-200 text-[#3E414C] text-sm font-semibold hover:bg-white transition-colors"
            >
              <PinIcon class="w-4 h-4 mr-2" />
              Ghim ngay
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'
import { XMarkIcon, PaperClipIcon, DocumentIcon } from '@heroicons/vue/24/outline'
import PinIcon from '@/assets/images/pin_icon.svg'
import { getJoinedDate } from '@/composables/formatDatetime.js'
import { NOTIFICATION_COLOR_MAP, NOTIFICATION_ICON_MAP } from '@/data/club/index.js'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  notification: {
    type: Object,
    default: null
  },
  isAdmin: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'close', 'pin', 'unpin'])

const close = () => {
  emit('update:modelValue', false)
  emit('close')
}

const currentColors = computed(() => {
  if (!props.notification) return NOTIFICATION_COLOR_MAP[1]
  return NOTIFICATION_COLOR_MAP[props.notification.club_notification_type_id] || NOTIFICATION_COLOR_MAP[1]
})

const currentIcon = computed(() => {
  if (!props.notification) return NOTIFICATION_ICON_MAP[1]
  return NOTIFICATION_ICON_MAP[props.notification.club_notification_type_id] || NOTIFICATION_ICON_MAP[1]
})

const isImage = (url) => {
  if (!url) return false
  const extension = url.split('.').pop().toLowerCase()
  return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(extension)
}

const getFileName = (url) => {
  if (!url) return ''
  return url.split('/').pop()
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #E5E7EB;
  border-radius: 20px;
}
</style>
