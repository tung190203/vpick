<template>
    <div class="m-4 p-4 max-w-8xl h-[96%] rounded-md bg-white shadow-sm">
        <div class="flex justify-between mb-4">
            <div class="flex items-center gap-4 mb-4">
                <ArrowLeftIcon class="w-6 h-6 text-gray-600 hover:text-[#D72D36] cursor-pointer" @click="goBack" />
                <h1 class="text-lg font-semibold">Sắp xếp bảng đấu</h1>
            </div>
            <div>
                <button
                    class="w-full bg-secondary hover:bg-white border text-white px-4 py-2 rounded-md hover:text-[#D72D36] hover:border-[#D72D36] font-medium transition"
                    @click="confirmAssignments" :disabled="loading">
                    {{ loading ? 'Đang lưu...' : 'Xác nhận' }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-10 gap-4 h-[calc(100%-3rem)]">
            <!-- Left Sidebar - Unassigned Teams -->
            <div class="col-span-3 h-full bg-gray-100 rounded-lg p-4">
                <div class="flex flex-col h-full">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-semibold text-gray-700">ĐỘI CHƯA XẾP</h2>
                            <span class="text-gray-500 text-sm">• {{ unassignedTeams.length }}</span>
                        </div>
                        <button @click="autoAssignTeams"
                            class="text-red-500 text-xs font-medium hover:text-red-600 transition-colors">
                            Tự động xếp bảng
                        </button>
                    </div>

                    <!-- Unassigned Teams List -->
                    <div class="flex-1 space-y-2 overflow-y-auto custom-scrollbar-hide"
                        @drop="onDrop($event, 'unassigned')" @dragover.prevent @dragenter.prevent>
                        <div v-for="team in unassignedTeams" :key="team.team_id" :draggable="true"
                            @dragstart="onDragStart($event, team, 'unassigned')" @dragend="onDragEnd"
                            class="flex items-center gap-3 p-3 bg-white rounded-lg hover:bg-gray-50 transition-colors cursor-move"
                            :class="{ 'opacity-50': draggingTeam?.team_id === team.team_id }">
                            <img :src="dotMenu" alt="">

                            <img :src="team.team_avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${team.team_id}`"
                                :alt="team.team_name" class="w-10 h-10 rounded-full flex-shrink-0">

                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 text-sm truncate">{{ team.team_name }}</div>
                                <div class="text-xs text-gray-500 truncate">
                                    {{team.members.map(m => m.full_name).join(' & ')}}
                                </div>
                            </div>

                            <span
                                class="px-2.5 py-1 bg-blue-500 text-white text-xs font-medium rounded-full whitespace-nowrap flex-shrink-0">
                                {{ team.vndupr_avg?.toFixed(2) ?? '—' }} VNDUPR
                            </span>
                        </div>

                        <div v-if="unassignedTeams.length === 0" class="w-full p-8 text-gray-400 text-center text-sm">
                            Tất cả đội đã được phân chia vào các bảng
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Tournament Brackets Grid -->
            <div class="col-span-7 h-full">
                <div class="grid grid-cols-2 gap-4 h-full">
                    <!-- Dynamic Groups -->
                    <div v-for="group in groups" :key="group.group_id"
                        class="bg-white rounded-lg border border-gray-200 p-4 flex flex-col h-fit">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-base font-semibold text-gray-900">{{ group.group_name }}</h2>
                            <span class="px-2.5 py-1 text-white text-xs font-semibold rounded-full"
                                :class="getGroupBadgeClass(group)">
                                {{ group.teams.length }}/{{ teamsPerGroup }} Đội
                            </span>
                        </div>

                        <div class="flex-1 space-y-2 overflow-y-auto custom-scrollbar-hide transition-all duration-200"
                            @drop="onDrop($event, group.group_id)" @dragover.prevent
                            @dragenter.prevent="handleDragEnter(group.group_id)"
                            @dragleave.prevent="handleDragLeave($event, group.group_id)"
                            :class="{ 'bg-blue-50 border-2 border-dashed border-blue-300 rounded-lg p-2': isDragging && dragOverBang === group.group_id }">
                            <div v-for="team in group.teams" :key="team.team_id" :draggable="true"
                                @dragstart="onDragStart($event, team, group.group_id)" @dragend="onDragEnd"
                                @drop="onDrop($event, group.group_id, team)" @dragover.prevent="dropTargetTeam = team"
                                @dragleave="dropTargetTeam = null" @dragenter.prevent
                                class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg group hover:bg-gray-100 transition-colors cursor-move"
                                :class="{
                                    'opacity-50': draggingTeam?.team_id === team.team_id,
                                    'border-2 border-blue-400 bg-blue-50': isDragging && dropTargetTeam?.team_id === team.team_id
                                }">
                                <img :src="dotMenu" alt="">

                                <img :src="team.team_avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${team.team_id}`"
                                    :alt="team.team_name" class="w-10 h-10 rounded-full flex-shrink-0">

                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 text-sm truncate">{{ team.team_name }}</div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{team.members.map(m => m.full_name).join(' & ')}}
                                    </div>
                                </div>

                                <span
                                    class="px-2.5 py-1 bg-blue-500 text-white text-xs font-medium rounded-full whitespace-nowrap flex-shrink-0">
                                    {{ team.vndupr_avg?.toFixed(2) ?? '—' }} VNDUPR
                                </span>

                                <button @click="removeTeam(group.group_id, team.team_id)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity text-red-500 hover:text-red-600 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div v-if="group.teams.length === 0"
                                class="w-full py-12 border-2 border-dashed border-gray-300 rounded-lg text-gray-400 text-center text-xs">
                                Kéo thả đội vào đây
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { ArrowLeftIcon } from "@heroicons/vue/24/solid";
import { toast } from 'vue3-toastify';
import { useRouter, useRoute } from 'vue-router';
import dotMenu from '@/assets/images/dot-menu.svg';
import * as TournamentTypeService from '@/service/tournamentType.js'
import * as TournamentService from '@/service/tournament.js'

