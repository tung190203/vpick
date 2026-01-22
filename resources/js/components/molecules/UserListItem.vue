<template>
    <div
      @click="$emit('select', user)"
      :class="[
        'border rounded-lg cursor-pointer transition-all overflow-hidden flex h-fit p-2 items-center gap-3',
        user.id === selected
          ? 'border-red-500 shadow-md ring-1 ring-red-500'
          : 'border-gray-200 shadow-sm'
      ]"
    >
      <UserCard
        :avatar="user.avatar_url"
        :show-hover-delete="false"
        :rating="getUserRating(user)"
        :defaultImage="defaultImage"
      />
  
      <div class="flex-1 min-w-0 flex flex-col justify-start gap-1">
        <div class="flex justify-start items-center gap-2">
          <h3
            class="font-semibold text-gray-900 text-base leading-tight truncate"
            v-tooltip="user.full_name"
          >
            {{ user.full_name }}
          </h3>
  
          <span
            class="px-2 py-1 rounded text-xs font-medium capitalize whitespace-nowrap"
            :class="{
              'bg-green-100 text-green-700': user.visibility === 'open',
              'bg-yellow-100 text-yellow-700': user.visibility === 'friend-only',
              'bg-red-100 text-red-700': user.visibility === 'private'
            }"
          >
            {{ getVisibilityText(user.visibility) }}
          </span>
        </div>
  
        <div class="flex items-center gap-1.5 text-xs text-gray-600 truncate">
          <img v-if="user.gender == 1" :src="maleIcon" class="w-4 h-4" />
          <img v-else-if="user.gender == 2" :src="femaleIcon" class="w-4 h-4" />
          <span class="truncate">
            {{ user.gender_text || 'Khác' }}
            {{ user.age_group ? ' • ' + user.age_group : '' }}
          </span>
        </div>
      </div>
  
      <div class="flex-shrink-0 w-1/4">
        <p class="text-xs text-[#207AD5] line-clamp-2 break-words" v-tooltip="user.address">
          {{ user.address }}
        </p>
      </div>
    </div>
  </template>
  
  <script setup>
    import UserCard from '@/components/molecules/UserCard.vue';
  defineProps({
    user: {
      type: Object,
      required: true
    },
    selected: [String, Number],
    defaultImage: String,
    maleIcon: String,
    femaleIcon: String,
    getUserRating: {
      type: Function,
      required: true
    },
    getVisibilityText: {
      type: Function,
      required: true
    }
  })
  
  defineEmits(['select'])
  </script>
  