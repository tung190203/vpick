<template>
  <div class="grid grid-cols-10 gap-4">
    <CreateMatch v-model="showCreateMatchModal" :data="detailData" :tournament="tournament" @updated="handleMatchUpdated" />

    <div class="col-span-3">
  <div class="p-4 space-y-4 max-w-[450px]">
    <!-- Header -->
    <div class="flex justify-between items-center p-4 bg-[#EDEEF2] rounded-md shadow">
      <h2 class="text-lg font-bold text-gray-800">Bảng xếp hạng</h2>
      <button
        class="w-9 h-9 rounded-full shadow-lg flex items-center justify-center border border-[#BBBFCC] transition-colors duration-200 hover:bg-gray-100 hover:border-[#838799]">
        <PencilIcon class="w-5 h-5 text-[#838799] transition-colors duration-200 hover:text-black" />
      </button>
    </div>

    <!-- Rankings -->
    <div class="bg-gray-100 rounded-lg shadow overflow-hidden">
      <!-- Header Row -->
      <div class="grid grid-cols-[40px_1fr_80px_80px] bg-gray-200 px-4 py-2 text-gray-600 font-semibold text-sm">
        <span>#</span>
        <span>Đội</span>
        <span class="text-center">Điểm</span>
        <span class="text-center">Hiệu số</span>
      </div>

      <!-- Teams -->
      <div class="divide-y divide-gray-200">
        <!-- Có dữ liệu -->
        <template v-if="rank.rankings && rank.rankings.length">
          <div v-for="(team, index) in rank.rankings" :key="team.team_id"
            class="grid grid-cols-[40px_1fr_80px_80px] items-center px-4 py-3 bg-white hover:bg-blue-50 transition-colors duration-200 cursor-pointer">
            <!-- Rank -->
            <span class="text-gray-700 font-medium">{{ index + 1 }}</span>

            <!-- Team -->
            <div class="flex items-center gap-2">
              <img :src="team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(team.team_name)}`" alt="logo team" class="w-8 h-8 rounded-full border border-gray-300" />
              <p class="text-gray-800 font-medium text-sm">
                {{ team.team_name }}
              </p>
            </div>

            <!-- Points -->
            <span class="text-center font-semibold text-gray-700">
              {{ team.points }}
            </span>

            <!-- Point diff -->
            <span class="text-center font-semibold text-gray-700">
              {{ team.point_diff }}
            </span>
          </div>
        </template>

        <!-- Không có dữ liệu -->
        <div v-else class="px-4 py-6 text-center text-gray-400 italic">
          Chưa có dữ liệu bảng xếp hạng
        </div>
      </div>
    </div>
  </div>