const router = useRouter();
const route = useRoute();
const goBack = () => router.back();

/* ================= STATE ================= */
const unassignedTeams = ref([]);
const groups = ref([]);
const teamsPerGroup = ref(3);

const draggingTeam = ref(null);
const sourceBang = ref(null);
const isDragging = ref(false);
const dragOverBang = ref(null);
const dropTargetTeam = ref(null);
const dragCounter = ref({});

const loading = ref(false);
const tournamentId = route.params.id;
const tournamentTypeId = ref(null);

/* ================= HELPERS ================= */
const findGroupById = (id) =>
    groups.value.find(g => g.group_id === id);

const resetDrag = () => {
    draggingTeam.value = null;
    sourceBang.value = null;
    dragOverBang.value = null;
    dropTargetTeam.value = null;
    dragCounter.value = {};
    isDragging.value = false;
};

const removeTeamFromSource = (team, source) => {
    if (source === 'unassigned') {
        unassignedTeams.value =
            unassignedTeams.value.filter(t => t.team_id !== team.team_id);
    } else {
        const g = findGroupById(source);
        if (!g) return;
        g.teams = g.teams.filter(t => t.team_id !== team.team_id);
    }
};

const insertTeamToGroup = (groupId, team, index = null) => {
    const g = findGroupById(groupId);
    if (!g) return;
    index === null
        ? g.teams.push(team)
        : g.teams.splice(index, 0, team);
};

/* ================= UI ================= */
const getGroupBadgeClass = (group) => {
    if (group.teams.length === teamsPerGroup.value) return 'bg-green-500';
    if (group.teams.length === 0) return 'bg-red-500';
    return 'bg-gray-400';
};

/* ================= API ================= */
const getTournamentTypeId = async (id) => {
    const res = await TournamentService.getTournamentById(id);
    tournamentTypeId.value = res?.tournament_types?.[0]?.id;
};

const fetchTeams = async () => {
    try {
        const res = await TournamentTypeService.groupWithTeamsForSort(
            tournamentTypeId.value
        );

        groups.value = res.groups || [];
        unassignedTeams.value = res.available_teams || [];

        if (res.config) {
            const total =
                unassignedTeams.value.length +
                groups.value.reduce((s, g) => s + g.teams.length, 0);

            teamsPerGroup.value =
                Math.ceil(total / (res.config.num_groups || groups.value.length));
        }
    } catch(error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi thực hiện thao tác');
    }
};

