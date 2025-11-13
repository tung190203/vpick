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
              {{ getRoundName(round.round) }}
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
                    'flex justify-between items-center text-xs font-medium px-4 py-2 bg-[#dcdee6] rounded-t-lg',
                    match.legs[0].status === 'in_progress'
                      ? 'text-red-500'
                      : 'text-[#838799]',
                  ]"
                >
                  <span class="uppercase">SÂN 1</span>
                  <div class="flex items-center gap-2">
                    <span
                      v-if="match.legs[0].status === 'in_progress'"
                      class="text-white font-bold text-xs flex items-center bg-red-500 rounded-full px-2 py-0.5"
                    >
                      <VideoCameraIcon class="w-3 h-3 mr-1" />
                      Trực tiếp
                    </span>
                    <span class="text-xs">
                      {{
                        match.legs[0].scheduled_at
                          ? formatTime(match.legs[0].scheduled_at)
                          : "8:00"
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
                      <span class="font-bold text-lg text-[#3E414C]">
                        {{ match.legs[0].home_score !== null ? match.legs[0].home_score : "-" }}
                      </span>
                    </div>

                    <!-- Sets của Home -->
                    <div class="flex gap-1 text-xs text-[#838799]">
                      <span
                        v-for="(set, index) in match.legs[0].sets ? Object.values(match.legs[0].sets).map(s => s.find(r => r.team_id === match.home_team.id).score) : []"
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
                      <span class="font-bold text-lg text-[#3E414C]">
                        {{ match.legs[0].away_score !== null ? match.legs[0].away_score : "-" }}
                      </span>
                    </div>

                    <!-- Sets của Away -->
                    <div class="flex gap-1 text-xs text-[#838799]">
                      <span
                        v-for="(set, index) in match.legs[0].sets ? Object.values(match.legs[0].sets).map(s => s.find(r => r.team_id === match.away_team.id).score) : []"
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
  
  const getRoundName = (roundNumber) => {
    const roundMap = {
      1: "Vòng 1",
      2: "Vòng 2",
      3: "Vòng 3",
    };
    return roundMap[roundNumber] || `Vòng ${roundNumber}`;
  };
  
  const getTeamInitials = (name) => {
    if (!name) return "??";
    const parts = name.split(" ");
    if (parts.length > 1) {
      return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
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
    if (!scheduledAt) return "8:00";
    try {
      const date = new Date(scheduledAt);
      const hours = date.getHours().toString().padStart(2, "0");
      const minutes = date.getMinutes().toString().padStart(2, "0");
      return `${hours}:${minutes}`;
    } catch {
      return "8:00";
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