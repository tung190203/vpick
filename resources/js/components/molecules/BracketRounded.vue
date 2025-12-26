<template>
  <div class="grid grid-cols-10 gap-4">
    <CreateMatch v-model="showCreateMatchModal" :data="detailData" :tournament="tournament" @updated="handleMatchUpdated" />

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
                <img :src="team.team_avatar || 'https://placehold.co/400x400'" class="w-8 h-8 rounded-full" alt="logo team" />
                <p class="font-medium text-sm">{{ team.team_name }}</p>
              </div>
            </div>
            <p class="font-semibold text-[20px]">{{ team.points }}</p>
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

            <div v-for="match in matches" :key="match.id" :class="[matchCardWrapperClass(match),
                { 'opacity-50': isDragging && draggedTeam?.matchId === match.id }
              ]"
              class="match-card bg-[#dcdee6] rounded-lg mb-4 w-64 flex flex-col transition-all">
              
              <div :class="matchHeaderContentClass(match)"
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
                
                <!-- HOME TEAM - DRAGGABLE -->
                <div 
                  class="flex justify-between items-center px-2 -mx-2 rounded transition-all"
                  :class="{
                    'bg-blue-100 ring-2 ring-blue-400': isDropTarget(match.id, 'home'),
                    'cursor-move hover:bg-gray-100': canDrag(match, round),
                    'cursor-pointer': !canDrag(match, round)
                  }"
                  :draggable="canDrag(match, round) ? 'true' : 'false'"
                  @dragstart="handleDragStart($event, match, 'home', round)"
                  @dragend="handleDragEnd"
                  @dragover.prevent="handleDragOver($event, match.id, 'home')"
                  @dragleave="handleDragLeave($event)"
                  @drop.prevent.stop="handleDrop($event, match.id, 'home')"
                  @click="!isDragging ? handleMatchClick(match.id) : null"
                >
                  <div class="flex items-center gap-2 pointer-events-none">
                    <img
                      :src="match.home_team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.home_team.name)}`"
                      class="w-8 h-8 rounded-full" :alt="match.home_team.name" />
                    <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team.name }}</p>
                  </div>
                  <span
  :class="scoreClass(match, 'home')"
  class="font-bold text-lg pointer-events-none"
>
  {{ match.home_score !== null ? match.home_score : "-" }}
</span>

                </div>

                <!-- AWAY TEAM - DRAGGABLE -->
                <div 
                  class="flex justify-between items-center px-2 -mx-2 rounded transition-all"
                  :class="{
                    'bg-blue-100 ring-2 ring-blue-400': isDropTarget(match.id, 'away'),
                    'cursor-move hover:bg-gray-100': canDrag(match, round),
                    'cursor-pointer': !canDrag(match, round)
                  }"
                  :draggable="canDrag(match, round) ? 'true' : 'false'"
                  @dragstart="handleDragStart($event, match, 'away', round)"
                  @dragend="handleDragEnd"
                  @dragover.prevent="handleDragOver($event, match.id, 'away')"
                  @dragleave="handleDragLeave($event)"
                  @drop.prevent.stop="handleDrop($event, match.id, 'away')"
                  @click="!isDragging ? handleMatchClick(match.id) : null"
                >
                  <div class="flex items-center gap-2 pointer-events-none">
                    <img
                      :src="match.away_team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.away_team.name)}`"
                      class="w-8 h-8 rounded-full" :alt="match.away_team.name" />
                    <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team.name }}</p>
                  </div>
                  <span
  :class="scoreClass(match, 'away')"
  class="font-bold text-lg pointer-events-none"
>
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

const emit = defineEmits(['refresh']);

const showCreateMatchModal = ref(false);
const detailData = ref({});
const isDragging = ref(false);
const draggedTeam = ref(null);
const dropTargetMatch = ref(null);
const dropTargetPosition = ref(null);

/* ===========================
   DRAG & DROP HANDLERS
=========================== */
const canDrag = (match, round) => {
  const isRound1 = parseInt(round) === 1;
  const canSwap = !['in_progress', 'completed', 'finished'].includes(match.status);
  return isRound1 && canSwap;
};

