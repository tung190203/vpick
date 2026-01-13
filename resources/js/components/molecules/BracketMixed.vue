<template>
    <div class="grid grid-cols-10 gap-4">
        <CreateMatch v-model="showCreateMatchModal" :data="detailData" :tournament="tournament"
            @updated="handleMatchUpdated" />
        <div class="col-span-3">
            <div class="p-4 space-y-4">
                <!-- Header -->
                <div class="flex justify-between items-center p-4 bg-[#EDEEF2] rounded-md">
                    <h2 class="text-lg font-bold text-gray-800">Bảng xếp hạng</h2>
                    <button
                        class="w-9 h-9 rounded-full shadow-lg flex items-center justify-center border border-[#BBBFCC] transition-colors duration-200 hover:bg-gray-100 hover:border-[#838799]">
                        <PencilIcon class="w-5 h-5 text-[#838799] transition-colors duration-200 hover:text-black" />
                    </button>
                </div>

                <!-- Groups -->
                <div v-if="!hasAnyRanking" class="py-2 text-center text-gray-500">
                    Chưa có dữ liệu bảng xếp hạng
                </div>

                <div v-else>
                    <div v-for="group in rank.group_rankings" :key="group.group_id"
                        class="bg-gray-100 rounded-lg shadow overflow-hidden mb-4">
                        <template v-if="group.rankings && group.rankings.length">
                            <!-- Group Header -->
                            <div
                                class="grid grid-cols-[20px_1fr_60px_60px] bg-gray-200 px-4 py-2 text-gray-600 font-semibold text-sm">
                                <span>#</span>
                                <span>Đội</span>
                                <span class="text-center">Điểm</span>
                                <span class="text-center">Hiệu số</span>
                            </div>

                            <!-- Teams -->
                            <div class="divide-y divide-gray-200">
                                <div v-for="(team, index) in group.rankings" :key="team.team_id"
                                    class="grid grid-cols-[20px_1fr_60px_60px] items-center px-4 py-3 bg-white hover:bg-blue-50 transition-colors duration-200">
                                    <span class="font-medium">{{ index + 1 }}</span>

                                    <div class="flex items-center gap-2">
                                        <img :src="team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(team.team_name)}`"
                                            class="w-8 h-8 rounded-full border" />
                                        <p class="text-sm font-medium">{{ team.team_name }}</p>
                                    </div>

                                    <span class="text-center font-semibold">{{ team.points }}</span>
                                    <span class="text-center font-semibold">{{ team.point_diff }}</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-span-7 p-4 pt-0">
            <div class="overflow-x-auto h-full custom-scrollbar-hide">
                <div class="flex w-max min-h-full pb-4">

                    <!-- POOL STAGE -->
                    <div v-for="group in bracket.pool_stage" :key="group.group_id"
                        class="round-column flex flex-col items-center pt-4 min-w-[280px]">

                        <div :class="roundHeaderClass(group.group_name, true)"
                            class="flex justify-between items-center w-full mb-4 bg-[#EDEEF2] p-4">
                            <h2 class="font-bold text-[#3E414C] whitespace-nowrap">
                                {{ group.group_name }}
                            </h2>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-[#838799]">Chưa xác định</span>
                                <button
                                    class="w-9 h-9 rounded-full flex items-center justify-center border border-[#BBBFCC] transition-colors duration-200 hover:bg-gray-100 hover:border-[#838799]">
                                    <PencilIcon
                                        class="w-5 h-5 text-[#838799] transition-colors duration-200 hover:text-black" />
                                </button>
                            </div>
                        </div>

                        <!-- GỘP LEGS THÀNH 1 CARD -->
                        <div v-for="match in group.matches" :key="match.match_id" :class="[
                            matchCardWrapperClass(match),
                            { 'opacity-50': isDragging && draggedTeam?.matchId === match.match_id }
                        ]" class="match-card bg-[#dcdee6] rounded-lg mb-4 w-64 flex flex-col transition-all">

                            <div :class="matchHeaderContentClass(match)"
                                class="flex justify-between items-center text-xs font-medium text-[#838799] px-4 py-2 bg-[#dcdee6] rounded-tl-lg rounded-tr-lg">
                                <span class="uppercase">SÂN {{ match.legs?.[0]?.court || 1 }}</span>
                                <div class="flex items-center gap-2">
                                    <span v-if="hasAnyLegInProgress(match)"
                                        class="text-white font-bold text-xs flex items-center">
                                        <VideoCameraIcon class="w-4 h-4 mr-1" />
                                        Trực tiếp
                                    </span>
                                    <span class="text-xs" v-else>
                                        {{ formatTime(match.legs?.[0]?.scheduled_at) }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">

                                <!-- HOME TEAM - DRAGGABLE -->
                                <div class="flex justify-between items-center px-2 -mx-2 rounded transition-all" :class="{
                                    'bg-blue-100 ring-2 ring-blue-400': isDropTarget(match.match_id, 'home'),
                                    'cursor-move hover:bg-gray-100': canDragPoolStage(match),
                                    'cursor-pointer': !canDragPoolStage(match)
                                }" :draggable="canDragPoolStage(match)"
                                    @dragstart="handleDragStart($event, match, 'home')" @dragend="handleDragEnd"
                                    @dragover.prevent="handleDragOver($event, match.match_id, 'home')"
                                    @dragleave="handleDragLeave($event)"
                                    @drop.prevent.stop="handleDrop($event, match.match_id, 'home')"
                                    @click="!isDragging ? handleMatchClick(match.match_id) : null">
                                    <div class="flex items-center gap-2 pointer-events-none">
                                        <img :src="match.home_team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.home_team.name)}`"
                                            class="w-8 h-8 rounded-full" :alt="match.home_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">
                                            {{ match.home_team.name }}
                                        </p>
                                    </div>
                                    <span class="font-bold text-lg pointer-events-none" :class="[
                                        { 'text-green-700': isWinner(match, 'home') },
                                        { 'text-red-700': isLoser(match, 'home') }
                                    ]">
                                        {{ match.aggregate_score?.home ?? 0 }}
                                    </span>
                                </div>

                                <!-- AWAY TEAM - DRAGGABLE -->
                                <div class="flex justify-between items-center px-2 -mx-2 rounded transition-all" :class="{
                                    'bg-blue-100 ring-2 ring-blue-400': isDropTarget(match.match_id, 'away'),
                                    'cursor-move hover:bg-gray-100': canDragPoolStage(match),
                                    'cursor-pointer': !canDragPoolStage(match)
                                }" :draggable="canDragPoolStage(match)"
                                    @dragstart="handleDragStart($event, match, 'away')" @dragend="handleDragEnd"
                                    @dragover.prevent="handleDragOver($event, match.match_id, 'away')"
                                    @dragleave="handleDragLeave($event)"
                                    @drop.prevent.stop="handleDrop($event, match.match_id, 'away')"
                                    @click="!isDragging ? handleMatchClick(match.match_id) : null">
                                    <div class="flex items-center gap-2 pointer-events-none">
                                        <img :src="match.away_team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.away_team.name)}`"
                                            class="w-8 h-8 rounded-full" :alt="match.away_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">
                                            {{ match.away_team.name }}
                                        </p>
                                    </div>
                                    <span class="font-bold text-lg pointer-events-none" :class="[
                                        { 'text-green-700': isWinner(match, 'away') },
                                        { 'text-red-700': isLoser(match, 'away') }
                                    ]">
                                        {{ match.aggregate_score?.away ?? 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KNOCKOUT STAGE -->
                    <div v-for="roundData in bracket.knockout_stage" :key="roundData.round"
                        class="round-column flex flex-col items-center pt-4 min-w-[280px]">

                        <div :class="roundHeaderClass(roundData.round_name, false)"
                            class="flex justify-between items-center w-full mb-4 bg-[#EDEEF2] p-4">
                            <h2 class="font-bold text-[#3E414C] whitespace-nowrap">
                                {{roundData.matches.some(m => m.is_third_place == 1)
                                    ? 'Tranh hạng 3'
                                    : roundData.round_name
                                }}
                            </h2>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-[#838799]">Chưa xác định</span>
                                <button
                                    class="w-9 h-9 rounded-full flex items-center justify-center border border-[#BBBFCC] transition-colors duration-200 hover:bg-gray-100 hover:border-[#838799]">
                                    <PencilIcon
                                        class="w-5 h-5 text-[#838799] transition-colors duration-200 hover:text-black" />
                                </button>
                            </div>
                        </div>

                        <!-- GỘP LEGS THÀNH 1 CARD -->
                        <div v-for="match in roundData.matches" :key="match.match_id"
                            :class="matchCardWrapperClass(match)"
                            class="match-card bg-[#dcdee6] rounded-lg mb-4 w-64 flex flex-col cursor-pointer hover:shadow-lg transition-all"
                            @click="handleMatchClick(match.match_id)">

                            <div :class="matchHeaderContentClass(match)"
                                class="flex justify-between items-center text-xs font-medium text-[#838799] px-4 py-2 bg-[#dcdee6] rounded-tl-lg rounded-tr-lg">
                                <span class="uppercase">SÂN {{ match.legs?.[0]?.court || 1 }}</span>
                                <div class="flex items-center gap-2">
                                    <span v-if="hasAnyLegInProgress(match)"
                                        class="text-white font-bold text-xs flex items-center">
                                        <VideoCameraIcon class="w-4 h-4 mr-1" />
                                        Trực tiếp
                                    </span>
                                    <span class="text-xs" v-else>
                                        {{ formatTime(match.legs?.[0]?.scheduled_at) }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.home_team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.home_team.name)}`"
                                            class="w-8 h-8 rounded-full" :alt="match.home_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">
                                            {{ match.home_team.name }}
                                        </p>
                                    </div>
                                    <span class="font-bold text-lg" :class="[
                                        { 'text-green-700': isWinner(match, 'home') },
                                        { 'text-red-700': isLoser(match, 'home') }
                                    ]">
                                        {{ match.aggregate_score?.home ?? 0 }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <img :src="match.away_team.team_avatar || `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(match.away_team.name)}`"
                                            class="w-8 h-8 rounded-full" :alt="match.away_team.name" />
                                        <p class="text-sm font-semibold text-[#3E414C]">
                                            {{ match.away_team.name }}
                                        </p>
                                    </div>
                                    <span class="font-bold text-lg" :class="[
                                        { 'text-green-700': isWinner(match, 'away') },
                                        { 'text-red-700': isLoser(match, 'away') }
                                    ]">
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
const canDragPoolStage = (match) => {
    // Chỉ cho drag khi tất cả legs đều pending (chưa bắt đầu)
    return match.legs?.every(leg => leg.status === 'pending');
};

const handleDragStart = (event, match, position) => {
    if (!canDragPoolStage(match)) {
        event.preventDefault();
        return;
    }

    isDragging.value = true;
    const teamData = position === 'home' ? match.home_team : match.away_team;

    draggedTeam.value = {
        matchId: match.match_id,
        position: position,
        teamId: teamData.id,
        teamName: teamData.name,
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

const hasAnyRanking = computed(() => {
    return props.rank?.group_rankings?.some(
        g => g.rankings && g.rankings.length > 0
    );
});

const handleDragLeave = (event) => {
    const rect = event.currentTarget.getBoundingClientRect();
    const x = event.clientX;
    const y = event.clientY;

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
        handleDragEnd();
        return;
    }

    // Tìm trận đích
    const targetMatch = findMatchById(targetMatchId);
    if (!targetMatch) {
        toast.error('Không tìm thấy trận đấu đích');
        handleDragEnd();
        return;
    }

    // Lấy team bị thay thế (to_team)
    const targetTeam = targetPosition === 'home'
        ? targetMatch.home_team
        : targetMatch.away_team;

    try {
        const payload = {
            from_team_id: draggedTeam.value.teamId,
            to_team_id: targetTeam.id,
        };

        await MatchesService.swapTeams(targetMatchId, payload);
        toast.success('Hoán đổi đội thành công!');
        emit('refresh');
    } catch (error) {
        const errorMsg = error.response?.data?.message || 'Có lỗi xảy ra khi hoán đổi đội';
        toast.error(errorMsg);
    } finally {
        handleDragEnd();
    }
};

// Helper function
const findMatchById = (matchId) => {
    for (const group of props.bracket.pool_stage) {
        const match = group.matches.find(m => m.match_id === matchId);
        if (match) return match;
    }
    return null;
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

const handleMatchUpdated = () => {
    showCreateMatchModal.value = false;
    emit('refresh');
};

/* ===========================
   COMPUTED PROPERTIES
=========================== */
const poolStages = computed(() => props.bracket.pool_stage || []);
const knockoutStages = computed(() => props.bracket.knockout_stage || []);

const allRoundNames = computed(() => {
    const poolNames = poolStages.value.map(g => g.group_name);
    const knockoutNames = knockoutStages.value.map(r => r.round_name);
    return [...poolNames, ...knockoutNames];
});

/* ===========================
   STYLING HELPERS
=========================== */
const roundHeaderClass = (roundName, isPoolStage) => {
    const keys = allRoundNames.value;
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

const hasAnyLegInProgress = (match) => {
    return match.legs?.some(leg => leg.status === 'in_progress');
};

const hasAnyLegStarted = (match) => {
    return match.legs?.some(leg => ['in_progress', 'completed'].includes(leg.status));
};

const matchCardWrapperClass = (match) => {
    if (match.status === 'completed') {
        return "border border-green-500 shadow-md bg-green-500";
    } else if (hasAnyLegStarted(match)) {
        return "border border-[#FBBF24] shadow-md !bg-[#FBBF24]";
    }
    return "border";
};

const matchHeaderContentClass = (match) => {
    if (match.status === 'completed') {
        return 'text-white bg-green-500';
    } else if (hasAnyLegStarted(match)) {
        return 'text-white !bg-[#FBBF24]';
    }
    return 'text-[#838799]';
};

/* ===========================
   WINNER/LOSER LOGIC
=========================== */
const isWinner = (match, position) => {
    if (!match.aggregate_score || match.status !== 'completed') return false;

    const homeScore = match.aggregate_score.home ?? 0;
    const awayScore = match.aggregate_score.away ?? 0;

    if (position === 'home') {
        return homeScore > awayScore;
    } else {
        return awayScore > homeScore;
    }
};

const isLoser = (match, position) => {
    if (!match.aggregate_score || match.status !== 'completed') return false;

    const homeScore = match.aggregate_score.home ?? 0;
    const awayScore = match.aggregate_score.away ?? 0;

    if (position === 'home') {
        return homeScore < awayScore;
    } else {
        return awayScore < homeScore;
    }
};

/* ===========================
   UTILITY
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
    if (!scheduledAt) return "Chưa xác định";
    try {
        const date = new Date(scheduledAt);
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    } catch {
        return "Chưa xác định";
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