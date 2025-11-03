<template>
  <div class="flex flex-col items-center gap-2">
    <!-- Avatar Container -->
    <div class="relative">
      <!-- Empty State -->
      <div v-if="empty"
        :class="[
          `w-${computedSize} h-${computedSize}`,
          'border-2 border-dashed border-gray-400 rounded-full flex items-center justify-center bg-white cursor-pointer hover:border-blue-500 transition-colors'
        ]">
        <PlusIcon :class="`w-${iconSize} h-${iconSize} text-gray-400`" />
      </div>

      <!-- Avatar Image -->
      <div v-else :class="`w-${computedSize} h-${computedSize} rounded-full overflow-hidden`">
        <img :src="avatar" :alt="name"
          class="w-full h-full object-cover cursor-pointer hover:scale-110 transition-transform duration-300" />
      </div>

      <!-- Rating Badge (Bottom Left) -->
      <div v-if="!empty && rating"
        :class="[
          `absolute -bottom-1 -left-1 w-${badgeSize} h-${badgeSize}`,
          'bg-blue-500 text-white font-bold rounded-full flex items-center justify-center border-2 border-white'
        ]"
        :style="{ fontSize: ratingSize + 'px' }"
        >
        {{ rating }}
      </div>

      <!-- Status Badge (Bottom Right) -->
      <div v-if="!empty && status"
        :class="[
          `absolute -bottom-1 -right-1 w-${badgeSize} h-${badgeSize} rounded-full flex items-center justify-center border-2 border-white`,
          statusColor
        ]">
        <!-- Approved Check -->
        <CheckIcon v-if="status === 'approved'" :class="`w-${iconInnerSize} h-${iconInnerSize} text-white`" />
        <!-- Pending Question -->
        <QuestionMarkCircleIcon v-else-if="status === 'pending'" :class="`w-${iconInnerSize} h-${iconInnerSize} text-white`" />
        <!-- Rejected Cross -->
         <XMarkIcon v-else-if="status === 'rejected'" :class="`w-${iconInnerSize} h-${iconInnerSize} text-white`" />
      </div>

      <!-- Edit Button (for empty state) -->
      <button v-if="empty"
        :class="[
          `absolute -bottom-1 -right-1 w-${badgeSize} h-${badgeSize}`,
          'bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-colors flex items-center justify-center border-2 border-white'
        ]">
        <PencilIcon :class="`w-${iconInnerSize} h-${iconInnerSize}`" />
      </button>
    </div>

    <!-- Name -->
    <div v-if="name" class="text-sm font-medium text-gray-700 text-center">
      {{ name }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { PlusIcon, PencilIcon, CheckIcon, QuestionMarkCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  name: {
    type: String,
    default: '',
  },
  avatar: {
    type: String,
    default: '',
  },
  rating: {
    type: [Number, String],
    default: null,
  },
  status: {
    type: String,
    default: '',
  },
  empty: {
    type: Boolean,
    default: false,
  },
  size: {
    type: Number,
    default: 16,
  },
  badgeSize: {
    type: Number,
    default: 6, // Default badge size (tailwind unit)
  },
  ratingSize: {
    type: Number,
    default: 11,
  },
})

const computedSize = computed(() => `${props.size}`)
const iconSize = computed(() => `${Math.floor(props.size / 2)}`)
const badgeSize = computed(() => `${props.badgeSize}`)
const iconInnerSize = computed(() => `${Math.floor(props.badgeSize / 1.5)}`)
const statusColor = computed(() => {
  if (props.status === 'approved') return 'bg-green-500'
  if (props.status === 'pending') return 'bg-yellow-400'
  if (props.status === 'rejected') return 'bg-red-500'
  return 'bg-gray-400'
})
</script>