const handleDragStart = (event, match, position, round) => {
  if (!canDrag(match, round)) {
    event.preventDefault();
    return;
  }

  isDragging.value = true;
  const teamData = position === 'home' ? match.home_team : match.away_team;
  
  draggedTeam.value = {
    matchId: match.id,
    position: position,
    teamId: teamData.id,
    teamName: teamData.name,
    round: round
  };

  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', JSON.stringify(draggedTeam.value));
};

const handleDragEnd = (event) => {
  isDragging.value = false;
  draggedTeam.value = null;
  dropTargetMatch.value = null;
  dropTargetPosition.value = null;
};

const handleDragOver = (event, matchId, position) => {
  if (!draggedTeam.value) return;
  
  // Không cho drop vào chính vị trí đang drag
  if (draggedTeam.value.matchId === matchId && draggedTeam.value.position === position) {
    event.dataTransfer.dropEffect = 'none';
    return;
  }

  event.dataTransfer.dropEffect = 'move';
  dropTargetMatch.value = matchId;
  dropTargetPosition.value = position;
};

const handleDragLeave = (event) => {
  const rect = event.currentTarget.getBoundingClientRect();
  const x = event.clientX;
  const y = event.clientY;
  
  // Chỉ clear khi thực sự rời khỏi element
  if (x < rect.left || x >= rect.right || y < rect.top || y >= rect.bottom) {
    dropTargetMatch.value = null;
    dropTargetPosition.value = null;
  }
};

const handleDrop = async (event, targetMatchId, targetPosition) => {
  event.preventDefault();
  event.stopPropagation();
  if (!draggedTeam.value) return;
  if (draggedTeam.value.matchId === targetMatchId && draggedTeam.value.position === targetPosition) {
    handleDragEnd(event);
    return;
  }

  try {
    const payload = {};
    if (targetPosition === 'home') {
      payload.home_team_id = draggedTeam.value.teamId;
    } else {
      payload.away_team_id = draggedTeam.value.teamId;
    }
    const res = await MatchesService.swapTeams(targetMatchId, payload);
    
    if (res) {
      toast.success('Hoán đổi đội thành công!');
      emit('refresh');
    }
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Có lỗi xảy ra khi hoán đổi đội';
    toast.error(errorMsg);
  } finally {
    handleDragEnd(event);
  }
};

const handleMatchUpdated = () => {
    showCreateMatchModal.value = false;
    emit('refresh'); // Refresh bracket data
};

const isDropTarget = (matchId, position) => {
  return dropTargetMatch.value === matchId && dropTargetPosition.value === position;
};

/* ===========================
 GET DETAIL MATCH
=========================== */
const handleMatchClick = async (matchId) => {
  if (!matchId || isDragging.value) return;

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

/* ===========================
   COMPUTED PROPERTIES
=========================== */
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
    const numA = parseInt(a.toString().replace(/\D/g, ''));
    const numB = parseInt(b.toString().replace(/\D/g, ''));
    return numA - numB;
  });
});

/* ===========================
   STYLING HELPERS
=========================== */
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

const hasScoreInSets = (leg) => {
    if (!leg.sets) return false;
    return Object.values(leg.sets).some(setArr => 
        setArr.some(item => item.score !== null)
    );
};

const matchCardWrapperClass = (leg) => {
    if (leg.status === "pending" && hasScoreInSets(leg)) {
        return "border border-[#FBBF24] shadow-md !bg-[#FBBF24]";
    } else if (leg.status === 'completed') {
        return "border border-green-500 shadow-md bg-green-500";
    }
    return "border";
};

const matchHeaderContentClass = (leg) => {
    if (leg.status === "pending" && hasScoreInSets(leg)) {
        return 'text-white !bg-[#FBBF24]';
    } else if (leg.status === 'completed') {
        return 'text-white bg-green-500';
    }
    return 'text-[#838799]';
};

const scoreClass = (match, position) => {
  if (
    match.home_score === null ||
    match.away_score === null ||
    match.status !== 'completed'
  ) {
    return 'text-[#3E414C]';
  }

  if (match.home_score === match.away_score) {
    return 'text-[#3E414C]';
  }

  const isHomeWin = match.home_score > match.away_score;

  if (position === 'home') {
    return isHomeWin ? 'text-green-700' : 'text-red-700';
  }

  return isHomeWin ? 'text-red-700' : 'text-green-700';
};

/* ===========================
   UTILITY FUNCTIONS
=========================== */
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

<style scoped>
.custom-scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.custom-scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>