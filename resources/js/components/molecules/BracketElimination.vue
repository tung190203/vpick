<template>
  <div class="p-4 pt-0 min-h-screen overflow-x-auto">
    <CreateMatch v-model="showCreateMatchModal" :data="detailData" :tournament="tournament" @updated="handleMatchUpdated" />

    <!-- HEADER CÁC ROUND -->
    <div class="flex w-max min-h-full pb-4">

      <div
        v-for="(round, roundIndex) in bracket.bracket"
        :key="roundIndex"
        class="flex flex-col min-w-[320px] relative"
      >
        <div
          :class="[
            'flex justify-between items-center w-full mb-6 bg-[#EDEEF2] p-4 sticky top-0 z-10',
            roundIndex === 0
              ? 'rounded-l-md'
              : roundIndex === totalRounds - 1
              ? 'rounded-r-md border-l border-white'
              : 'border-l border-white',
          ]"
        >
          <h2 class="font-bold text-[#3E414C] whitespace-nowrap">
            {{ round.round_name }}
          </h2>

          <div class="flex items-center gap-2">
            <span class="text-sm text-[#838799]">
              {{ formatRoundDate(round.date) }}
            </span>

            <button
              class="w-9 h-9 rounded-full flex items-center justify-center border border-[#BBBFCC] hover:bg-gray-100 hover:border-[#838799] transition-colors"
            >
              <PencilIcon class="w-5 h-5 text-[#838799]" />
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- BRACKET -->
    <Bracket :rounds="rounds">
      <template #player="{ player }">
        <div
          v-if="player.isPlayer1"
          class="w-64 rounded-lg shadow-md border bg-[#EDEEF2] relative cursor-pointer hover:shadow-lg transition-all"
          :class="{
            'ring-2 ring-red-500': player.isLive,
            'bg-amber-500 text-white': player.isThirdPlace,
            'opacity-50': isDragging && draggedTeam?.matchId === player.matchId,
          }"
          @click="handleMatchClick(player.matchId)"
        >
          <div
            class="flex justify-between items-center text-xs font-medium rounded-t-lg px-4 py-2"
            :class="{
              'bg-red-500 text-white': player.isLive,
              'bg-[#dcdee6] text-[#838799]': !player.isLive && !player.isThirdPlace,
            }"
          >
            <span class="uppercase">{{ player.label || 'SÂN 1' }}</span>

            <div class="flex items-center gap-2">
              <span v-if="player.isLive"
                class="text-white font-bold text-xs flex items-center bg-red-600 rounded-full px-2 py-0.5">
                <VideoCameraIcon class="w-3 h-3 mr-1" />
                Trực tiếp
              </span>
              <span class="text-xs">{{ player.time ? formatTime(player.time) : "Chưa xác định" }}</span>
            </div>
          </div>

          <!-- TEAMS -->
          <div class="px-4 space-y-1">

            <!-- HOME TEAM -->
            <div 
              class="space-y-1 rounded transition-colors"
              :class="{
                'bg-blue-100': isDropTarget(player.matchId, 'home'),
                'cursor-move': player.roundNumber === 1 && !player.isLive
              }"
              :draggable="player.roundNumber === 1 && !player.isLive"
              @dragstart="handleDragStart($event, player, 'home')"
              @dragend="handleDragEnd"
              @dragover.prevent="handleDragOver($event, player.matchId, 'home')"
              @dragleave="handleDragLeave"
              @drop="handleDrop($event, player.matchId, 'home')"
            >
              <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                  <img :src="player.logo || placeholderFor(player.name)" class="w-8 h-8 rounded-full" />
                  <p class="text-sm font-semibold text-[#3E414C]">{{ player.name }}</p>
                </div>

                <span class="font-bold text-lg"
                  :class="player.id === player.winnerId ? 'text-[#D72D36]' : 'text-[#3E414C]'">
                  {{ player.score ?? 0 }}
                </span>
              </div>
            </div>

            <!-- AWAY TEAM -->
            <div 
              class="space-y-1 rounded transition-colors"
              :class="{
                'bg-blue-100': isDropTarget(player.matchId, 'away'),
                'cursor-move': player.roundNumber === 1 && !player.isLive
              }"
              :draggable="player.roundNumber === 1 && !player.isLive"
              @dragstart="handleDragStart($event, player, 'away')"
              @dragend="handleDragEnd"
              @dragover.prevent="handleDragOver($event, player.matchId, 'away')"
              @dragleave="handleDragLeave"
              @drop="handleDrop($event, player.matchId, 'away')"
            >
              <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                  <img :src="player.opponent.logo || placeholderFor(player.opponent.name)" class="w-8 h-8 rounded-full" />
                  <p class="text-sm font-semibold text-[#3E414C]">{{ player.opponent.name }}</p>
                </div>

                <span class="font-bold text-lg"
                  :class="player.opponent.id === player.winnerId ? 'text-[#D72D36]' : 'text-[#3E414C]'">
                  {{ player.opponent.score ?? 0 }}
                </span>
              </div>
            </div>

          </div>
        </div>
      </template>
    </Bracket>

  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import Bracket from "vue-tournament-bracket";
