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

    <div class="flex justify-between items-center gap-2 mb-4">
        <div class="flex gap-2">
            <button v-for="tab in scheduleTabs" :key="tab.id" @click="scheduleActiveTab = tab.id" :class="[
                'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
                scheduleActiveTab === tab.id
                    ? 'bg-[#D72D36] text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]">
                {{ tab.label }}
            </button>
        </div>

        <button v-if="scheduleActiveTab === 'ranking'" @click="showRankingModal = true"
            class="px-4 py-2 bg-[#D72D36] text-white rounded-lg text-sm font-medium hover:bg-[#b91c1c] transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Xem chi tiết BXH
        </button>
    </div>

    <template v-if="scheduleActiveTab === 'ranking'">
        <template v-if="data.tournament_types?.[0]?.format === 2 || data.tournament_types?.[0]?.format === 3">
            <div v-if="rank && rank.rankings" class="p-4 space-y-4">
                <div class="bg-gray-100 rounded-lg shadow overflow-hidden">
                    <div class="grid grid-cols-[40px_1fr_80px_80px] bg-gray-200 px-4 py-2 text-gray-600 font-semibold text-sm">
                        <span>#</span>
                        <span>Đội</span>
                        <span class="text-center">Điểm</span>
                        <span class="text-center">Hiệu số</span>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <div v-for="(team, index) in rank.rankings" :key="team.team_id"
                            class="grid grid-cols-[40px_1fr_80px_80px] items-center px-4 py-3 bg-white hover:bg-blue-50 transition-colors duration-200 cursor-pointer">
                            <span class="font-bold text-lg" :class="{
                                'text-yellow-500': index === 0,
                                'text-gray-400': index === 1,
                                'text-orange-500': index === 2
                            }">{{ index + 1 }}</span>
                            <div class="flex items-center gap-2">
                                <img :src="team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(team.team_name)}`"
                                    alt="logo team" class="w-8 h-8 rounded-full border border-gray-300" />
                                <p class="text-gray-800 font-medium text-sm break-words whitespace-normal leading-snug min-w-0">{{ team.team_name }}</p>
                            </div>
                            <span class="text-center font-bold text-lg text-blue-600">{{ team.points }}</span>
                            <span class="text-center font-semibold" :class="{
                                'text-green-600': team.point_diff > 0,
                                'text-red-600': team.point_diff < 0,
                                'text-gray-600': team.point_diff === 0
                            }">
                                {{ team.point_diff > 0 ? '+' : '' }}{{ team.point_diff }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="data.tournament_types?.[0]?.format === 1">
            <div v-for="group in rank.group_rankings" :key="group.group_id" class="p-4 space-y-4">
                <div class="bg-gray-100 rounded-lg shadow overflow-hidden">
                    <div class="grid grid-cols-[40px_1fr_80px_80px] bg-gray-200 px-4 py-2 text-gray-600 font-semibold text-sm">
                        <span>#</span>
                        <span>{{ group.group_name }}</span>
                        <span class="text-center">Điểm</span>
                        <span class="text-center">Hiệu số</span>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <div v-for="(team, index) in group.rankings" :key="team.team_id"
                            class="grid grid-cols-[40px_1fr_80px_80px] items-center px-4 py-3 bg-white hover:bg-blue-50 transition-colors duration-200 cursor-pointer">
                            <span class="font-bold text-lg" :class="{
                                'text-yellow-500': index === 0,
                                'text-gray-400': index === 1,
                                'text-orange-500': index === 2
                            }">{{ index + 1 }}</span>
                            <div class="flex items-center gap-2">
                                <img :src="team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(team.team_name)}`"
                                    alt="logo team" class="w-8 h-8 rounded-full border border-gray-300" />
                                <p class="text-gray-800 font-medium text-sm break-words whitespace-normal leading-snug min-w-0">{{ team.team_name }}</p>
                            </div>
                            <span class="text-center font-bold text-lg text-blue-600">{{ team.points }}</span>
                            <span class="text-center font-semibold" :class="{
                                'text-green-600': team.point_diff > 0,
                                'text-red-600': team.point_diff < 0,
                                'text-gray-600': team.point_diff === 0
                            }">
                                {{ team.point_diff > 0 ? '+' : '' }}{{ team.point_diff }}
                            </span>
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
        <template v-if="data.tournament_types?.[0]?.format === 1">
            <template v-if="mixedBracket.pool_stage || mixedBracket.knockout_stage">
                <div class="flex justify-start gap-2 mb-6">
                    <button v-for="stage in mixedStages" :key="stage.id" @click="currentMixedStage = stage.id"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            currentMixedStage === stage.id
                                ? 'bg-[#D72D36] text-white shadow-md border'
                                : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                        ]">
                        {{ stage.label }}
                    </button>
                </div>

                <template v-if="currentMixedStage === 'pool' && mixedBracket.poolStage">
                    <div v-for="group in mixedBracket.poolStage" :key="group.group_id" class="mb-6">
                        <div class="bg-[#EDEEF2] px-4 py-3 rounded-lg mb-4">
                            <h3 class="font-bold text-[#3E414C]">{{ group.group_name }}</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 px-2 cursor-pointer">
                            <div v-for="match in group.matches" :key="match.match_id" @click="getDetailMatches(match.match_id)">
                                <div v-for="leg in match.legs" :key="leg.id" class="match-card bg-[#dcdee6] rounded-lg w-full flex flex-col mb-3">
                                    <div class="flex justify-between items-center text-xs font-medium text-[#838799] px-4 py-2 bg-[#dcdee6] rounded-tl-lg rounded-tr-lg">
                                        <span class="uppercase">SÂN 1 - Lượt {{ leg.leg === 1 ? 'đi' : 'về' }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs">{{ leg.scheduled_at ? formatDate(leg.scheduled_at) : 'Chưa xác định' }}</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center gap-2">
                                                <img :src="match.home_team.logo || 'https://placehold.co/40x40'" class="w-8 h-8 rounded-full object-cover" :alt="match.home_team.name" />
                                                <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team.name }}</p>
                                            </div>
                                            <span class="font-bold text-lg text-[#3E414C]">{{ leg.home_score ?? 0 }}</span>
                                        </div>

                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center gap-2">
                                                <img :src="match.away_team.logo || 'https://placehold.co/40x40'" class="w-8 h-8 rounded-full object-cover" :alt="match.away_team.name" />
                                                <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team.name }}</p>
                                            </div>
                                            <span class="font-bold text-lg text-[#3E414C]">{{ leg.away_score ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-if="currentMixedStage === 'knockout' && (mixedBracket.leftSide || mixedBracket.rightSide || mixedBracket.finalMatch)">
                    <div v-if="currentKnockoutRound" class="mb-6 cursor-pointer">
                        <div class="grid grid-cols-2 items-center mb-4 uppercase px-2">
                            <p class="text-sm font-semibold">
                                {{ currentKnockoutRound.round_name }} • {{ currentKnockoutRound.matches.length }} trận đấu
                            </p>
                            <p class="text-sm font-semibold text-right">{{ getKnockoutStatusText(currentKnockoutRound.matches) }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 px-2">
                            <div v-for="match in currentKnockoutRound.matches" :key="match.match_id" @click="getDetailMatches(match.match_id)"
                                :class="['match-card rounded-lg w-full flex flex-col bg-[#dcdee6]']">
                                <div :class="['flex justify-between items-center text-xs font-medium px-4 py-2 rounded-tl-lg rounded-tr-lg bg-[#dcdee6] text-[#838799]']">
                                    <span class="uppercase">{{ match.match_label || `Trận ${match.match_id}` }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs">{{ match.scheduled_at ? formatDate(match.scheduled_at) : 'Chưa xác định' }}</span>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-2">
                                            <img :src="match.home_team?.team_avatar || match.home_team?.logo || 'https://placehold.co/40x40'" class="w-8 h-8 rounded-full object-cover" :alt="match.home_team?.name" />
                                            <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team?.name || 'TBD' }}</p>
                                        </div>
                                        <span :class="['font-bold text-lg', match.winner_team_id === match.home_team?.id ? 'text-[#D72D36]' : 'text-[#3E414C]']">
                                            {{ match.home_score ?? 0 }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-2">
                                            <img :src="match.away_team?.team_avatar || match.away_team?.logo || 'https://placehold.co/40x40'" class="w-8 h-8 rounded-full object-cover" :alt="match.away_team?.name" />
                                            <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team?.name || 'TBD' }}</p>
                                        </div>
                                        <span :class="['font-bold text-lg', match.winner_team_id === match.away_team?.id ? 'text-[#D72D36]' : 'text-[#3E414C]']">
                                            {{ match.away_score ?? 0 }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="allKnockoutRounds.length > 1" class="flex justify-center items-center gap-4 mt-4">
                        <button @click="previousKnockoutRound" :disabled="!hasPreviousKnockoutRound" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            hasPreviousKnockoutRound ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed border'
                        ]">← Vòng trước</button>

                        <span class="text-sm text-gray-600">Vòng {{ currentKnockoutRoundIndex + 1 }} / {{ allKnockoutRounds.length }}</span>

                        <button @click="nextKnockoutRound" :disabled="!hasNextKnockoutRound" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            hasNextKnockoutRound ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed border'
                        ]">Vòng sau →</button>
                    </div>
                </template>
            </template>

            <template v-else>
                <p class="text-center text-gray-500">Không có trận đấu nào.</p>
            </template>
        </template>

        <template v-else-if="data.tournament_types?.[0]?.format === 2">
            <template v-if="eliminationBracket && eliminationBracket.length > 0">
                <div v-if="currentEliminationRound" class="mb-6 cursor-pointer">
                    <div class="grid grid-cols-2 items-center mb-4 uppercase px-2">
                        <p class="text-sm font-semibold">{{ currentEliminationRound.round_name }} • {{ currentEliminationRound.matches.length }} trận đấu</p>
                        <p class="text-sm font-semibold text-right">{{ getEliminationStatusText(currentEliminationRound.matches) }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 px-2">
                        <div v-for="match in currentEliminationRound.matches" :key="match.match_id" @click="getDetailMatches(match.match_id)"
                            :class="['match-card rounded-xl w-full flex flex-col', match.is_third_place ? 'bg-amber-100 border-2 border-amber-500 bg-amber-500' : 'bg-[#dcdee6]']">
                            <div :class="['flex justify-between items-center text-xs font-medium px-4 py-2 rounded-tl-lg rounded-tr-lg',
                                match.is_third_place ? 'bg-amber-500 text-white' : 'bg-[#dcdee6] text-[#838799]']">
                                <span class="uppercase">{{ match.match_label || (match.is_third_place ? 'Tranh hạng 3' : `Trận ${match.match_id}`) }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs">{{ match.legs[0]?.scheduled_at ? formatDate(match.legs[0].scheduled_at) : 'Chưa xác định' }}</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.home_team.logo || 'https://placehold.co/40x40'" class="w-8 h-8 rounded-full object-cover" :alt="match.home_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team.name }}</p>
                                    </div>
                                    <span class="font-bold text-lg" :class="{ 'text-[#D72D36]': match.winner_team_id === match.home_team.id }">
                                        {{ match.aggregate_score.home }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.away_team.logo || 'https://placehold.co/40x40'" class="w-8 h-8 rounded-full object-cover" :alt="match.away_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team.name }}</p>
                                    </div>
                                    <span class="font-bold text-lg" :class="{ 'text-[#D72D36]': match.winner_team_id === match.away_team.id }">
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
                        hasPreviousEliminationRound ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    ]">← Vòng trước</button>

                    <span class="text-sm text-gray-600">Vòng {{ currentEliminationRoundIndex + 1 }} / {{ eliminationBracket.length }}</span>

                    <button @click="nextEliminationRound" :disabled="!hasNextEliminationRound" :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                        hasNextEliminationRound ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    ]">Vòng sau →</button>
                </div>
            </template>

            <template v-else>
                <p class="text-center text-gray-500">Không có trận đấu nào.</p>
            </template>
        </template>

        <template v-else>
            <template v-if="matches.length > 0">
                <div v-if="currentRoundMatches.length > 0" class="mb-6">
                    <div class="grid grid-cols-2 items-center mb-4 uppercase px-2">
                        <p class="text-sm font-semibold">Vòng {{ currentRound }} • {{ currentRoundMatches.length }} trận đấu</p>
                        <p class="text-sm font-semibold text-right">{{ getStatusText(currentRoundMatches) }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 px-2 cursor-pointer">
                        <div v-for="match in currentRoundMatches" :key="match.id" class="match-card bg-[#dcdee6] rounded-lg w-full flex flex-col" @click="getDetailMatches(match.id)">
                            <div class="flex justify-between items-center text-xs font-medium text-[#838799] px-4 py-2 bg-[#dcdee6] rounded-tl-lg rounded-tr-lg">
                                <span class="uppercase">SÂN {{ match.court }}</span>
                                <div class="flex items-center gap-2">
                                    <span v-if="match.scheduled_at" class="text-xs">{{ formatDate(match.scheduled_at) }}</span>
                                    <span v-else class="text-xs">Chưa xác định</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.home_team.logo || 'https://placehold.co/40x40'" class="w-8 h-8 rounded-full object-cover" :alt="match.home_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">{{ match.home_team.name }}</p>
                                    </div>
                                    <span class="font-bold text-lg" :class="{ 'text-[#D72D36]': match.is_completed && match.home_score > match.away_score }">
                                        {{ match.home_score }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.away_team.logo || 'https://placehold.co/40x40'" class="w-8 h-8 rounded-full object-cover" :alt="match.away_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">{{ match.away_team.name }}</p>
                                    </div>
                                    <span class="font-bold text-lg" :class="{ 'text-[#D72D36]': match.is_completed && match.away_score > match.home_score }">
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
                        hasPreviousRound ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    ]">← Vòng trước</button>

                    <span class="text-sm text-gray-600">Vòng {{ currentRound }} / {{ totalRounds }}</span>

                    <button @click="nextRound" :disabled="!hasNextRound" :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                        hasNextRound ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    ]">Vòng sau →</button>
                </div>
            </template>

            <template v-else>
                <p class="text-center text-gray-500">Không có trận đấu nào.</p>
            </template>
        </template>
    </template>

    <CreateMatch v-model="showCreateMatchModal" :data="detailData" :tournament="data" />

    <Teleport to="body">
        <Transition name="modal">
            <div v-show="showRankingModal"
                class="fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4"
                @click.self="showRankingModal = false">
                <div class="bg-white rounded-lg w-full h-full overflow-hidden shadow-2xl max-w-[95vw] max-h-[95vh] flex flex-col">
                    <div class="sticky top-0 bg-white z-10 flex justify-between items-center p-4 border-b border-gray-200 flex-shrink-0">
                        <h2 class="text-2xl font-bold text-gray-800">Sơ đồ thi đấu</h2>
                        <button @click="showRankingModal = false"
                            class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition-colors">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-hidden">
                        <BracketMixedPreview
                            v-if="data?.tournament_types?.[0]?.format === 1"
                            :tournamentId="data?.id"
                            :bracketData="mixedBracket"
                            :rankData="rank"
                            @close="showRankingModal = false"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import CreateMatch from '@/components/molecules/CreateMatch.vue'
