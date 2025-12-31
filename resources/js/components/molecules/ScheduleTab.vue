<template>
    <div class="flex items-center justify-between border-b border-[#BBBFCC] px-3 py-4 mb-4" v-if="isCreator">
        <p class="font-semibold uppercase">Người chơi tự nhập điểm</p>
        <button @click="$emit('handleToggle')"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
            :class="toggle ? 'bg-[#D72D36]' : 'bg-gray-300'">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                :class="toggle ? 'translate-x-6' : 'translate-x-1'" />
        </button>
    </div>

    <div class="flex justify-start gap-2 mb-4">
        <button v-for="tab in scheduleTabs" :key="tab.id" @click="scheduleActiveTab = tab.id" :class="[
            'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
            scheduleActiveTab === tab.id
                ? 'bg-[#D72D36] text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        ]">
            {{ tab.label }}
        </button>
    </div>

    <template v-if="scheduleActiveTab === 'ranking'">
  <template v-if="data.tournament_types?.[0]?.format === 2 || data.tournament_types?.[0]?.format === 3">
    <!-- BXH không group -->
    <div v-if="rank && rank.rankings" class="p-4 space-y-4">
      <div class="bg-gray-100 rounded-lg shadow overflow-hidden">
        <!-- Header -->
        <div class="grid grid-cols-[40px_1fr_80px_80px] bg-gray-200 px-4 py-2 text-gray-600 font-semibold text-sm">
          <span>#</span>
          <span>Đội</span>
          <span class="text-center">Điểm</span>
          <span class="text-center">Hiệu số</span>
        </div>

        <!-- Teams -->
        <div class="divide-y divide-gray-200">
          <div v-for="(team, index) in rank.rankings" :key="team.team_id"
               class="grid grid-cols-[40px_1fr_80px_80px] items-center px-4 py-3 bg-white hover:bg-blue-50 transition-colors duration-200 cursor-pointer">
            <span class="text-gray-700 font-medium">{{ index + 1 }}</span>
            <div class="flex items-center gap-2">
              <img :src="team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(team.team_name)}`" alt="logo team"
                   class="w-8 h-8 rounded-full border border-gray-300" />
              <p class="text-gray-800 font-medium text-sm">{{ team.team_name }}</p>
            </div>
            <span class="text-center font-semibold text-gray-700">{{ team.points }}</span>
            <span class="text-center font-semibold text-gray-700">{{ team.point_diff }}</span>
          </div>
        </div>
      </div>
    </div>
  </template>

  <template v-else-if="data.tournament_types?.[0]?.format === 1">
    <!-- BXH group (Mixed format) -->
    <div v-for="group in rank.group_rankings" :key="group.group_id" class="p-4 space-y-4">
      <div class="bg-gray-100 rounded-lg shadow overflow-hidden">
        <!-- Group Header -->
        <div class="grid grid-cols-[40px_1fr_80px_80px] bg-gray-200 px-4 py-2 text-gray-600 font-semibold text-sm">
          <span>#</span>
          <span>{{ group.group_name }}</span>
          <span class="text-center">Điểm</span>
          <span class="text-center">Hiệu số</span>
        </div>

        <!-- Teams -->
        <div class="divide-y divide-gray-200">
          <div v-for="(team, index) in group.rankings" :key="team.team_id"
               class="grid grid-cols-[40px_1fr_80px_80px] items-center px-4 py-3 bg-white hover:bg-blue-50 transition-colors duration-200 cursor-pointer">
            <span class="text-gray-700 font-medium">{{ index + 1 }}</span>
            <div class="flex items-center gap-2">
              <img :src="team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(team.team_name)}`" alt="logo team"
                   class="w-8 h-8 rounded-full border border-gray-300" />
              <p class="text-gray-800 font-medium text-sm">{{ team.team_name }}</p>
            </div>
            <span class="text-center font-semibold text-gray-700">{{ team.points }}</span>
            <span class="text-center font-semibold text-gray-700">{{ team.point_diff }}</span>
          </div>
        </div>
      </div>
    </div>
  </template>

  <template v-else>
    <p class="text-center text-gray-500">Không có dữ liệu bảng xếp hạng.</p>
  </template>
