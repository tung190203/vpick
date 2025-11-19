<template>
  <div class="p-4 pt-0 min-h-screen">
    <div class="overflow-x-auto">
      <div class="flex w-max min-h-full pb-4">
        <div
          v-for="(round, roundIndex) in bracket"
          :key="round.round"
          class="flex flex-col min-w-[320px] relative"
        >
          <!-- Header vòng -->
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
              <span class="text-sm text-[#838799]">20 Th7 - 8:00</span>
              <button
                class="w-9 h-9 rounded-full flex items-center justify-center border border-[#BBBFCC] hover:bg-gray-100 hover:border-[#838799] transition-colors"
              >
                <PencilIcon class="w-5 h-5 text-[#838799]" />
              </button>
            </div>
          </div>

          <!-- Các trận đấu -->
          <div class="flex flex-col items-center relative">
            <div
              v-for="(match, matchIndex) in round.matches"
              :key="match.match_id"
              :style="getMatchStyle(roundIndex, matchIndex, round.matches.length)"
              class="relative"
            >
              <div
                :class="[
                  'w-64 rounded-lg relative z-10',
                  match.legs[0].status === 'in_progress'
                    ? 'ring-2 ring-red-500'
                    : '',
                ]"
              >
                <!-- Header sân và thời gian -->
                <div
                  :class="[
                    'flex justify-between items-center text-xs font-medium px-4 py-2 rounded-t-lg',
                    match.legs[0].status === 'in_progress'
                      ? 'bg-red-500 text-white'
                      : match.is_third_place
                      ? 'bg-amber-500 text-white'
                      : 'bg-[#dcdee6] text-[#838799]',
                  ]"
                >
                  <span class="uppercase">
                    {{ match.match_label || 'SÂN 1' }}
                  </span>
                  <div class="flex items-center gap-2">
                    <span
                      v-if="match.legs[0].status === 'in_progress'"
                      class="text-white font-bold text-xs flex items-center bg-red-600 rounded-full px-2 py-0.5"
                    >
                      <VideoCameraIcon class="w-3 h-3 mr-1" />
                      Trực tiếp
                    </span>
                    <span class="text-xs">
                      {{
                        match.legs[0].scheduled_at
                          ? formatTime(match.legs[0].scheduled_at)
                          : "Chưa xác định"
                      }}
                    </span>
                  </div>
                </div>

                <!-- Thông tin đội và điểm tổng -->
                <div
                  class="flex flex-col gap-2 rounded-b-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3"
                >
                  <!-- Home team -->
                  <div class="flex flex-col gap-1">
                    <div class="flex justify-between items-center">
                      <div class="flex items-center gap-2">
                        <img
                          :src="
                            match.home_team.logo ||
                            `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(
                              match.home_team.name
                            )}`
                          "
                          class="w-8 h-8 rounded-full"
                          :alt="match.home_team.name"
                        />
                        <p class="text-sm font-semibold text-[#3E414C]">
                          {{ match.home_team.name }}
                        </p>
                      </div>
                      <span 
                        :class="[
                          'font-bold text-lg',
                          match.winner_team_id === match.home_team.id
                            ? 'text-[#D72D36]'
                            : 'text-[#3E414C]'
                        ]"
                      >
                        {{ match.aggregate_score?.home ?? 0 }}
                      </span>
                    </div>

                    <!-- Sets của Home -->
                    <div v-if="match.legs[0].sets && Object.keys(match.legs[0].sets).length > 0" class="flex gap-1 text-xs text-[#838799]">
                      <span
                        v-for="(set, index) in getTeamSets(match.legs[0].sets, match.home_team.id)"
                        :key="index"
                        class="px-2 py-0.5 bg-gray-200 rounded"
                      >
                        {{ set }}
                      </span>
                    </div>
                  </div>

                  <!-- Away team -->
                  <div class="flex flex-col gap-1">
                    <div class="flex justify-between items-center">
                      <div class="flex items-center gap-2">
                        <img
                          :src="
                            match.away_team.logo ||
                            `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(
                              match.away_team.name
                            )}`
                          "
                          class="w-8 h-8 rounded-full"
                          :alt="match.away_team.name"
                        />
                        <p class="text-sm font-semibold text-[#3E414C]">
                          {{ match.away_team.name }}
                        </p>
                      </div>
                      <span 
                        :class="[
                          'font-bold text-lg',
                          match.winner_team_id === match.away_team.id
                            ? 'text-[#D72D36]'
                            : 'text-[#3E414C]'
                        ]"
                      >
                        {{ match.aggregate_score?.away ?? 0 }}
                      </span>
                    </div>

                    <!-- Sets của Away -->
                    <div v-if="match.legs[0].sets && Object.keys(match.legs[0].sets).length > 0" class="flex gap-1 text-xs text-[#838799]">
                      <span
                        v-for="(set, index) in getTeamSets(match.legs[0].sets, match.away_team.id)"
                        :key="index"
                        class="px-2 py-0.5 bg-gray-200 rounded"
                      >
                        {{ set }}
                      </span>
                    </div>
                  </div>
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
import { computed } from "vue";
import { PencilIcon, VideoCameraIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
  bracket: {
    type: Object,
    required: true,
  },
});

const MATCH_HEIGHT = 140;
const BASE_SPACING = 20;

const bracket = computed(() => props.bracket.bracket || []);
const totalRounds = computed(() => bracket.value.length);

const getTeamInitials = (name) => {
  if (!name) return "??";
  const parts = name.split(" ");
  if (parts.length > 1) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const getTeamSets = (sets, teamId) => {
  if (!sets) return [];
  
  return Object.values(sets).map(setData => {
    const teamResult = setData.find(r => r.team_id === teamId);
    return teamResult ? teamResult.score : 0;
  });
};

const getMatchStyle = (roundIndex, matchIndex, totalMatches) => {
  // Logic giữ nguyên để đảm bảo khoảng cách giữa các trận trong mỗi vòng
  const spacing = BASE_SPACING * Math.pow(2, roundIndex);
  const topOffset = roundIndex > 0 ? spacing * Math.pow(2, roundIndex - 1) : 0;
  
  return {
    marginBottom: matchIndex < totalMatches - 1 ? `${spacing}px` : '0',
    marginTop: matchIndex === 0 && roundIndex > 0 ? `${topOffset}px` : '0',
  };
};

const formatTime = (scheduledAt) => {
  if (!scheduledAt) return "Chưa xác định";
  try {
    const date = new Date(scheduledAt);
    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");
    return `${hours}:${minutes}`;
  } catch {
    return "Chưa xác định";
  }
};
</script>

<style scoped>
/* Giữ nguyên style cho thanh cuộn */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 10px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>