import BracketMixedPreview from '@/components/molecules/BracketMixedPreview.vue'
import { ref, watch, computed } from 'vue'
import { SCHEDULE_TABS } from '@/data/tournament/index.js'
import { toast } from 'vue3-toastify';
import * as TournamentService from '@/service/tournament.js'
import * as MatchesService from '@/service/match.js'

const scheduleTabs = SCHEDULE_TABS
const scheduleActiveTab = ref('ranking')
const matches = ref([])
const currentRound = ref('1')
const showCreateMatchModal = ref(false)
const showRankingModal = ref(false)
const detailData = ref({});

const eliminationBracket = ref([])
const currentEliminationRoundIndex = ref(0)

const mixedBracket = ref({})
const currentMixedStage = ref('pool')
const currentKnockoutRoundIndex = ref(0)

const mixedStages = [
    { id: 'pool', label: 'Vòng bảng' },
    { id: 'knockout', label: 'Vòng loại trực tiếp' }
]

const props = defineProps({
    isCreator: { type: Boolean, default: false },
    toggle: { type: Boolean, required: true },
    rank: { type: Object, required: true },
    data: { type: Object, required: true }
})

const emit = defineEmits(['handleToggle'])

const hasAnyRanking = computed(() => {
    if (props.data.tournament_types?.[0]?.format === 1) {
        return props.rank?.group_rankings?.some(g => g.rankings && g.rankings.length > 0);
    }
    return props.rank?.rankings && props.rank.rankings.length > 0;
});