import { VideoCameraIcon, PencilIcon } from "@heroicons/vue/24/solid";
import CreateMatch from '@/components/molecules/CreateMatch.vue';
import * as MatchesService from '@/service/match.js';
import { toast } from 'vue3-toastify';

const props = defineProps({
  bracket: { type: Object, required: true },
  tournament: { type: Object, required: true },
});
const emit = defineEmits(['refresh']);
const showCreateMatchModal = ref(false);
const detailData = ref({});
const isDragging = ref(false);
const draggedTeam = ref(null);
const dropTargetMatch = ref(null);
const dropTargetPosition = ref(null);

const totalRounds = computed(() => props.bracket.bracket.length);

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
   DRAG & DROP HANDLERS
=========================== */
const handleDragStart = (event, player, position) => {
  // Chỉ cho phép drag ở round 1 và khi trận chưa bắt đầu
  if (player.roundNumber !== 1 || player.isLive) {
    event.preventDefault();
    return;
  }

  isDragging.value = true;
  draggedTeam.value = {
    matchId: player.matchId,
    position: position, // 'home' hoặc 'away'
    teamId: position === 'home' ? player.id : player.opponent.id,
    teamName: position === 'home' ? player.name : player.opponent.name,
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
  
  // Không cho drop vào chính vị trí đang drag
  if (draggedTeam.value.matchId === matchId && draggedTeam.value.position === position) {
    event.dataTransfer.dropEffect = 'none';
    return;
  }

  event.dataTransfer.dropEffect = 'move';
  dropTargetMatch.value = matchId;
  dropTargetPosition.value = position;
};

const handleDragLeave = () => {
  dropTargetMatch.value = null;
  dropTargetPosition.value = null;
};

const handleDrop = async (event, targetMatchId, targetPosition) => {
  event.preventDefault();
  
  if (!draggedTeam.value) return;

  // Không cho swap với chính mình
  if (draggedTeam.value.matchId === targetMatchId && draggedTeam.value.position === targetPosition) {
    handleDragEnd();
    return;
  }

  try {
    // Gọi API swap teams
    const payload = {};
    if (targetPosition === 'home') {
      payload.home_team_id = draggedTeam.value.teamId;
    } else {
      payload.away_team_id = draggedTeam.value.teamId;
    }

    const res = await MatchesService.swapTeams(targetMatchId, payload);
    
    if (res) {
      toast.success('Hoán đổi đội thành công!');
      // Refresh bracket data
      emit('refresh');
    }
  } catch (error) {
    toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi hoán đổi đội');
  } finally {
    handleDragEnd();
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
   FORMAT ROUNDS FOR BRACKET
=========================== */
const rounds = computed(() => {
  return props.bracket.bracket.map((round, roundIndex) => ({
    games: round.matches.map((match) => ({
      player1: {
        id: match.home_team.id,
        name: match.home_team.name,
        logo: match.home_team.team_avatar,
        score: match.aggregate_score?.home,
        sets: match.legs[0].sets ? getTeamSets(match.legs[0].sets, match.home_team.id) : [],
        winnerId: match.winner_team_id,
        isLive: match.legs[0].status === "in_progress",
        isThirdPlace: !!match.is_third_place,
        label: match.match_label,
        time: match.legs[0].scheduled_at,
        isPlayer1: true,
        matchId: match.match_id,
        roundNumber: roundIndex + 1,
        opponent: {
          id: match.away_team.id,
          name: match.away_team.name,
          logo: match.away_team.team_avatar,
          score: match.aggregate_score?.away,
          sets: match.legs[0].sets ? getTeamSets(match.legs[0].sets, match.away_team.id) : [],
        }
      },
      player2: { isPlayer1: false },
    })),
  }));
});

/* ================================
   UTILS
================================ */
const getTeamSets = (sets, teamId) =>
  Object.values(sets).map((s) => s.find((r) => r.team_id === teamId)?.score ?? 0);

const formatTime = (t) => new Date(t).toLocaleTimeString("vi-VN", { hour: "2-digit", minute: "2-digit" });

const formatRoundDate = (d) => {
  if (!d) return "Chưa xác định";

  const date = new Date(d);
  if (isNaN(date.getTime())) return "Chưa xác định";

  const day = date.toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "short",
  });

  const time = date.toLocaleTimeString("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
  });

  return `${day} - ${time}`;
};

const placeholderFor = (name) => {
  const parts = name?.split(" ") || [];
  const initials =
    parts.length > 1 ? parts[0][0] + parts.pop()[0] : name?.slice(0, 2) || "??";
  return `https://placehold.co/40x40/BBBFCC/3E414C?text=${initials.toUpperCase()}`;
};
</script>

<style scoped>
.overflow-x-auto::-webkit-scrollbar {
  height: 0px;
}
</style>