</div>

    <div class="col-span-7 p-4 pt-0">
      <div class="overflow-x-auto h-full custom-scrollbar-hide">
        <div class="flex w-max min-h-full pb-4">
          <div v-for="roundData in bracket.bracket" :key="roundData.round"
            class="round-column flex flex-col items-center pt-4 min-w-[280px]">
            
            <div :class="roundHeaderClass(roundData.round_name, bracket.bracket.map(r => r.round_name))"
              class="flex justify-between items-center w-full mb-4 bg-[#EDEEF2] p-4">
              <h2 class="font-bold text-[#3E414C] whitespace-nowrap">{{ roundData.round_name }}</h2>
              <div class="flex items-center gap-2">
                <span class="text-sm text-[#838799]">8:00</span>
                <button
                  class="w-9 h-9 rounded-full flex items-center justify-center border border-[#BBBFCC] transition-colors duration-200 hover:bg-gray-100 hover:border-[#838799]">
                  <PencilIcon class="w-5 h-5 text-[#838799] transition-colors duration-200 hover:text-black" />
                </button>
              </div>
            </div>

            <div v-for="match in roundData.matches" :key="match.match_id" 
              :class="[matchCardWrapperClass(match),
                { 'opacity-50': isDragging && draggedTeam?.matchId === match.match_id }
              ]"
              class="match-card bg-[#dcdee6] rounded-lg mb-4 w-64 flex flex-col transition-all shadow-sm border"
            >
              
              <div :class="matchHeaderContentClass(match)"
                class="flex justify-between items-center text-xs font-medium px-4 py-2 bg-[#dcdee6] rounded-tl-md rounded-tr-md">
                <span class="uppercase">SÂN {{ match.legs?.[0]?.court || 1 }}</span>
                <div class="flex items-center gap-2">
                  <span v-if="match.status === 'in_progress'" class="text-white font-bold text-xs flex items-center">
                    <VideoCameraIcon class="w-4 h-4 mr-1" /> Trực tiếp
                  </span>
                  <span class="text-xs">{{ formatTime(match.legs?.[0]?.scheduled_at) }}</span>
                </div>
              </div>

              <div class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                
                <div v-tooltip="match.home_team.name"
                  class="flex justify-between items-center px-2 -mx-2 rounded transition-all"
                  :class="{
                    'bg-blue-100 ring-2 ring-blue-400': isDropTarget(match.match_id, 'home'),
                    'cursor-move hover:bg-gray-100': canDrag(match, roundData.round),
                    'cursor-pointer': !canDrag(match, roundData.round)
                  }"
                  :draggable="canDrag(match, roundData.round) ? 'true' : 'false'"
                  @dragstart="handleDragStart($event, match, 'home', roundData.round)"
                  @dragend="handleDragEnd"
                  @dragover.prevent="handleDragOver($event, match.match_id, 'home')"
                  @dragleave="handleDragLeave($event)"
                  @drop.prevent.stop="handleDrop($event, match.match_id, 'home')"
                  @click="!isDragging ? handleMatchClick(match) : null"
                >
                  <div class="flex items-center gap-2 pointer-events-none">
                    <img
                      :src="match.home_team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.home_team.name)}`"
                      class="w-8 h-8 rounded-full" :alt="match.home_team.name" />
                    <p class="text-sm font-semibold text-[#3E414C] truncate w-32">{{ match.home_team.name }}</p>
                  </div>
                  <span :class="scoreClass(match, 'home')" class="font-bold text-lg pointer-events-none">
                    {{ match.aggregate_score?.home ?? 0 }}
                  </span>
                </div>

                <div v-tooltip="match.away_team.name"
                  class="flex justify-between items-center px-2 -mx-2 rounded transition-all"
                  :class="{
                    'bg-blue-100 ring-2 ring-blue-400': isDropTarget(match.match_id, 'away'),
                    'cursor-move hover:bg-gray-100': canDrag(match, roundData.round),
                    'cursor-pointer': !canDrag(match, roundData.round)
                  }"
                  :draggable="canDrag(match, roundData.round) ? 'true' : 'false'"
                  @dragstart="handleDragStart($event, match, 'away', roundData.round)"
                  @dragend="handleDragEnd"
                  @dragover.prevent="handleDragOver($event, match.match_id, 'away')"
                  @dragleave="handleDragLeave($event)"
                  @drop.prevent.stop="handleDrop($event, match.match_id, 'away')"
                  @click="!isDragging ? handleMatchClick(match) : null"
                >
                  <div class="flex items-center gap-2 pointer-events-none">
                    <img
                      :src="match.away_team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.away_team.name)}`"
                      class="w-8 h-8 rounded-full" :alt="match.away_team.name" />
                    <p class="text-sm font-semibold text-[#3E414C] truncate w-32">{{ match.away_team.name }}</p>
                  </div>
                  <span :class="scoreClass(match, 'away')" class="font-bold text-lg pointer-events-none">
                    {{ match.aggregate_score?.away ?? 0 }}
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
  bracket: { type: Object, required: true },
  rank: { type: Object, required: true },
  tournament: { type: Object, required: true },
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
  const canSwap = match.status !== 'completed';
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
    matchId: match.match_id,
    position: position,
    teamId: teamData.id,
    round: round
  };
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', JSON.stringify(draggedTeam.value));
};