</template>


    <template v-else-if="scheduleActiveTab === 'matches'">
        <!-- Mixed Format (format === 1) -->
        <template v-if="data.tournament_types?.[0]?.format === 1">
            <template v-if="mixedBracket.pool_stage || mixedBracket.knockout_stage">
                <!-- Stage Tabs -->
                <div class="flex justify-start gap-2 mb-6">
                    <button 
                        v-for="stage in mixedStages" 
                        :key="stage.id" 
                        @click="currentMixedStage = stage.id"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            currentMixedStage === stage.id
                                ? 'bg-[#D72D36] text-white shadow-md border'
                                : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                        ]">
                        {{ stage.label }}
                    </button>
                </div>

                <!-- Pool Stage -->
                <template v-if="currentMixedStage === 'pool' && mixedBracket.pool_stage">
                    <div v-for="group in mixedBracket.pool_stage" :key="group.group_id" class="mb-6">
                        <div class="bg-[#EDEEF2] px-4 py-3 rounded-lg mb-4">
                            <h3 class="font-bold text-[#3E414C]">{{ group.group_name }}</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 px-2 cursor-pointer">
                            <div v-for="match in group.matches" :key="match.match_id" @click="getDetailMatches(match.match_id)">
                                <div v-for="leg in match.legs" :key="leg.id"
                                    class="match-card bg-[#dcdee6] rounded-lg w-full flex flex-col mb-3">
                                    <div
                                        class="flex justify-between items-center text-xs font-medium text-[#838799] px-4 py-2 bg-[#dcdee6] rounded-tl-lg rounded-tr-lg">
                                        <span class="uppercase">SÂN 1 - Lượt {{ leg.leg === 1 ? 'đi' : 'về' }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs">
                                                {{ leg.scheduled_at ? formatDate(leg.scheduled_at) : 'Chưa xác định' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center gap-2">
                                                <img :src="match.home_team.logo || 'https://placehold.co/40x40'"
                                                    class="w-8 h-8 rounded-full object-cover" :alt="match.home_team.name" />
                                                <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team.name }}</p>
                                            </div>
                                            <span class="font-bold text-lg text-[#3E414C]">
                                                {{ leg.home_score ?? 0 }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center gap-2">
                                                <img :src="match.away_team.logo || 'https://placehold.co/40x40'"
                                                    class="w-8 h-8 rounded-full object-cover" :alt="match.away_team.name" />
                                                <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team.name }}</p>
                                            </div>
                                            <span class="font-bold text-lg text-[#3E414C]">
                                                {{ leg.away_score ?? 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Knockout Stage -->
                <template v-if="currentMixedStage === 'knockout' && mixedBracket.knockout_stage">
                    <div v-if="currentKnockoutRound" class="mb-6 cursor-pointer">
                        <div class="grid grid-cols-2 items-center mb-4 uppercase px-2">
                            <p v-if="currentKnockoutRound.matches.length === 1" class="text-sm font-semibold">
                            {{ currentKnockoutRound.matches[0].is_third_place == 1 ? 'Tranh hạng 3' : currentKnockoutRound.round_name }} •
                            {{ currentKnockoutRound.matches.length }} trận đấu
                            </p>

                            <p v-else class="text-sm font-semibold">
                            {{ currentKnockoutRound.round_name }} • {{ currentKnockoutRound.matches.length }} trận đấu
                            </p>

                            <p class="text-sm font-semibold text-right">
                                {{ getKnockoutStatusText(currentKnockoutRound.matches) }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 px-2">
                            <div v-for="match in currentKnockoutRound.matches" :key="match.match_id" @click="getDetailMatches(match.match_id)"
                                :class="[
                                    'match-card rounded-lg w-full flex flex-col bg-[#dcdee6]'
                                ]">
                                <div
                                    :class="[
                                        'flex justify-between items-center text-xs font-medium px-4 py-2 rounded-tl-lg rounded-tr-lg bg-[#dcdee6] text-[#838799]'
                                    ]">
                                    <span class="uppercase">
                                        {{ match.match_label || `Trận ${match.match_id}` }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs">
                                            {{ match.legs[0]?.scheduled_at ? formatDate(match.legs[0].scheduled_at) : 'Chưa xác định' }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-2">
                                            <img :src="match.home_team.logo || 'https://placehold.co/40x40'"
                                                class="w-8 h-8 rounded-full object-cover" :alt="match.home_team.name" />
                                            <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team.name }}</p>
                                        </div>
                                        <span 
                                            :class="[
                                                'font-bold text-lg',
                                                match.winner_team_id === match.home_team.id ? 'text-[#D72D36]' : 'text-[#3E414C]'
                                            ]">
                                            {{ match.aggregate_score?.home ?? 0 }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-2">
                                            <img :src="match.away_team.logo || 'https://placehold.co/40x40'"
                                                class="w-8 h-8 rounded-full object-cover" :alt="match.away_team.name" />
                                            <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team.name }}</p>
                                        </div>
                                        <span 
                                            :class="[
                                                'font-bold text-lg',
                                                match.winner_team_id === match.away_team.id ? 'text-[#D72D36]' : 'text-[#3E414C]'
                                            ]">
                                            {{ match.aggregate_score?.away ?? 0 }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Knockout Pagination -->
                    <div v-if="mixedBracket.knockout_stage && mixedBracket.knockout_stage.length > 1" class="flex justify-center items-center gap-4 mt-4">
                        <button @click="previousKnockoutRound" :disabled="!hasPreviousKnockoutRound" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            hasPreviousKnockoutRound
                                ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer'
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed border'
                        ]">
                            ← Vòng trước
                        </button>

                        <span class="text-sm text-gray-600">
                            Vòng {{ currentKnockoutRoundIndex + 1 }} / {{ mixedBracket.knockout_stage.length }}
                        </span>

                        <button @click="nextKnockoutRound" :disabled="!hasNextKnockoutRound" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            hasNextKnockoutRound
                                ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer'
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed border'
                        ]">
                            Vòng sau →
                        </button>
                    </div>
                </template>
            </template>

            <template v-else>
                <p class="text-center text-gray-500">Không có trận đấu nào.</p>
            </template>
        </template>

        <!-- Elimination Format (format === 2) -->
        <template v-else-if="data.tournament_types?.[0]?.format === 2">
            <template v-if="eliminationBracket && eliminationBracket.length > 0">
                <div v-if="currentEliminationRound" class="mb-6 cursor-pointer">
                    <div class="grid grid-cols-2 items-center mb-4 uppercase px-2">
                        <p class="text-sm font-semibold">{{ currentEliminationRound.round_name }} • {{
                            currentEliminationRound.matches.length }} trận đấu</p>
                        <p class="text-sm font-semibold text-right">
                            {{ getEliminationStatusText(currentEliminationRound.matches) }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 px-2">
                        <div v-for="match in currentEliminationRound.matches" :key="match.match_id" @click="getDetailMatches(match.match_id)"
                            :class="[
                                'match-card rounded-lg w-full flex flex-col',
                                match.is_third_place ? 'bg-amber-100 border-2 border-amber-500' : 'bg-[#dcdee6]'
                            ]">
                            <div
                                :class="[
                                    'flex justify-between items-center text-xs font-medium px-4 py-2 rounded-tl-lg rounded-tr-lg',
                                    match.is_third_place ? 'bg-amber-500 text-white' : 'bg-[#dcdee6] text-[#838799]'
                                ]">
                                <span class="uppercase">
                                    {{ match.match_label || (match.is_third_place ? 'Tranh hạng 3' : `Trận ${match.match_id}`) }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs">
                                        {{ match.legs[0]?.scheduled_at ? formatDate(match.legs[0].scheduled_at) : 'Chưa xác định' }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.home_team.logo || 'https://placehold.co/40x40'"
                                            class="w-8 h-8 rounded-full object-cover" :alt="match.home_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team.name }}</p>
                                    </div>
                                    <span class="font-bold text-lg" :class="{
                                        'text-[#D72D36]': match.winner_team_id === match.home_team.id
                                    }">
                                        {{ match.aggregate_score.home }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.away_team.logo || 'https://placehold.co/40x40'"
                                            class="w-8 h-8 rounded-full object-cover" :alt="match.away_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team.name }}</p>
                                    </div>
                                    <span class="font-bold text-lg" :class="{
                                        'text-[#D72D36]': match.winner_team_id === match.away_team.id
                                    }">
                                        {{ match.aggregate_score.away }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center items-center gap-4 mt-4">
                    <button @click="previousEliminationRound" :disabled="!hasPreviousEliminationRound" :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                        hasPreviousEliminationRound
                            ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        ]">
                        ← Vòng trước
                    </button>

                    <span class="text-sm text-gray-600">
                        Vòng {{ currentEliminationRoundIndex + 1 }} / {{ eliminationBracket.length }}
                    </span>

                    <button @click="nextEliminationRound" :disabled="!hasNextEliminationRound" :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                        hasNextEliminationRound
                            ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        ]">
                        Vòng sau →
                    </button>
                </div>
            </template>

            <template v-else>
                <p class="text-center text-gray-500">Không có trận đấu nào.</p>
            </template>
        </template>

        <!-- Round Robin Format (format === 3) -->
        <template v-else>
            <template v-if="matches.length > 0">
                <div v-if="currentRoundMatches.length > 0" class="mb-6">
                    <div class="grid grid-cols-2 items-center mb-4 uppercase px-2">
                        <p class="text-sm font-semibold">Vòng {{ currentRound }} • {{ currentRoundMatches.length }} trận
                            đấu</p>
                        <p class="text-sm font-semibold text-right">
                            {{ getStatusText(currentRoundMatches) }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 px-2 cursor-pointer">
                        <div v-for="match in currentRoundMatches" :key="match.id"
                            class="match-card bg-[#dcdee6] rounded-lg w-full flex flex-col" @click="getDetailMatches(match.id)">
                            <div
                                class="flex justify-between items-center text-xs font-medium text-[#838799] px-4 py-2 bg-[#dcdee6] rounded-tl-lg rounded-tr-lg">
                                <span class="uppercase">SÂN {{ match.court }}</span>
                                <div class="flex items-center gap-2">
                                    <span v-if="match.scheduled_at" class="text-xs">{{ formatDate(match.scheduled_at)
                                        }}</span>
                                    <span v-else class="text-xs">Chưa xác định</span>
                                </div>
                            </div>

                            <div
                                class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.home_team.logo || 'https://placehold.co/40x40'"
                                            class="w-8 h-8 rounded-full object-cover" :alt="match.home_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team.name }}</p>
                                    </div>
                                    <span class="font-bold text-lg" :class="{
                                        'text-[#D72D36]': match.is_completed && match.home_score > match.away_score
                                    }">
                                        {{ match.home_score }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.away_team.logo || 'https://placehold.co/40x40'"
                                            class="w-8 h-8 rounded-full object-cover" :alt="match.away_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team.name }}</p>
                                    </div>
                                    <span class="font-bold text-lg" :class="{
                                        'text-[#D72D36]': match.is_completed && match.away_score > match.home_score
                                    }">
                                        {{ match.away_score }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center items-center gap-4 mt-4">
                    <button @click="previousRound" :disabled="!hasPreviousRound" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            hasPreviousRound
                                ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer'
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        ]">
                        ← Vòng trước
                    </button>

                    <span class="text-sm text-gray-600">
                        Vòng {{ currentRound }} / {{ totalRounds }}
                    </span>

                    <button @click="nextRound" :disabled="!hasNextRound" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            hasNextRound
                                ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer'
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        ]">
                        Vòng sau →
                    </button>
                </div>
            </template>

            <template v-else>
                <p class="text-center text-gray-500">Không có trận đấu nào.</p>
            </template>
        </template>
    </template>
    <CreateMatch v-model="showCreateMatchModal" :data="detailData" :tournament="data" />
