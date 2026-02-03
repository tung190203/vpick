<template>
  <div 
    class="flex flex-col p-4 bg-white rounded-lg shadow-sm border-l-[2px] mb-4 transition gap-4"
    :class="[
      disabled ? 'border-gray-200 opacity-70 grayscale-[0.5]' : (
        type === 'danger' ? 'border-red-500' : 
        type === 'primary' ? 'border-blue-500' : 'border-[#BBBFCC]'
      )
    ]"
  >
    <div class="flex items-start justify-between w-full">
      <div class="flex items-start space-x-3">
        <!-- Date Section -->
        <div 
          class="flex flex-col items-center justify-center w-[72px] h-[72px] rounded-lg border"
          :class="[
            type === 'danger' ? 'bg-red-50 border-[#EB969B] text-[#D72D36]' : 
            type === 'primary' ? 'bg-blue-50 border-[#6AAAEB] text-[#4392E0]' : 'bg-gray-50 border-[#BBBFCC] text-[#838799]'
          ]"
        >
          <span class="text-xs font-bold uppercase">{{ day }}</span>
          <span class="text-3xl font-bold">{{ date }}</span>
        </div>

        <!-- Info Section -->
        <div class="flex flex-col space-y-1 pt-1">
          <h3 class="font-semibold text-[#3E414C] leading-tight">{{ title }}</h3>
          <div class="flex flex-col space-y-1 text-xs text-[#838799]">
            <div class="flex items-center space-x-1.5">
              <ClockIcon class="w-4 h-4" />
              <span>{{ time }}</span>
            </div>
            <div class="flex items-center space-x-1.5">
               <UsersIcon class="w-4 h-4" />
               <span>{{ participants }}</span>
            </div>
            <div v-if="countdown" class="flex items-center space-x-1.5 text-blue-600 font-semibold">
              <ClockIcon class="w-4 h-4" />
              <span>Bắt đầu sau: {{ countdown }}</span>
            </div>
          </div>
        </div>
      </div>

       <!-- Status Badge -->
       <span 
          v-if="status"
          class="text-[11px] font-bold px-2.5 py-1 rounded uppercase whitespace-nowrap"
          :class="{
              'bg-[#E3F7EF] text-[#2D9B71]': status === 'open',
              'bg-[#F2F7FC] text-[#4392E0]': status === 'private',
              'bg-[#EDEEF2] text-[#838799]': !['open', 'private'].includes(status),
          }"
        >
        {{ statusText }}
      </span>
    </div>

    <!-- Action Section -->
    <div class="w-full flex space-x-2">
    <Button v-if="status === 'private'"
        size="md" 
        color="secondary"
        class="w-full font-semibold text-sm py-2 rounded-md flex justify-center"
        :disabled="disabled"
      >
        Huỷ tham gia
      </Button>
      <Button 
        size="md" 
        :color="buttonColor"
        class="w-full font-semibold text-sm py-2 rounded-md flex justify-center"
        :disabled="disabled"
      >
        {{ buttonText }}
      </Button>
    </div>
  </div>
</template>

<script setup>
import { ClockIcon, UsersIcon, MapPinIcon } from '@heroicons/vue/24/outline'
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
  statusText: {
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
  },
  countdown: {
    type: String,
    default: ''
  }
})

const buttonColor = computed(() => {
  if (props.type === 'danger') return 'danger'
  if (props.type === 'primary') return 'primary'
  return 'secondary'
})
</script>