const handleDragEnd = () => {
  isDragging.value = false;
  draggedTeam.value = null;
  dropTargetMatch.value = null;
  dropTargetPosition.value = null;
};

const handleDragOver = (event, matchId, position) => {
  if (!draggedTeam.value) return;
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
  if (event.clientX < rect.left || event.clientX >= rect.right || event.clientY < rect.top || event.clientY >= rect.bottom) {
    dropTargetMatch.value = null;
    dropTargetPosition.value = null;
  }
};

const handleDrop = async (event, targetMatchId, targetPosition) => {
  event.preventDefault();
  if (!draggedTeam.value) return;
  
  try {
    const payload = {};
    if (targetPosition === 'home') payload.home_team_id = draggedTeam.value.teamId;
    else payload.away_team_id = draggedTeam.value.teamId;
    
    // Lưu ý: targetMatchId ở đây là match_id từ backend
    await MatchesService.swapTeams(targetMatchId, payload);
    toast.success('Hoán đổi đội thành công!');
    emit('refresh');
  } catch (error) {
    toast.error(error.response?.data?.message || 'Lỗi hoán đổi');
  } finally {
    handleDragEnd();
  }
};

/* ===========================
   MODAL LOGIC
=========================== */
const handleMatchClick = async (match) => {
  if (!match || isDragging.value) return;

  try {
    const res = await MatchesService.detailMatches(match.match_id);
    if (res) {
      detailData.value = res;
      showCreateMatchModal.value = true;
    }
  } catch (error) {
    toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy chi tiết trận đấu');
  }
};
const handleMatchUpdated = () => {
    showCreateMatchModal.value = false;
    emit('refresh');
};

const isDropTarget = (matchId, position) => {
  return dropTargetMatch.value === matchId && dropTargetPosition.value === position;
};

/* ===========================
   STYLING HELPERS
=========================== */
const roundHeaderClass = (roundName, stageRounds) => {
  const index = stageRounds.indexOf(roundName);

  if (stageRounds.length === 1) {
    return 'rounded-md border';
  } else if (index === 0) {
    return 'rounded-tl-md rounded-bl-md';
  } else if (index === stageRounds.length - 1) {
    return 'rounded-tr-md rounded-br-md';
  } else {
    return 'border-l border-r border-white';
  }
};

const matchCardWrapperClass = (match) => {
    if (match.status === 'completed') return "border-green-500 bg-green-500";
    const hasStarted = match.legs?.some(l => l.status === 'completed');
    if (hasStarted) return "border-yellow-500 bg-yellow-50";
    return "border-gray-200";
};

const matchHeaderContentClass = (match) => {
    if (match.status === 'completed') return 'text-white bg-green-500';
    if (match.legs?.some(l => l.status === 'completed')) return 'text-white bg-yellow-500';
    return 'text-[#838799]';
};

const scoreClass = (match, position) => {
  const s = match.aggregate_score;
  if (!s || match.status !== 'completed') return 'text-[#3E414C]';
  if (s.home === s.away) return 'text-[#3E414C]';
  const isHomeWin = s.home > s.away;
  if (position === 'home') return isHomeWin ? 'text-green-700' : 'text-red-700';
  return isHomeWin ? 'text-red-700' : 'text-green-700';
};

/* ===========================
   UTILITY
=========================== */
const getTeamInitials = (name) => {
  if (!name) return "??";
  return name.split(" ").map(n => n[0]).join("").toUpperCase().substring(0, 2);
};

const formatTime = (scheduledAt) => {
  if (!scheduledAt) return "08:00";
  const d = new Date(scheduledAt);
  return d.getHours().toString().padStart(2, '0') + ":" + d.getMinutes().toString().padStart(2, '0');
};
</script>

<style scoped>
.custom-scrollbar-hide::-webkit-scrollbar { display: none; }
.custom-scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>