const getMatches = async (tournamentId) => {
    try {
        if (!tournamentId) return;

        const response = await TournamentService.getBracketByTournamentId(tournamentId)

        if (props.data.tournament_types?.[0]?.format === 1) {
            // Format Mixed - sử dụng cấu trúc mới
            mixedBracket.value = {
                poolStage: response.poolStage || [],
                leftSide: response.leftSide || [],
                rightSide: response.rightSide || [],
                finalMatch: response.finalMatch || null,
                thirdPlaceMatch: response.thirdPlaceMatch || null
            }
            currentMixedStage.value = 'pool'
            currentKnockoutRoundIndex.value = 0
        } else if (props.data.tournament_types?.[0]?.format === 2) {
            // Format Elimination
            eliminationBracket.value = response.bracket || []
            currentEliminationRoundIndex.value = 0
        } else {
            // Format Round Robin
            matches.value = response.bracket || []
            if (matches.value.length > 0) {
                const rounds = [...new Set(matches.value.map(m => m.round))].sort((a, b) => parseInt(a) - parseInt(b))
                if (rounds.length > 0) currentRound.value = rounds[0]
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

const allKnockoutRounds = computed(() => {
    const rounds = []
    // Thêm leftSide rounds
    if (mixedBracket.value.leftSide) {
        rounds.push(...mixedBracket.value.leftSide)
    }
    // Thêm rightSide rounds
    if (mixedBracket.value.rightSide) {
        rounds.push(...mixedBracket.value.rightSide)
    }
    // Thêm final match nếu có
    if (mixedBracket.value.finalMatch) {
        rounds.push({
            round_name: mixedBracket.value.finalMatch.round_name || 'Chung kết',
            matches: [mixedBracket.value.finalMatch]
        })
    }
    // Thêm third place match nếu có
    if (mixedBracket.value.thirdPlaceMatch) {
        rounds.push({
            round_name: 'Tranh hạng Ba',
            matches: [mixedBracket.value.thirdPlaceMatch]
        })
    }
    // Sắp xếp theo round number
    return rounds.sort((a, b) => (a.round || 0) - (b.round || 0))
})

const currentKnockoutRound = computed(() => allKnockoutRounds.value[currentKnockoutRoundIndex.value] || null)
const hasPreviousKnockoutRound = computed(() => currentKnockoutRoundIndex.value > 0)
const hasNextKnockoutRound = computed(() => currentKnockoutRoundIndex.value < allKnockoutRounds.value.length - 1)
const previousKnockoutRound = () => { if (hasPreviousKnockoutRound.value) currentKnockoutRoundIndex.value-- }
const nextKnockoutRound = () => { if (hasNextKnockoutRound.value) currentKnockoutRoundIndex.value++ }

const getKnockoutStatusText = (matches) => {
    if (!matches || matches.length === 0) return 'Chưa có trận đấu'
    const completedCount = matches.filter(m => m.status === 'completed').length
    const pendingCount = matches.filter(m => m.status === 'pending').length
    if (completedCount === matches.length) return `Hoàn thành • ${completedCount}`
    return `Đang diễn ra • ${completedCount}/${matches.length}`
}

const groupedMatchesByRound = computed(() => {
    const grouped = {}
    matches.value.forEach(match => {
        if (!grouped[match.round]) grouped[match.round] = []
        grouped[match.round].push(match)
    })
    return grouped
})

const availableRounds = computed(() => Object.keys(groupedMatchesByRound.value).sort((a, b) => parseInt(a) - parseInt(b)))
const totalRounds = computed(() => availableRounds.value.length)
const currentRoundMatches = computed(() => groupedMatchesByRound.value[currentRound.value] || [])
const hasPreviousRound = computed(() => {
    const currentIndex = availableRounds.value.indexOf(currentRound.value)
    return currentIndex > 0
})
const hasNextRound = computed(() => {
    const currentIndex = availableRounds.value.indexOf(currentRound.value)
    return currentIndex < availableRounds.value.length - 1
})

const previousRound = () => {
    if (hasPreviousRound.value) {
        const currentIndex = availableRounds.value.indexOf(currentRound.value)
        currentRound.value = availableRounds.value[currentIndex - 1]
    }
}

const nextRound = () => {
    if (hasNextRound.value) {
        const currentIndex = availableRounds.value.indexOf(currentRound.value)
        currentRound.value = availableRounds.value[currentIndex + 1]
    }
}

const getStatusText = (roundMatches) => {
    const completedCount = roundMatches.filter(m => m.is_completed).length
    const pendingCount = roundMatches.filter(m => m.status === 'pending').length
    if (completedCount === roundMatches.length) return `Chờ xác nhận • ${pendingCount}`
    return `Chờ xác nhận • ${pendingCount}`
}

const currentEliminationRound = computed(() => eliminationBracket.value[currentEliminationRoundIndex.value] || null)
const hasPreviousEliminationRound = computed(() => currentEliminationRoundIndex.value > 0)
const hasNextEliminationRound = computed(() => currentEliminationRoundIndex.value < eliminationBracket.value.length - 1)
const previousEliminationRound = () => { if (hasPreviousEliminationRound.value) currentEliminationRoundIndex.value-- }
const nextEliminationRound = () => { if (hasNextEliminationRound.value) currentEliminationRoundIndex.value++ }

const getEliminationStatusText = (matches) => {
    const completedCount = matches.filter(m => m.legs.some(leg => leg.is_completed)).length
    const pendingCount = matches.filter(m => m.legs.every(leg => leg.status === 'pending')).length
    if (completedCount === matches.length) return `Chờ xác nhận • ${pendingCount}`
    return `Chờ xác nhận • ${pendingCount}`
}

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
    const parts = name.split(" ");
    if (parts.length > 1) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

watch(
    () => props.data?.id,
    async (newTournamentId) => {
        if (newTournamentId) await getMatches(newTournamentId);
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

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-to,
.modal-leave-from {
    opacity: 1;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
    transition: transform 0.3s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
    transform: scale(0.95);
}

.modal-enter-to .bg-white,
.modal-leave-from .bg-white {
    transform: scale(1);
}
</style>
