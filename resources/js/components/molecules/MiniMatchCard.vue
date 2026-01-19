<template>
    <div>
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-baseline gap-3">
          <label class="relative flex items-baseline cursor-pointer group" @click.stop>
              <input type="checkbox" class="sr-only peer" :checked="selected" @change="emit('update:selected', !selected)" :disabled="!selectable"/>
              <span
              class="relative w-4 h-4 border-2 border-gray-400 rounded-full bg-white transition-all duration-200
                    peer-checked:bg-[#D72D36] peer-checked:border-[#D72D36]
                    group-hover:border-gray-500 peer-checked:group-hover:bg-[#D72D36]
                    after:content-[''] after:absolute after:hidden after:left-[4px] after:top-[1px]
                    after:w-[4px] after:h-[8px] after:border-white after:border-r-[2px] after:border-b-[2px]
                    after:rotate-45 peer-checked:after:block"
            ></span>
          </label>
          <div class="w-28">
            <h3 class="font-semibold text-gray-800">{{ matchTitle }}</h3>
            <p class="text-sm text-gray-500">{{ matchTime }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2 text-gray-500">
          <CalendarIcon class="w-4 h-4" />
          <span class="text-sm font-medium">{{ courtName }}</span>
        </div>
      </div>

      <!-- Match Teams -->
      <div class="grid grid-cols-[1fr_auto_1fr] gap-4 items-center mb-6" @click.stop>
        <!-- Team 1 -->
          <div class="rounded-lg p-3" :class="teamWinId === team1[0].team_id ? 'border-2 border-red-400 bg-red-50' : 'bg-gray-100'" @click.stop>
          <div class="flex justify-between gap-2">
            <UserCard
              v-for="player in team1"
              :key="player.id"
              :name="player.name"
              :avatar="player.avatar"
              :rating="player.rating"
              :status="player.status"
              :size="11"
              :badgeSize="5"
              :ratingSize="7"
            />
              <div
                  v-if="miniMatchType !== MATCH_TYPE_SINGLE && team1.length < 2"
                  class="w-11 h-11 border-2 border-dashed border-gray-400 bg-[#EDEEF2] cursor-pointer rounded-full flex items-center justify-center">
                  <PlusIcon class="w-11 h-11 text-gray-400"/>
              </div>
          </div>
        </div>

        <!-- VS -->
        <div class="flex justify-center">
          <span class="text-[1rem] font-bold text-gray-400">VS</span>
        </div>

        <!-- Team 2 -->
      <div class="rounded-lg p-3" :class="teamWinId === team2[0].team_id  ? 'border-2 border-red-400 bg-red-50' : 'bg-gray-100'" @click.stop>
          <div class="flex justify-between gap-2">
            <UserCard
              v-for="player in team2"
              :key="player.id"
              :name="player.name"
              :avatar="player.avatar"
              :rating="player.rating"
              :status="player.status"
              :size="11"
              :badgeSize="5"
              :ratingSize="7"
            />
            <div
              v-if="miniMatchType !== MATCH_TYPE_SINGLE && team2.length < 2"
              class="w-11 h-11 border-2 border-dashed border-gray-400 bg-[#EDEEF2] cursor-pointer rounded-full flex items-center justify-center">
              <PlusIcon class="w-11 h-11 text-gray-400"/>
            </div>
          </div>
        </div>
      </div>

      <!-- Set Scores -->
      <div class="grid grid-cols-3 gap-4">
        <div
          v-for="(set, index) in sets"
          :key="index"
          class="text-center"
        >
          <div class="flex items-center justify-center gap-1 mb-1">
            <span class="text-[#6AAAEB] font-medium text-xs">Set</span>
            <div class="bg-[#d2e5fa] text-[#6AAAEB] border-2 border-[#6AAAEB] rounded px-2 py-0.5 text-xs font-semibold min-w-[24px]">
              {{ index + 1 }}
            </div>
          </div>
          <div class="text-[1rem] font-bold text-[#004D99]">
            {{ set.team1 }}-{{ set.team2 }}
          </div>
        </div>
      </div>
    </div>
</template>

<script setup>
import { CalendarIcon, PlusIcon } from '@heroicons/vue/24/outline'
import UserCard from '@/components/molecules/UserCard.vue'

const MATCH_TYPE_SINGLE = 2

const props = defineProps({
    matchTitle: String,
    matchTime: String,
    courtName: String,
    miniMatchType: Number,
    teamWinId: Number,
    team1: Array,
    team2: Array,
    sets: Array,

    selected: {
        type: Boolean,
        default: false
    },
    selectable: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['update:selected'])
</script>
