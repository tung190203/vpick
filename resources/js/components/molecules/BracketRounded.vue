<template>
  <div class="grid grid-cols-10 gap-4">
    <CreateMatch v-model="showCreateMatchModal" :data="detailData" :tournament="tournament" />

    <div class="col-span-3 p-4">
      <div class="flex justify-between items-center p-4 mb-4 bg-[#EDEEF2] rounded-md">
        <h2 class="font-bold text-[#3E414C]">Bảng xếp hạng</h2>
        <button
          class="w-9 h-9 rounded-full shadow-lg flex items-center justify-center border border-[#BBBFCC] transition-colors duration-200 hover:bg-gray-100 hover:border-[#838799]">
          <PencilIcon class="w-5 h-5 text-[#838799] transition-colors duration-200 hover:text-black" />
        </button>
      </div>

      <div class="rounded-md bg-[#dcdee6] shadow-md border border-[#dcdee6] mx-2">
        <div class="flex justify-between items-center px-4 py-2 text-[#838799]">
          <p class="font-semibold text-sm">Đội</p>
          <p class="font-semibold text-sm">Điểm</p>
        </div>
        <div class="rounded-md bg-[#EDEEF2]">
          <div v-for="(team, index) in rank.rankings" :key="team.team_id"
            class="px-4 py-2 flex justify-between items-center text-[#6B6F80] hover:text-[#4392E0] hover:bg-blue-100 cursor-pointer"
            :class="{ 'rounded-tl-md rounded-tr-md': index === 0 }">
            <div class="flex items-center gap-3">
              <span class="text-sm">{{ index + 1 }}</span>
              <div class="flex items-center gap-2">
                <img :src="team.logo || 'https://placehold.co/400x400'" class="w-8 h-8 rounded-full" alt="logo team" />
                <p class="font-medium text-sm">{{ team.team_name }}</p>
              </div>
            </div>
            <p class="font-semibold text-[20px]">{{ team.total_set_points }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- MATCHES COLUMN -->
    <div class="col-span-7 p-4 pt-0">
      <div class="overflow-x-auto h-full custom-scrollbar-hide">
        <div class="flex w-max min-h-full pb-4">
          <div v-for="(matches, round) in groupedMatches" :key="round"
            class="round-column flex flex-col items-center pt-4 min-w-[280px]">
            <div :class="roundHeaderClass(round)"
              class="flex justify-between items-center w-full mb-4 bg-[#EDEEF2] p-4">
              <h2 class="font-bold text-[#3E414C] whitespace-nowrap">Vòng {{ round }}</h2>
              <div class="flex items-center gap-2">
                <span class="text-sm text-[#838799]">20 Th7 - 8:00</span>
                <button
                  class="w-9 h-9 rounded-full flex items-center justify-center border border-[#BBBFCC] transition-colors duration-200 hover:bg-gray-100 hover:border-[#838799]">
                  <PencilIcon class="w-5 h-5 text-[#838799] transition-colors duration-200 hover:text-black" />
                </button>
              </div>
            </div>

            <div v-for="match in matches" :key="match.index" :class="matchCardWrapperClass(match.status)"
              class="match-card bg-[#dcdee6] rounded-lg mb-4 w-64 flex flex-col cursor-pointer hover:shadow-lg transition-all"
              @click="handleMatchClick(match.id)">
              <div :class="matchHeaderContentClass(match.status)"
                class="flex justify-between items-center text-xs font-medium text-[#838799] px-4 py-2 bg-[#dcdee6] rounded-tl-lg rounded-tr-lg">
                <span class="uppercase">SÂN 1</span>
                <div class="flex items-center gap-2">
                  <span v-if="match.status === 'in_progress'" class="text-white font-bold text-xs flex items-center">
                    <VideoCameraIcon class="w-4 h-4 mr-1" /> Trực tiếp
                  </span>
                  <span class="text-xs">{{ formatTime(match.scheduled_at) }}</span>
                </div>
              </div>

              <div class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-2">
                    <img
                      :src="match.home_team.logo || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.home_team.name)}`"
                      class="w-8 h-8 rounded-full" :alt="match.home_team.name" />
                    <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team.name }}</p>
                  </div>
                  <span :class="scoreClass(match.status)" class="font-bold text-lg">
                    {{ match.home_score !== null ? match.home_score : "-" }}
                  </span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-2">
                    <img
                      :src="match.away_team.logo || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.away_team.name)}`"
                      class="w-8 h-8 rounded-full" :alt="match.away_team.name" />
                    <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team.name }}</p>
                  </div>
                  <span :class="scoreClass(match.status)" class="font-bold text-lg">
                    {{ match.away_score !== null ? match.away_score : "-" }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { PencilIcon, VideoCameraIcon } from "@heroicons/vue/24/solid";
import CreateMatch from '@/components/molecules/CreateMatch.vue';
import * as MatchesService from '@/service/match.js';
import { toast } from 'vue3-toastify';

const props = defineProps({
  bracket: {
    type: Object,
    required: true,
  },
  rank: {
    type: Object,
    required: true,
  },
  tournament: {
    type: Object,
    required: true,
  },
});

const showCreateMatchModal = ref(false);
const detailData = ref({});

/* ===========================
 GET DETAIL MATCH
=========================== */
const handleMatchClick = async (matchId) => {
  if (!matchId) return;

  try {
    const res = await MatchesService.detailMatches(matchId);
    if (res) {
      detailData.value = res;
      showCreateMatchModal.value = true;
    }
  } catch (error) {
    toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy chi tiết trận đấu');
  }
};

const groupedMatches = computed(() => {
  if (!props.bracket || !props.bracket.matches) {
    return {};
  }

  return props.bracket.matches.reduce((acc, match) => {
    const roundKey = match.round;
    if (!acc[roundKey]) {
      acc[roundKey] = [];
    }
    acc[roundKey].push(match);
    return acc;
  }, {});
});

const roundKeys = computed(() => {
  return Object.keys(groupedMatches.value).sort((a, b) => {
    const numA = parseInt(a.replace('Vòng ', ''));
    const numB = parseInt(b.replace('Vòng ', ''));
    return numA - numB;
  });
});

const roundHeaderClass = (roundName) => {
  const keys = roundKeys.value;
  const index = keys.indexOf(roundName);
  if (keys.length === 1) {
    return 'rounded-md';
  } else if (index === 0) {
    return 'rounded-tl-md rounded-bl-md';
  } else if (index === keys.length - 1) {
    return 'rounded-tr-md rounded-br-md border-l border-white';
  }

  return 'border-l border-white';
};

const matchCardWrapperClass = (status) => {
  if (status === "in_progress") {
    return "border border-red-500 shadow-md bg-red-100";
  }
  return "";
};

const matchHeaderContentClass = (status) => {
  if (status === 'in_progress') {
    return 'text-red-500';
  }
  return 'text-[#838799]';
}

const scoreClass = (status) => {
  if (status === "in_progress") {
    return "text-red-500";
  }
  return "text-[#3E414C]";
};

const getTeamInitials = (name) => {
  if (!name) return "??";
  const parts = name.split(" ");
  if (parts.length > 1) {
      return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const formatTime = (scheduledAt) => {
  if (!scheduledAt) return "8:00";
  try {
      const date = new Date(scheduledAt);
      const hours = date.getHours().toString().padStart(2, '0');
      const minutes = date.getMinutes().toString().padStart(2, '0');
      return `${hours}:${minutes}`;
  } catch {
      return "8:00";
  }
};
</script>

<style>
.custom-scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.custom-scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>