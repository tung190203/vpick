<template>
  <div 
    class="flex flex-col p-4 bg-white rounded-lg shadow-sm border-l-[2px] mb-4 transition gap-4 cursor-pointer hover:shadow-md hover:border-l-4"
    @click="$emit('click-card')"
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
            <div v-if="address" class="flex items-center space-x-1.5">
               <MapPinIcon class="w-4 h-4 flex-shrink-0" />
               <span class="truncate max-w-[200px]" :title="address">{{ address }}</span>
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

      <div class="flex flex-col items-end space-y-4">
       <!-- Status Badge -->
       <span 
          v-if="status"
          class="text-[10px] font-bold px-2 py-0.5 rounded uppercase whitespace-nowrap h-fit"
          :class="{
              'bg-[#E3F7EF] text-[#2D9B71]': status === 'open',
              'bg-[#F2F7FC] text-[#4392E0]': status === 'private',
              'bg-[#EDEEF2] text-[#838799]': !['open', 'private'].includes(status),
          }"
        >
        {{ statusText }}
      </span>

      <!-- Creator Edit Button -->
      <Button 
        v-if="isCreator"
        size="md" 
        color="secondary"
        class="font-bold px-3 py-3 rounded-[8px]"
        :disabled="disabled"
        @click.stop="$emit('edit')"
      >
        <PencilIcon class="w-5 h-5 text-[#3E414C]" />
      </Button>
      </div>
    </div>

    <!-- Action Section -->
    <div v-if="!isCreator" class="w-full flex items-center justify-between gap-2" @click.stop>
      <!-- Pending Approval -->
      <Button v-if="registrationStatus === 'pending'"
        size="md" 
        color="secondary"
        class="w-full font-semibold text-sm py-2 rounded-md flex justify-center bg-[#EDEEF2] text-[#3E414C] border border-[#DCDEE6] shadow-sm"
        :disabled="disabled"
        @click="$emit('cancel-join')"
      >
        <div class="flex items-center gap-2">
          <ClockIcon class="w-4 h-4" />
          <span>{{ pendingText }}</span>
        </div>
      </Button>

      <!-- Accepted / Participant -->
      <template v-else-if="registrationStatus === 'accepted'">
        <Button 
          size="md" 
          color="secondary"
          class="w-full font-semibold text-sm py-2 rounded-md flex justify-center"
          :disabled="disabled"
          @click="$emit('cancel-join')"
        >
          Huỷ tham gia
        </Button>
        <Button 
          size="md" 
          color="primary"
          class="w-full font-semibold text-sm py-2 rounded-md flex justify-center"
          :disabled="disabled"
          @click="$emit('check-in')"
        >
          Check-in
        </Button>
      </template>

      <!-- Not Registered -->
      <Button v-else
        size="md" 
        :color="buttonColor"
        class="w-full font-semibold text-sm py-2 rounded-md flex justify-center"
        :disabled="disabled"
        @click="$emit('register')"
      >
        {{ buttonText }}
      </Button>
    </div>
  </div>
</template>

<script setup>
import { ClockIcon, UsersIcon, MapPinIcon, PencilIcon } from '@heroicons/vue/24/outline'
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
  address: {
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
  },
  isCreator: {
    type: Boolean,
    default: false
  },
  registrationStatus: {
    type: String,
    default: 'none' // 'none', 'pending', 'accepted'
  },
  pendingText: {
    type: String,
    default: 'Đang chờ duyệt'
  }
})

defineEmits(['click-card', 'edit', 'register', 'cancel-join', 'check-in'])

const buttonColor = computed(() => {
  if (props.type === 'danger') return 'danger'
  if (props.type === 'primary') return 'primary'
  return 'secondary'
})
</script>
