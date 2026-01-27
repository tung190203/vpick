<template>
  <div 
    class="flex items-center justify-between p-4 bg-white rounded-lg shadow-sm border-l-[2px] mb-4 transition"
    :class="[
      disabled ? 'border-gray-200 opacity-70 grayscale-[0.5]' : (
        type === 'danger' ? 'border-red-500' : 
        type === 'primary' ? 'border-blue-500' : 'border-[#BBBFCC]'
      )
    ]"
  >
    <div class="flex items-center space-x-4">
      <!-- Date Section -->
      <div 
        class="flex flex-col items-center justify-center w-16 h-16 rounded-md border"
        :class="[
          type === 'danger' ? 'bg-red-50 border-[#EB969B] text-[#D72D36]' : 
          type === 'primary' ? 'bg-blue-50 border-[#6AAAEB] text-[#4392E0]' : 'bg-gray-50 border-[#BBBFCC] text-[#838799]'
        ]"
      >
        <span class="text-xs font-bold uppercase">{{ day }}</span>
        <span class="text-2xl font-bold">{{ date }}</span>
      </div>

      <!-- Info Section -->
      <div class="flex flex-col space-y-1">
        <h3 class="text-xl font-semi-bold text-[#3E414C]">{{ title }}</h3>
        <div class="flex items-center space-x-4 text-sm text-[#838799]">
          <div class="flex items-center space-x-1">
            <ClockIcon class="w-4 h-4" />
            <span>{{ time }}</span>
          </div>
          <div v-if="location" class="flex items-center space-x-1">
            <div class="w-1 h-1 bg-[#838799] rounded-full"></div>
            <span>{{ location }}</span>
          </div>
        </div>
        <div class="flex items-center space-x-1 text-sm text-[#838799]">
            <UsersIcon class="w-4 h-4" />
            <span>{{ participants }}</span>
        </div>
      </div>
    </div>

    <!-- Action Section -->
    <div class="flex flex-col items-end space-y-2">
      <span 
        v-if="status"
        class="text-[10px] font-bold px-2 py-0.5 rounded uppercase"
        :class="{
            'bg-[#E3F7EF] text-[#2D9B71]': status === 'open',
            'bg-[#F2F7FC] text-[#4392E0]': status === 'private',
            'bg-[#EDEEF2] text-[#838799]': !['open', 'private'].includes(status),
        }"
      >
        {{ status }}
      </span>
      <Button 
        size="md" 
        :color="buttonColor"
        class="font-bold px-6 py-2 rounded-md"
        :disabled="disabled"
      >
        {{ buttonText }}
      </Button>
    </div>
  </div>
</template>

<script setup>
import { ClockIcon, UsersIcon } from '@heroicons/vue/24/outline'
import Button from '@/components/atoms/Button.vue'
import { computed } from 'vue'

const props = defineProps({
  day: {
    type: String,
    required: true
  },
  date: {
    type: String,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  time: {
    type: String,
    required: true
  },
  location: {
    type: String,
    default: ''
  },
  participants: {
    type: String,
    required: true
  },
  status: {
    type: String,
    default: ''
  },
  buttonText: {
    type: String,
    default: 'Đăng ký'
  },
  type: {
    type: String,
    default: 'primary' // 'danger', 'primary', 'secondary'
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const buttonColor = computed(() => {
  if (props.type === 'danger') return 'danger'
  if (props.type === 'primary') return 'primary'
  return 'secondary'
})
</script>