</template>

<script setup>
import CreateMatch from '@/components/molecules/CreateMatch.vue'
import { ref, watch, computed } from 'vue'
import { SCHEDULE_TABS } from '@/data/tournament/index.js'
import { VideoCameraIcon } from "@heroicons/vue/24/solid";
import { toast } from 'vue3-toastify';
import * as TournamentTypeService from '@/service/tournamentType.js'
import * as MatchesService from '@/service/match.js'

const scheduleTabs = SCHEDULE_TABS
const scheduleActiveTab = ref('ranking')
const matches = ref([])
const currentRound = ref('1')
const showCreateMatchModal = ref(false)
const detailData = ref({});

// Elimination bracket data
const eliminationBracket = ref([])
const currentEliminationRoundIndex = ref(0)

// Mixed bracket data
const mixedBracket = ref({})
const currentMixedStage = ref('pool')
const currentKnockoutRoundIndex = ref(0)

const mixedStages = [
    { id: 'pool', label: 'Vòng bảng' },
    { id: 'knockout', label: 'Vòng loại trực tiếp' }
]

const props = defineProps({
    isCreator: {
        type: Boolean,
        default: false
    },
    toggle: {
        type: Boolean,
        required: true
    },
    rank: {
        type: Object,
        required: true
    },
    data: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['handleToggle'])

// Lấy danh sách trận đấu từ API
const getMatches = async (tournamentTypeId) => {
    try {
        const response = await TournamentTypeService.getBracketByTournamentTypeId(tournamentTypeId)

        // Check format to determine data structure
        if (props.data.tournament_types?.[0]?.format === 1) {
            // Mixed format - pool_stage and knockout_stage
            mixedBracket.value = {
                pool_stage: response.pool_stage || [],
                knockout_stage: response.knockout_stage || []
            }
            currentMixedStage.value = 'pool'
            currentKnockoutRoundIndex.value = 0
        } else if (props.data.tournament_types?.[0]?.format === 2) {
            // Elimination format - bracket structure
            eliminationBracket.value = response.bracket || []
            currentEliminationRoundIndex.value = 0
        } else {
            // Round Robin format - matches array
            matches.value = response.matches || []

            // Set current round to first round when data loads
            if (matches.value.length > 0) {
                const rounds = [...new Set(matches.value.map(m => m.round))].sort((a, b) => {
                    return parseInt(a) - parseInt(b)
                })
                if (rounds.length > 0) {
                    currentRound.value = rounds[0]
                }
            }
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Lấy trận thi đấu thất bại');
    }
}

const getDetailMatches = async (id) => {
    try {
        const res = await MatchesService.detailMatches(id);
        if(res) {
            detailData.value = res
            showCreateMatchModal.value = true;
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi thực hiện thao tác này');
    }
}

// ========== MIXED FORMAT COMPUTED PROPERTIES ==========

// Lấy vòng knockout hiện tại
const currentKnockoutRound = computed(() => {
    return mixedBracket.value.knockout_stage?.[currentKnockoutRoundIndex.value] || null
})

// Kiểm tra có vòng knockout trước không
const hasPreviousKnockoutRound = computed(() => {
    return currentKnockoutRoundIndex.value > 0
})

// Kiểm tra có vòng knockout sau không
const hasNextKnockoutRound = computed(() => {
    return currentKnockoutRoundIndex.value < (mixedBracket.value.knockout_stage?.length || 0) - 1
})

// Chuyển sang vòng knockout trước
const previousKnockoutRound = () => {
    if (hasPreviousKnockoutRound.value) {
        currentKnockoutRoundIndex.value--
    }
}

// Chuyển sang vòng knockout sau
const nextKnockoutRound = () => {
    if (hasNextKnockoutRound.value) {
        currentKnockoutRoundIndex.value++
    }
}

// Lấy text trạng thái cho knockout matches
const getKnockoutStatusText = (matches) => {
    const completedCount = matches.filter(m => m.legs.some(leg => leg.is_completed)).length
    const pendingCount = matches.filter(m => m.legs.every(leg => leg.status === 'pending')).length
    
    if (completedCount === matches.length) {
        return `Chờ xác nhận • ${pendingCount}`
    }
    return `Chờ xác nhận • ${pendingCount}`
}

// ========== ROUND ROBIN COMPUTED PROPERTIES ==========

// Nhóm các trận đấu theo vòng
const groupedMatchesByRound = computed(() => {
    const grouped = {}
    matches.value.forEach(match => {
        if (!grouped[match.round]) {
            grouped[match.round] = []
        }
        grouped[match.round].push(match)
    })
    return grouped
})

// Lấy danh sách các vòng có sẵn (đã sort)
const availableRounds = computed(() => {
    return Object.keys(groupedMatchesByRound.value).sort((a, b) => {
        return parseInt(a) - parseInt(b)
    })
})

// Tổng số vòng
const totalRounds = computed(() => {
    return availableRounds.value.length
})

// Lấy các trận đấu của vòng hiện tại
const currentRoundMatches = computed(() => {
    return groupedMatchesByRound.value[currentRound.value] || []
})

// Kiểm tra có vòng trước không
const hasPreviousRound = computed(() => {
    const currentIndex = availableRounds.value.indexOf(currentRound.value)
    return currentIndex > 0
})

// Kiểm tra có vòng sau không
const hasNextRound = computed(() => {
    const currentIndex = availableRounds.value.indexOf(currentRound.value)
    return currentIndex < availableRounds.value.length - 1
})

// Chuyển sang vòng trước
const previousRound = () => {
    if (hasPreviousRound.value) {
        const currentIndex = availableRounds.value.indexOf(currentRound.value)
        currentRound.value = availableRounds.value[currentIndex - 1]
    }
}

// Chuyển sang vòng sau
const nextRound = () => {
    if (hasNextRound.value) {
        const currentIndex = availableRounds.value.indexOf(currentRound.value)
        currentRound.value = availableRounds.value[currentIndex + 1]
    }
}

// Lấy text trạng thái cho từng vòng
const getStatusText = (roundMatches) => {
    const completedCount = roundMatches.filter(m => m.is_completed).length
    const pendingCount = roundMatches.filter(m => m.status === 'pending').length

    if (completedCount === roundMatches.length) {
        return `Chờ xác nhận • ${pendingCount}`
    }
    return `Chờ xác nhận • ${pendingCount}`
}

// ========== ELIMINATION COMPUTED PROPERTIES ==========

// Lấy vòng đấu hiện tại trong elimination
const currentEliminationRound = computed(() => {
    return eliminationBracket.value[currentEliminationRoundIndex.value] || null
})

// Kiểm tra có vòng trước không (elimination)
const hasPreviousEliminationRound = computed(() => {
    return currentEliminationRoundIndex.value > 0
})

// Kiểm tra có vòng sau không (elimination)
const hasNextEliminationRound = computed(() => {
    return currentEliminationRoundIndex.value < eliminationBracket.value.length - 1
})

// Chuyển sang vòng trước (elimination)
const previousEliminationRound = () => {
    if (hasPreviousEliminationRound.value) {
        currentEliminationRoundIndex.value--
    }
}

// Chuyển sang vòng sau (elimination)
const nextEliminationRound = () => {
    if (hasNextEliminationRound.value) {
        currentEliminationRoundIndex.value++
    }
}

// Lấy text trạng thái cho elimination matches
const getEliminationStatusText = (matches) => {
    const completedCount = matches.filter(m => m.legs.some(leg => leg.is_completed)).length
    const pendingCount = matches.filter(m => m.legs.every(leg => leg.status === 'pending')).length
    
    if (completedCount === matches.length) {
        return `Chờ xác nhận • ${pendingCount}`
    }
    return `Chờ xác nhận • ${pendingCount}`
}

// ========== SHARED FUNCTIONS ==========

// Format ngày giờ
const formatDate = (dateString) => {
    if (!dateString) return 'Chưa xác định'
    const date = new Date(dateString)
    const day = date.getDate()
    const month = date.getMonth() + 1
    const hours = date.getHours().toString().padStart(2, '0')
    const minutes = date.getMinutes().toString().padStart(2, '0')
    return `${day} Th${month} - ${hours}:${minutes}`
}

const getTeamInitials = (name) => {
  if (!name) return "??";
  return name.split(" ").map(n => n[0]).join("").toUpperCase().substring(0, 2);
};

// Watch để tự động load trận đấu khi tournament_type_id thay đổi
watch(
    () => props.data?.tournament_types?.[0]?.id,
    async (newTournamentTypeId) => {
        if (newTournamentTypeId) {
            await getMatches(newTournamentTypeId);
        }
    },
    { immediate: true, deep: true }
);
</script>

<style scoped>
.match-card {
    transition: all 0.3s ease;
}

.match-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Custom scrollbar for round tabs */
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #D72D36;
    border-radius: 10px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #b91c1c;
}
</style>