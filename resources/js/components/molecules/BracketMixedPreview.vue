<template>
    <div class="bracket-preview-container">
        <div class="bracket-scroll-wrapper">
            <!-- BẢNG XẾP HẠNG - LEFT SIDEBAR -->
            <div class="rankings-sidebar left">
                <div class="sidebar-header">
                    <span class="header-text">BẢNG XẾP HẠNG</span>
                    <button class="edit-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>
                <div class="rankings-list">
                    <div v-for="group in leftPoolGroups" :key="group.group_id" class="group-card">
                        <div class="group-header">{{ group.group_name }}</div>
                        <div class="teams-list">
                            <div v-for="(team, idx) in group.teams" :key="team.id || idx" class="team-row">
                                <span class="rank-number">{{ idx + 1 }}</span>
                                <div class="team-avatar">
                                    {{ getTeamInitials(team.name) }}
                                </div>
                                <span class="team-name-text">{{ team.name }}</span>
                                <span class="team-score">{{ team.points || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BRACKET AREA - HORIZONTAL SCROLL -->
            <div class="bracket-area">
                <!-- VÒNG 1/8 - NHÁNH TRÁI -->
                <div v-if="round16Round && leftRound16Matches.length > 0" class="bracket-column">
                    <div class="round-header">
                        <span class="round-title">{{ round16Round.round_name || 'VÒNG 1/8' }}</span>
                    </div>
                    <div class="matches-container">
                        <div v-for="(match, matchIdx) in leftRound16Matches"
                            :key="match.match_id"
                            class="match-box-wrapper"
                            :style="{ '--match-index': matchIdx }">
                            <!-- Connector line to next round -->
                            <div class="connector-line connector-right"></div>

                            <div class="match-box">
                                <div class="match-box-header">
                                    <span class="court-label">SÂN {{ match.legs?.[0]?.court || 1 }}</span>
                                    <span class="match-time">{{ formatMatchTime(match) }}</span>
                                </div>
                                <div class="match-box-content">
                                    <div class="team-row-match">
                                        <div class="team-avatar-small">
                                            <img v-if="match.home_team?.team_avatar"
                                                :src="match.home_team.team_avatar"
                                                :alt="match.home_team.name" />
                                            <span v-else>{{ getTeamInitials(match.home_team?.name || 'TBD') }}</span>
                                        </div>
                                        <span class="team-name-match">{{ match.home_team?.name || 'T TBD' }}</span>
                                        <span class="score-value">{{ match.aggregate_score?.home ?? 0 }}</span>
                                    </div>
                                    <div class="team-row-match">
                                        <div class="team-avatar-small">
                                            <img v-if="match.away_team?.team_avatar"
                                                :src="match.away_team.team_avatar"
                                                :alt="match.away_team.name" />
                                            <span v-else>{{ getTeamInitials(match.away_team?.name || 'TBD') }}</span>
                                        </div>
                                        <span class="team-name-match">{{ match.away_team?.name || 'T TBD' }}</span>
                                        <span class="score-value">{{ match.aggregate_score?.away ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TỨ KẾT, BÁN KẾT, CHUNG KẾT -->
                <div v-for="(round, roundIdx) in quarterfinalsAndLater"
                    :key="round.round || roundIdx"
                    class="bracket-column">
                    <div class="round-header">
                        <span class="round-title">{{ getRoundLabel(round, roundIdx) }}</span>
                    </div>
                    <div class="matches-container">
                        <div v-for="(match, matchIdx) in round.matches"
                            :key="match.match_id"
                            class="match-box-wrapper"
                            :style="{ '--match-index': matchIdx }">
                            <!-- Connector lines -->
                            <div v-if="roundIdx < quarterfinalsAndLater.length - 1" class="connector-line connector-right"></div>

                            <div class="match-box">
                                <div class="match-box-header">
                                    <span class="court-label">SÂN {{ match.legs?.[0]?.court || 1 }}</span>
                                    <span class="match-time">{{ formatMatchTime(match) }}</span>
                                </div>
                                <div class="match-box-content">
                                    <div class="team-row-match">
                                        <div class="team-avatar-small">
                                            <img v-if="match.home_team?.team_avatar"
                                                :src="match.home_team.team_avatar"
                                                :alt="match.home_team.name" />
                                            <span v-else>{{ getTeamInitials(match.home_team?.name || 'TBD') }}</span>
                                        </div>
                                        <span class="team-name-match">{{ match.home_team?.name || 'T TBD' }}</span>
                                        <span class="score-value">{{ match.aggregate_score?.home ?? 0 }}</span>
                                    </div>
                                    <div class="team-row-match">
                                        <div class="team-avatar-small">
                                            <img v-if="match.away_team?.team_avatar"
                                                :src="match.away_team.team_avatar"
                                                :alt="match.away_team.name" />
                                            <span v-else>{{ getTeamInitials(match.away_team?.name || 'TBD') }}</span>
                                        </div>
                                        <span class="team-name-match">{{ match.away_team?.name || 'T TBD' }}</span>
                                        <span class="score-value">{{ match.aggregate_score?.away ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VÒNG 1/8 - NHÁNH PHẢI -->
                <div v-if="round16Round && rightRound16Matches.length > 0" class="bracket-column">
                    <div class="round-header">
                        <span class="round-title">{{ round16Round.round_name || 'VÒNG 1/8' }}</span>
                    </div>
                    <div class="matches-container">
                        <div v-for="(match, matchIdx) in rightRound16Matches"
                            :key="match.match_id"
                            class="match-box-wrapper"
                            :style="{ '--match-index': matchIdx }">
                            <!-- Connector line to previous round (left) -->
                            <div class="connector-line connector-left"></div>

                            <div class="match-box">
                                <div class="match-box-header">
                                    <span class="court-label">SÂN {{ match.legs?.[0]?.court || 1 }}</span>
                                    <span class="match-time">{{ formatMatchTime(match) }}</span>
                                </div>
                                <div class="match-box-content">
                                    <div class="team-row-match">
                                        <div class="team-avatar-small">
                                            <img v-if="match.home_team?.team_avatar"
                                                :src="match.home_team.team_avatar"
                                                :alt="match.home_team.name" />
                                            <span v-else>{{ getTeamInitials(match.home_team?.name || 'TBD') }}</span>
                                        </div>
                                        <span class="team-name-match">{{ match.home_team?.name || 'T TBD' }}</span>
                                        <span class="score-value">{{ match.aggregate_score?.home ?? 0 }}</span>
                                    </div>
                                    <div class="team-row-match">
                                        <div class="team-avatar-small">
                                            <img v-if="match.away_team?.team_avatar"
                                                :src="match.away_team.team_avatar"
                                                :alt="match.away_team.name" />
                                            <span v-else>{{ getTeamInitials(match.away_team?.name || 'TBD') }}</span>
                                        </div>
                                        <span class="team-name-match">{{ match.away_team?.name || 'T TBD' }}</span>
                                        <span class="score-value">{{ match.aggregate_score?.away ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BẢNG XẾP HẠNG - RIGHT SIDEBAR -->
            <div class="rankings-sidebar right">
                <div class="sidebar-header">
                    <span class="header-text">BẢNG XẾP HẠNG</span>
                    <button class="edit-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>
                <div class="rankings-list">
                    <div v-for="group in rightPoolGroups" :key="group.group_id" class="group-card">
                        <div class="group-header">{{ group.group_name }}</div>
                        <div class="teams-list">
                            <div v-for="(team, idx) in group.teams" :key="team.id || idx" class="team-row">
                                <span class="rank-number">{{ idx + 1 }}</span>
                                <div class="team-avatar">
                                    {{ getTeamInitials(team.name) }}
                                </div>
                                <span class="team-name-text">{{ team.name }}</span>
                                <span class="team-score">{{ team.points || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    bracketData: {
        type: Object,
        required: true
    },
    rankData: {
        type: Object,
        required: true
    }
});

const processedBracketData = ref({});

// Computed properties
const allPoolGroups = computed(() => {
    const pools = processedBracketData.value?.pool_stage || [];
    return pools.map(pool => ({
        ...pool,
        teams: pool.teams || []
    }));
});

const leftPoolGroups = computed(() => {
    const pools = allPoolGroups.value;
    const halfIndex = Math.ceil(pools.length / 2);
    return pools.slice(0, halfIndex);
});

const rightPoolGroups = computed(() => {
    const pools = allPoolGroups.value;
    const halfIndex = Math.ceil(pools.length / 2);
    return pools.slice(halfIndex);
});

const knockoutStages = computed(() => {
    return processedBracketData.value?.knockout_stage || [];
});

const round16Round = computed(() => {
    return knockoutStages.value[0] || null;
});

const leftRound16Matches = computed(() => {
    if (!round16Round.value || !round16Round.value.matches) return [];
    const matches = round16Round.value.matches;
    const halfIndex = Math.ceil(matches.length / 2);
    return matches.slice(0, halfIndex);
});

const rightRound16Matches = computed(() => {
    if (!round16Round.value || !round16Round.value.matches) return [];
    const matches = round16Round.value.matches;
    const halfIndex = Math.ceil(matches.length / 2);
    return matches.slice(halfIndex);
});

const quarterfinalsAndLater = computed(() => {
    return knockoutStages.value.slice(1);
});

// Methods
const mergeBracketWithRankings = () => {
    try {
        // Clone only the structure we need to avoid circular reference issues
        const bracket = {
            pool_stage: props.bracketData.pool_stage ? [...props.bracketData.pool_stage] : [],
            knockout_stage: props.bracketData.knockout_stage ? [...props.bracketData.knockout_stage] : []
        };

        if (!bracket.pool_stage || !props.rankData.group_rankings) {
            processedBracketData.value = bracket;
            return;
        }

        bracket.pool_stage = bracket.pool_stage.map(pool => {
            const groupRanking = props.rankData.group_rankings.find(
                g => g.group_id === pool.group_id
            );

            // Clone pool object and add teams
            const poolWithTeams = {
                ...pool,
                teams: groupRanking && groupRanking.rankings
                    ? groupRanking.rankings.map(team => ({
                        id: team.team_id,
                        name: team.team_name,
                        logo: team.team_avatar,
                        points: team.points,
                        point_diff: team.point_diff
                    }))
                    : []
            };

            return poolWithTeams;
        });

        processedBracketData.value = bracket;
    } catch (error) {
        console.error('Error merging bracket with rankings:', error);
        // Fallback: use original data
        processedBracketData.value = props.bracketData;
    }
};

const formatMatchTime = (match) => {
    const leg = match.legs?.[0];
    if (!leg?.scheduled_at) return 'CHƯA XẾP LỊCH';

    const date = new Date(leg.scheduled_at);
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');

    return `${hours}:${minutes}`;
};

const getRoundLabel = (round, index) => {
    if (round.round_name) return round.round_name;

    const labels = ['TỨ KẾT', 'BÁN KẾT', 'CHUNG KẾT'];
    return labels[index] || `VÒNG ${index + 1}`;
};

const getTeamInitials = (name) => {
    if (!name) return 'T';
    const parts = name.split(' ');
    if (parts.length > 1) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

// Watch for prop changes
watch(() => [props.bracketData, props.rankData], () => {
    mergeBracketWithRankings();
}, { immediate: true, deep: true });
</script>

<style scoped>
.bracket-preview-container {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    overflow: hidden;
}

.bracket-scroll-wrapper {
    display: flex;
    height: 100%;
    overflow-x: auto;
    overflow-y: auto;
}

/* Rankings Sidebar */
.rankings-sidebar {
    flex-shrink: 0;
    width: 280px;
    background: #fff;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.rankings-sidebar.left {
    border-right: 1px solid #e5e7eb;
}

.rankings-sidebar.right {
    border-left: 1px solid #e5e7eb;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e5e7eb;
}

.header-text {
    font-size: 0.875rem;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.edit-btn {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s;
}

.edit-btn:hover {
    background: #e5e7eb;
    color: #374151;
}

.rankings-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem;
}

.group-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-bottom: 0.75rem;
    overflow: hidden;
}

.group-header {
    background: #f3f4f6;
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.teams-list {
    display: flex;
    flex-direction: column;
}

.team-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    font-size: 0.75rem;
}

.team-row:last-child {
    border-bottom: none;
}

.rank-number {
    width: 16px;
    font-weight: 600;
    color: #6b7280;
    text-align: center;
}

.team-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.625rem;
    font-weight: 600;
    color: #374151;
    flex-shrink: 0;
}

.team-name-text {
    flex: 1;
    color: #374151;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.team-score {
    font-weight: 600;
    color: #2563eb;
    min-width: 24px;
    text-align: right;
}

/* Bracket Area */
.bracket-area {
    display: flex;
    gap: 0;
    padding: 1rem;
    min-width: max-content;
}

.bracket-column {
    display: flex;
    flex-direction: column;
    min-width: 240px;
    position: relative;
    padding: 0 1rem;
}

.round-header {
    text-align: center;
    padding: 0.75rem 0;
    margin-bottom: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.round-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.matches-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    flex: 1;
    justify-content: space-around;
    min-height: 400px;
}

.match-box-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.match-box {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    width: 220px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    z-index: 2;
}

.match-box-header {
    background: #6b7280;
    color: #fff;
    padding: 0.5rem 0.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
}

.court-label {
    font-weight: 600;
    text-transform: uppercase;
}

.match-time {
    font-size: 0.65rem;
    opacity: 0.9;
}

.match-box-content {
    padding: 0.5rem;
    background: #f9fafb;
}

.team-row-match {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    font-size: 0.75rem;
}

.team-avatar-small {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    font-weight: 600;
    color: #374151;
    flex-shrink: 0;
    overflow: hidden;
}

.team-avatar-small img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.team-name-match {
    flex: 1;
    color: #374151;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.score-value {
    font-weight: 600;
    color: #374151;
    min-width: 24px;
    text-align: right;
}

/* Connector Lines */
.connector-line {
    position: absolute;
    background: #d1d5db;
    z-index: 1;
}

.connector-right {
    right: -1rem;
    width: 1rem;
    height: 2px;
    top: 50%;
    transform: translateY(-50%);
}

.connector-left {
    left: -1rem;
    width: 1rem;
    height: 2px;
    top: 50%;
    transform: translateY(-50%);
}

/* Scrollbar Styling */
.bracket-scroll-wrapper::-webkit-scrollbar,
.rankings-list::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.bracket-scroll-wrapper::-webkit-scrollbar-track,
.rankings-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.bracket-scroll-wrapper::-webkit-scrollbar-thumb,
.rankings-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.bracket-scroll-wrapper::-webkit-scrollbar-thumb:hover,
.rankings-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