const confirmAssignments = async () => {
    loading.value = true;
    try {
        await TournamentTypeService.assignTeamsAndGenerate(
            tournamentTypeId.value,
            {
                groups: groups.value.map(g => ({
                    group_id: g.group_id,
                    team_ids: g.teams.map(t => t.team_id)
                }))
            }
        );
        toast.success('Đã lưu sắp xếp bảng đấu!');
        await fetchTeams();
    } catch(error) {
        toast.error(error.response?.data?.message || 'Không thể lưu sắp xếp bảng đấu');
    } finally {
        loading.value = false;
    }
};

/* ================= DRAG ================= */
const onDragStart = (e, team, source) => {
    draggingTeam.value = team;
    sourceBang.value = source;
    isDragging.value = true;
    e.dataTransfer.effectAllowed = 'move';
};

const onDragEnd = () => resetDrag();

const handleDragEnter = (groupId) => {
    dragCounter.value[groupId] = (dragCounter.value[groupId] || 0) + 1;
    dragOverBang.value = groupId;
};

const handleDragLeave = (e, groupId) => {
    dragCounter.value[groupId]--;
    if (dragCounter.value[groupId] === 0) dragOverBang.value = null;
};

/* ================= DROP ================= */
const onDrop = (e, targetGroupId, targetTeam = null) => {
    e.preventDefault();
    if (!draggingTeam.value) return resetDrag();

    const team = draggingTeam.value;
    const source = sourceBang.value;

    /* A. DROP TO UNASSIGNED */
    if (targetGroupId === 'unassigned') {
        removeTeamFromSource(team, source);
        unassignedTeams.value.push(team);
        return resetDrag();
    }

    const targetGroup = findGroupById(targetGroupId);
    if (!targetGroup) return resetDrag();

    /* B. REORDER SAME GROUP */
    if (source === targetGroupId && targetTeam) {
        const from = targetGroup.teams.findIndex(t => t.team_id === team.team_id);
        const to = targetGroup.teams.findIndex(t => t.team_id === targetTeam.team_id);

        targetGroup.teams.splice(from, 1);
        targetGroup.teams.splice(to, 0, team);
        return resetDrag();
    }

    /* C. MOVE (TARGET HAS SLOT) */
    if (source !== targetGroupId &&
        targetGroup.teams.length < teamsPerGroup.value) {

        removeTeamFromSource(team, source);

        const index = targetTeam
            ? targetGroup.teams.findIndex(t => t.team_id === targetTeam.team_id)
            : null;

        insertTeamToGroup(targetGroupId, team, index);
        return resetDrag();
    }

    /* D. SWAP (TARGET FULL – GIỮ POSITION) */
    if (
        source !== targetGroupId &&
        targetGroup.teams.length >= teamsPerGroup.value &&
        targetTeam
    ) {
        const targetIndex =
            targetGroup.teams.findIndex(t => t.team_id === targetTeam.team_id);

        let sourceIndex = null;
        let sourceGroup = null;

        if (source !== 'unassigned') {
            sourceGroup = findGroupById(source);
            sourceIndex = sourceGroup.teams.findIndex(
                t => t.team_id === team.team_id
            );
        }

        removeTeamFromSource(team, source);

        targetGroup.teams.splice(targetIndex, 1);
        targetGroup.teams.splice(targetIndex, 0, team);

        if (source === 'unassigned') {
            unassignedTeams.value.push(targetTeam);
        } else {
            sourceGroup.teams.splice(sourceIndex, 0, targetTeam);
        }

        return resetDrag();
    }

    resetDrag();
};

/* ================= ACTION ================= */
const removeTeam = (groupId, teamId) => {
    const g = findGroupById(groupId);
    if (!g) return;

    const idx = g.teams.findIndex(t => t.team_id === teamId);
    if (idx !== -1) {
        unassignedTeams.value.push(g.teams[idx]);
        g.teams.splice(idx, 1);
    }
};

const autoAssignTeams = async() => {
    try {
        await TournamentTypeService.autoGenerateTeamAndMatches(tournamentTypeId.value);
        toast.success('Chia bảng thành công');
        await fetchTeams();
    } catch(error) {
        toast.error(error.response?.data?.message || 'Không thể lưu sắp xếp bảng đấu');
    }
}

/* ================= INIT ================= */
onMounted(async () => {
    await getTournamentTypeId(tournamentId);
    await fetchTeams();
});
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