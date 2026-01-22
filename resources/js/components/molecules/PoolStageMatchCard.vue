<template>
    <div
        :class="[
            matchCardWrapperClass,
            {
                'opacity-50':
                    isDragging &&
                    draggedTeam?.matchId === match.match_id,
            },
        ]"
        class="match-card bg-[#dcdee6] rounded-lg mb-4 w-full flex flex-col transition-all"
    >
        <div
            :class="matchHeaderContentClass"
            class="flex justify-between items-center text-xs font-medium text-[#838799] px-4 py-2 bg-[#dcdee6] rounded-tl-lg rounded-tr-lg"
        >
            <span class="uppercase"
                >SÂN {{ match.legs?.[0]?.court || match.court || 1 }}</span
            >
            <div class="flex items-center gap-2">
                <span
                    v-if="hasAnyLegInProgress"
                    class="text-white font-bold text-xs flex items-center"
                >
                    <VideoCameraIcon class="w-4 h-4 mr-1" />
                    Trực tiếp
                </span>
                <span class="text-xs" v-else>
                    {{ formattedTime }}
                </span>
            </div>
        </div>

        <div
            class="flex flex-col gap-3 rounded-lg shadow-md border border-[#dcdee6] bg-[#EDEEF2] px-4 py-3"
        >
            <!-- HOME TEAM - DRAGGABLE -->
            <div
                v-tooltip="match.home_team.name"
                class="flex justify-between items-center px-2 -mx-2 rounded transition-all"
                :class="{
                    'bg-blue-100 ring-2 ring-blue-400':
                        isDropTarget(match.match_id, 'home'),
                    'cursor-move hover:bg-gray-100':
                        canDrag,
                    'cursor-pointer':
                        !canDrag,
                }"
                :draggable="enableDragDrop && canDrag"
                @dragstart="enableDragDrop ? handleDragStart($event, match, 'home') : null"
                @dragend="enableDragDrop ? handleDragEnd() : null"
                @dragover.prevent="enableDragDrop ? handleDragOver($event, match.match_id, 'home') : null"
                @dragleave="enableDragDrop ? handleDragLeave($event) : null"
                @drop.prevent.stop="enableDragDrop ? handleDrop($event, match.match_id, 'home') : null"
                @click="!isDragging ? $emit('match-click', match.match_id) : null"
            >
                <div
                    class="flex items-center gap-2 pointer-events-none"
                >
                    <img
                        :src="homeTeamAvatar"
                        class="w-8 h-8 rounded-full"
                        :alt="match.home_team.name"
                    />
                    <p
                        class="text-sm font-semibold text-[#3E414C] truncate max-w-[150px]"
                    >
                        {{ match.home_team.name }}
                    </p>
                </div>
                <span
                    class="font-bold text-lg pointer-events-none"
                    :class="[
                        {
                            'text-green-700': isHomeWinner,
                        },
                        {
                            'text-red-700': isHomeLoser,
                        },
                    ]"
                >
                    {{ match.aggregate_score?.home ?? match.home_score ?? 0 }}
                </span>
            </div>

            <!-- AWAY TEAM - DRAGGABLE -->
            <div
                v-tooltip="match.away_team.name"
                class="flex justify-between items-center px-2 -mx-2 rounded transition-all"
                :class="{
                    'bg-blue-100 ring-2 ring-blue-400':
                        isDropTarget(match.match_id, 'away'),
                    'cursor-move hover:bg-gray-100':
                        canDrag,
                    'cursor-pointer':
                        !canDrag,
                }"
                :draggable="canDrag"
                @dragstart="handleDragStart($event, match, 'away')"
                @dragend="handleDragEnd"
                @dragover.prevent="handleDragOver($event, match.match_id, 'away')"
                @dragleave="handleDragLeave($event)"
                @drop.prevent.stop="handleDrop($event, match.match_id, 'away')"
                @click="!isDragging ? $emit('match-click', match.match_id) : null"
            >
                <div
                    class="flex items-center gap-2 pointer-events-none"
                >
                    <img
                        :src="awayTeamAvatar"
                        class="w-8 h-8 rounded-full"
                        :alt="match.away_team.name"
                    />
                    <p
                        class="text-sm font-semibold text-[#3E414C] truncate max-w-[150px]"
                    >
                        {{ match.away_team.name }}
                    </p>
                </div>
                <span
                    class="font-bold text-lg pointer-events-none"
                    :class="[
                        {
                            'text-green-700': isAwayWinner,
                        },
                        {
                            'text-red-700': isAwayLoser,
                        },
                    ]"
                >
                    {{ match.aggregate_score?.away ?? match.away_score ?? 0 }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { VideoCameraIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    match: {
        type: Object,
        required: true,
    },
    isDragging: {
        type: Boolean,
        default: false,
    },
    draggedTeam: {
        type: Object,
        default: null,
    },
    dropTargetMatch: {
        type: [String, Number],
        default: null,
    },
    dropTargetPosition: {
        type: String,
        default: null,
    },
    enableDragDrop: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['match-click', 'drag-start', 'drag-end', 'drag-over', 'drag-leave', 'drop']);

const hasAnyLegInProgress = computed(() => {
    if (props.match.status === 'in_progress') return true;
    return props.match.legs?.some((leg) => leg.status === 'in_progress');
});

const hasAnyLegStarted = computed(() => {
    return props.match.legs?.some((leg) =>
        ['in_progress', 'completed'].includes(leg.status),
    );
});

const matchCardWrapperClass = computed(() => {
    if (props.match.status === 'completed') {
        return 'border border-green-500 shadow-md bg-green-500';
    } else if (hasAnyLegStarted.value || props.match.status === 'in_progress') {
        return 'border border-[#FBBF24] shadow-md !bg-[#FBBF24]';
    }
    return 'border';
});

const matchHeaderContentClass = computed(() => {
    if (props.match.status === 'completed') {
        return 'text-white bg-green-500';
    } else if (hasAnyLegStarted.value || props.match.status === 'in_progress') {
        return 'text-white !bg-[#FBBF24]';
    }
    return 'text-[#838799]';
});

const formattedTime = computed(() => {
    const scheduledAt = props.match.legs?.[0]?.scheduled_at || props.match.scheduled_at;
    if (!scheduledAt) return 'Chưa xác định';
    try {
        const date = new Date(scheduledAt);
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    } catch {
        return 'Chưa xác định';
    }
});

const isHomeWinner = computed(() => {
    if (props.match.status !== 'completed') return false;
    const homeScore = props.match.aggregate_score?.home ?? props.match.home_score ?? 0;
    const awayScore = props.match.aggregate_score?.away ?? props.match.away_score ?? 0;
    if (props.match.winner_team_id && props.match.home_team?.id) {
        return props.match.winner_team_id === props.match.home_team.id;
    }
    return homeScore > awayScore;
});

const isHomeLoser = computed(() => {
    if (props.match.status !== 'completed') return false;
    const homeScore = props.match.aggregate_score?.home ?? props.match.home_score ?? 0;
    const awayScore = props.match.aggregate_score?.away ?? props.match.away_score ?? 0;
    return homeScore < awayScore;
});

const isAwayWinner = computed(() => {
    if (props.match.status !== 'completed') return false;
    const homeScore = props.match.aggregate_score?.home ?? props.match.home_score ?? 0;
    const awayScore = props.match.aggregate_score?.away ?? props.match.away_score ?? 0;
    if (props.match.winner_team_id && props.match.away_team?.id) {
        return props.match.winner_team_id === props.match.away_team.id;
    }
    return awayScore > homeScore;
});

const isAwayLoser = computed(() => {
    if (props.match.status !== 'completed') return false;
    const homeScore = props.match.aggregate_score?.home ?? props.match.home_score ?? 0;
    const awayScore = props.match.aggregate_score?.away ?? props.match.away_score ?? 0;
    return awayScore < homeScore;
});

const canDrag = computed(() => {
    return props.match.legs?.every((leg) => leg.status === 'pending');
});

const isDropTarget = (matchId, position) => {
    return (
        props.dropTargetMatch === matchId &&
        props.dropTargetPosition === position
    );
};

const getTeamInitials = (name) => {
    if (!name) return '??';
    const parts = name.split(' ');
    if (parts.length > 1) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const homeTeamAvatar = computed(() => {
    return props.match.home_team.team_avatar ||
           `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(props.match.home_team.name)}`;
});

const awayTeamAvatar = computed(() => {
    return props.match.away_team.team_avatar ||
           `https://placehold.co/40x40/BBBFCC/3E414C?text=${getTeamInitials(props.match.away_team.name)}`;
});

const handleDragStart = (event, match, position) => {
    if (!canDrag.value) {
        event.preventDefault();
        return;
    }
    emit('drag-start', { event, match, position });
};

const handleDragEnd = () => {
    emit('drag-end');
};

const handleDragOver = (event, matchId, position) => {
    emit('drag-over', { event, matchId, position });
};

const handleDragLeave = (event) => {
    emit('drag-leave', { event });
};

const handleDrop = (event, matchId, position) => {
    emit('drop', { event, matchId, position });
};
</script>
