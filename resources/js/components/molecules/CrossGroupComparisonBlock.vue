<template>
    <div v-if="showSkeleton || visible">
        <!-- Skeleton loading -->
        <div v-if="loading && !comparisonData" class="space-y-3 animate-pulse">
            <div class="h-32 bg-gray-200 rounded-lg"></div>
            <div class="h-16 bg-gray-200 rounded-lg"></div>
            <div class="h-16 bg-gray-200 rounded-lg"></div>
        </div>

        <!-- Main block khi applied = true -->
        <div v-else-if="comparisonData?.applied" class="space-y-3">
            <!-- Header card -->
            <div class="bg-gradient-to-br from-[#FFF5F5] to-white border border-[#FBD5D5] rounded-lg p-4">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <h3 class="font-semibold text-gray-900 text-base">
                        Xét đội nhì tốt nhất — {{ comparisonData.qualification.additional_slots }} suất
                    </h3>
                    <button v-if="onShowRule" @click="onShowRule"
                        class="text-xs text-[#4392E0] underline shrink-0">Thể lệ</button>
                </div>

                <p class="text-sm text-gray-600 mb-2">
                    {{ comparisonData.qualification.runner_up_candidates }} đội nhì đi thẳng vào vòng loại trực tiếp.
                    Còn {{ comparisonData.qualification.additional_slots }} suất, hệ thống xét các đội nhì để chọn ra
                    đội tốt nhất.
                </p>

                <p class="text-xs text-gray-500 italic">
                    {{ comparisonData.comparison_rule.description }}
                </p>
            </div>

            <!-- Candidate list -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden divide-y divide-gray-100">
                <div v-for="candidate in comparisonData.candidates" :key="candidate.team.id + '-' + candidate.candidate_type"
                    class="px-4 py-3 cursor-pointer hover:bg-blue-50 transition-colors"
                    @click="openCandidateDetail(candidate)">
                    <div class="grid grid-cols-[40px_1fr_auto] items-center gap-3">
                        <!-- Rank -->
                        <span class="font-bold text-lg text-gray-700">{{ candidate.rank }}</span>

                        <!-- Team info -->
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate">{{ candidate.team.name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ candidate.group.name }} · {{ candidate.group.team_count }} đội →
                                <span v-if="candidate.has_excluded_matches" class="text-orange-600 font-medium">
                                    {{ candidate.matches.counted }} trận được xét (đã loại {{ candidate.matches.excluded }})
                                </span>
                                <span v-else>
                                    giữ nguyên cả {{ candidate.matches.counted }} trận
                                </span>
                            </p>
                        </div>

                        <!-- Stats + status badge -->
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <div class="flex items-center gap-2 text-xs">
                                <span class="font-semibold text-blue-600">{{ formatPercent(candidate.statistics.win_rate) }}</span>
                                <span class="font-semibold"
                                    :class="candidate.statistics.average_point_difference >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ formatPointDiff(candidate.statistics.average_point_difference) }}
                                </span>
                            </div>
                            <span :class="getStatusBadgeClass(candidate)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                                {{ getStatusLabel(candidate) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="!comparisonData.candidates.length" class="p-6 text-center text-gray-500 text-sm">
                    Không có đội Nhì/Ba nào để xét.
                </div>
            </div>
        </div>

        <!-- Applied = false: không hiển thị gì (rule chưa áp dụng hoặc đã tắt) -->
        <div v-else></div>

        <!-- Detail Modal -->
        <Teleport to="body">
            <div v-if="selectedCandidate" class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center bg-black/40"
                @click.self="closeCandidateDetail">
                <div class="bg-white w-full sm:max-w-lg sm:rounded-lg rounded-t-2xl max-h-[90vh] overflow-y-auto shadow-xl"
                    @click.stop>
                    <!-- Modal Header -->
                    <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 v-if="detailLoading" class="font-semibold text-gray-900 truncate">
                                <span class="inline-block w-32 h-5 bg-gray-200 rounded animate-pulse"></span>
                            </h3>
                            <h3 v-else-if="candidateDetail" class="font-semibold text-gray-900 truncate">
                                {{ candidateDetail.team.name }}
                            </h3>
                            <p v-if="candidateDetail" class="text-xs text-gray-500 mt-1">
                                Nhì {{ candidateDetail.group.name }} · bảng có {{ candidateDetail.group.team_count }} đội
                                <span v-if="candidateDetail.matches.find(m => !m.included)">
                                    nên bị loại {{ candidateDetail.comparison.excluded_matches }} trận để so công bằng
                                </span>
                            </p>
                        </div>
                        <button @click="closeCandidateDetail" class="ml-2 shrink-0 p-1 hover:bg-gray-100 rounded">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-4 space-y-4">
                        <!-- Loading -->
                        <div v-if="detailLoading" class="space-y-3 animate-pulse">
                            <div class="h-16 bg-gray-200 rounded"></div>
                            <div class="h-12 bg-gray-200 rounded"></div>
                            <div class="h-12 bg-gray-200 rounded"></div>
                        </div>

                        <template v-else-if="candidateDetail">
                            <!-- Summary stats -->
                            <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                                <p class="text-xs text-gray-500 uppercase font-semibold">Tóm tắt</p>
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div>
                                        <p class="text-lg font-bold text-gray-800">{{ candidateDetail.comparison.counted_matches }}</p>
                                        <p class="text-xs text-gray-500">trận được xét</p>
                                    </div>
                                    <div>
                                        <p class="text-lg font-bold text-blue-600">{{ formatPercent(candidateDetail.comparison.win_rate) }}</p>
                                        <p class="text-xs text-gray-500">tỉ lệ thắng</p>
                                    </div>
                                    <div>
                                        <p class="text-lg font-bold"
                                            :class="candidateDetail.comparison.average_point_difference >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ formatPointDiff(candidateDetail.comparison.average_point_difference) }}
                                        </p>
                                        <p class="text-xs text-gray-500">hiệu số TB</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Match list -->
                            <div>
                                <p class="text-xs font-semibold uppercase text-gray-500 mb-2">Các trận vòng bảng</p>
                                <div class="space-y-2">
                                    <div v-for="match in candidateDetail.matches" :key="match.id"
                                        class="border rounded-lg p-3"
                                        :class="match.included ? 'border-gray-200 bg-white' : 'border-gray-200 bg-gray-50 opacity-60'">
                                        <div class="flex items-center justify-between">
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-sm truncate"
                                                    :class="match.included ? 'text-gray-800' : 'text-gray-500 line-through'">
                                                    {{ match.opponent.name }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    <span v-if="match.opponent_group_position">Hạng {{ match.opponent_group_position }} bảng</span>
                                                </p>
                                            </div>
                                            <div class="text-right shrink-0 ml-2">
                                                <p class="font-bold text-sm">{{ match.score }}</p>
                                                <p v-if="!match.included" class="text-[10px] font-bold text-orange-600 uppercase mt-0.5">LOẠI</p>
                                                <p v-else class="text-[10px] font-bold uppercase mt-0.5"
                                                    :class="match.result === 'win' ? 'text-green-600' : match.result === 'loss' ? 'text-red-600' : 'text-gray-500'">
                                                    {{ resultLabel(match.result) }}
                                                </p>
                                            </div>
                                        </div>
                                        <p v-if="!match.included && match.exclusion_reason" class="text-xs text-gray-500 italic mt-2">
                                            {{ match.exclusion_reason }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Explanation -->
                            <div v-if="hasExcludedMatch" class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                                <p class="text-sm font-semibold text-orange-800 mb-1">Vì sao loại trận này?</p>
                                <p class="text-xs text-orange-700 leading-relaxed">
                                    Bảng {{ candidateDetail.group.name }} có {{ candidateDetail.group.team_count }} đội,
                                    trong khi bảng nhỏ nhất chỉ có {{ candidateDetail.comparison.minimum_group_size }} đội.
                                    Nếu giữ cả {{ candidateDetail.comparison.original_matches }} trận thì đội nhì
                                    bảng {{ candidateDetail.group.name }} có lợi thế hơn. Vì vậy trận gặp đội xếp cuối bảng được loại để
                                    mọi đội nhì đều được xét trên cùng số trận.
                                    <br><br>
                                    Kết quả thật của trận này vẫn được giữ nguyên trong bảng {{ candidateDetail.group.name }} và trong điểm trình người chơi.
                                </p>
                            </div>
                        </template>

                        <!-- Error -->
                        <div v-else-if="detailError" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                            <p class="text-sm text-red-700">{{ detailError }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { toast } from 'vue3-toastify';
import * as TournamentTypeService from '@/service/tournamentType.js';

const props = defineProps({
    tournamentTypeId: { type: [Number, String], default: null },
    tournamentId: { type: [Number, String], default: null },
    /** Optional callback khi user click "Thể lệ" */
    onShowRule: { type: Function, default: null },
});

const comparisonData = ref(null);
const loading = ref(false);
const error = ref(null);

const selectedCandidate = ref(null);
const candidateDetail = ref(null);
const detailLoading = ref(false);
const detailError = ref(null);

const hasExcludedMatch = computed(() => {
    return candidateDetail.value?.matches?.some((m) => !m.included) || false;
});

/**
 * Component hiển thị khi:
 * - Có tournamentTypeId hợp lệ
 * - Đang loading HOẶC đã load xong
 * (skeleton loading sẽ hiển thị trong lúc fetch)
 */
const showSkeleton = computed(() => {
    return !!props.tournamentTypeId && loading.value && !comparisonData.value;
});

/**
 * Component chỉ hiển thị block khi:
 * - tournamentTypeId hợp lệ
 * - data.applied = true
 */
const visible = computed(() => {
    return !!props.tournamentTypeId && !!comparisonData.value?.applied;
});

const fetchComparison = async () => {
    if (!props.tournamentTypeId) return;

    loading.value = true;
    error.value = null;
    try {
        const data = await TournamentTypeService.getCrossGroupComparison(props.tournamentTypeId);
        comparisonData.value = data;
    } catch (err) {
        comparisonData.value = null;
        error.value = err.response?.data?.message || 'Không thể tải dữ liệu xét Nhì/Ba';
        // Silent error: không hiển thị toast nếu applied=false, chỉ log
        if (err.response?.status !== 404) {
            console.error('CrossGroupComparison fetch error:', err);
        }
    } finally {
        loading.value = false;
    }
};

const openCandidateDetail = async (candidate) => {
    selectedCandidate.value = candidate;
    candidateDetail.value = null;
    detailError.value = null;
    detailLoading.value = true;

    try {
        const detail = await TournamentTypeService.getCrossGroupComparisonTeamMatches(
            props.tournamentTypeId,
            candidate.team.id
        );
        candidateDetail.value = detail;
    } catch (err) {
        detailError.value = err.response?.data?.message || 'Không thể tải chi tiết trận';
        toast.error(detailError.value);
    } finally {
        detailLoading.value = false;
    }
};

const closeCandidateDetail = () => {
    selectedCandidate.value = null;
    candidateDetail.value = null;
    detailError.value = null;
};

const formatPercent = (val) => {
    if (val === null || val === undefined) return '0%';
    return `${Number(val).toFixed(1).replace(/\.0$/, '')}%`;
};

const formatPointDiff = (val) => {
    if (val === null || val === undefined) return '0';
    const v = Number(val);
    const sign = v >= 0 ? '+' : '';
    // If integer, no decimal
    if (Number.isInteger(v)) {
        return `${sign}${v}`;
    }
    return `${sign}${v.toFixed(1)}`;
};

const resultLabel = (result) => {
    switch (result) {
        case 'win': return 'Thắng';
        case 'loss': return 'Thua';
        default: return 'Hòa';
    }
};

const getStatusLabel = (candidate) => {
    if (candidate.pending_draw) return 'Chờ bốc thăm';
    if (candidate.status === 'qualified') return 'Đi tiếp';
    if (candidate.status === 'not_qualified') return 'Không đi tiếp';
    return '';
};

const getStatusBadgeClass = (candidate) => {
    if (candidate.pending_draw) return 'bg-orange-100 text-orange-700';
    if (candidate.status === 'qualified') return 'bg-green-100 text-green-700';
    if (candidate.status === 'not_qualified') return 'bg-gray-100 text-gray-600';
    return 'bg-gray-100 text-gray-500';
};

// Watcher để re-fetch khi tournamentTypeId thay đổi
watch(() => props.tournamentTypeId, (newId) => {
    if (newId) fetchComparison();
}, { immediate: false });

onMounted(() => {
    if (props.tournamentTypeId) fetchComparison();
});
</script>

<style scoped>
/* Smooth fade-in cho modal */
.fixed {
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>