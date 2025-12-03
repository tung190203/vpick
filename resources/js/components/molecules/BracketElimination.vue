<template>
  <div class="p-4 pt-0 min-h-screen overflow-x-auto">

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
          class="w-64 rounded-lg shadow-md border bg-[#EDEEF2] relative"
          :class="{
            'ring-2 ring-red-500': player.isLive,
            'bg-amber-500 text-white': player.isThirdPlace,
          }"
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
            <div class="space-y-1">
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
            <div class="space-y-1">
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
import { computed } from "vue";
import Bracket from "vue-tournament-bracket";
import { VideoCameraIcon, PencilIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
  bracket: { type: Object, required: true },
});

const totalRounds = computed(() => props.bracket.bracket.length);

/* ===========================
   FORMAT ROUNDS FOR BRACKET
=========================== */
const rounds = computed(() => {
  return props.bracket.bracket.map((round) => ({
    games: round.matches.map((match) => ({
      player1: {
        id: match.home_team.id,
        name: match.home_team.name,
        logo: match.home_team.logo,
        score: match.aggregate_score?.home,
        sets: match.legs[0].sets ? getTeamSets(match.legs[0].sets, match.home_team.id) : [],
        winnerId: match.winner_team_id,
        isLive: match.legs[0].status === "in_progress",
        isThirdPlace: !!match.is_third_place,
        label: match.match_label,
        time: match.legs[0].scheduled_at,
        isPlayer1: true,
        opponent: {
          id: match.away_team.id,
          name: match.away_team.name,
          logo: match.away_team.logo,
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