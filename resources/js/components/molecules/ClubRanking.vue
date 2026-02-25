<template>
    <div>
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div class="relative">
                <button @click="showMonthPicker = !showMonthPicker"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                    {{ filters.month ? `Tháng ${filters.month}/${filters.year}` : 'Chọn thời gian' }}
                    <ChevronDownIcon class="w-4 h-4 text-gray-400" />
                </button>

                <!-- Simple Month Picker Dropdown -->
                <div v-if="showMonthPicker" class="absolute left-0 mt-2 w-64 bg-white border border-gray-200 rounded-xl shadow-xl z-50 p-4">
                    <div class="flex items-center justify-between mb-4">
                        <button @click="changeYear(-1)" class="p-1 hover:bg-gray-100 rounded">
                            <ChevronLeftIcon class="w-4 h-4" />
                        </button>
                        <span class="font-bold text-gray-800">{{ tempYear }}</span>
                        <button @click="changeYear(1)" class="p-1 hover:bg-gray-100 rounded">
                            <ChevronRightIcon class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="m in 12" :key="m" @click="selectMonth(m)"
                            class="py-2 text-sm rounded-lg transition-colors"
                            :class="m === tempMonth ? 'bg-[#D72D36] text-white' : 'hover:bg-gray-100 text-gray-600'">
                            Tháng {{ m }}
                        </button>
                    </div>
                </div>
            </div>
            <span class="text-xs text-gray-400 italic">Cập nhật lúc: {{ formattedUpdateTime }}</span>
        </div>

        <!-- Top 3 Section -->
        <div v-if="topThree.length > 0" class="flex justify-center items-end gap-2 sm:gap-6 mb-16 pt-12">

            <!-- Rank 2 (Silver) -->
            <div v-if="topThree[1]" class="flex flex-col items-center flex-1 max-w-[120px] relative">
                <div class="text-[#4F80FF] font-bold text-sm mb-1 uppercase tracking-wide">Top 2</div>
                <div class="relative w-32 h-32 sm:w-36 sm:h-36 flex items-center justify-center mb-2">
                    <!-- Frame -->
                    <img src="@/assets/ranking/silver.png" alt="Silver Frame"
                        class="absolute inset-0 w-full h-full object-contain z-20 pointer-events-none" />
                    <!-- Avatar -->
                    <img :src="topThree[1].user.avatar_url || defaultAvatar" alt="Rank 2"
                        class="absolute rounded-full object-cover z-10"
                        style="width: 47%; height: 47%; top: 57%; left: 50%; transform: translate(-50%, -60%);" />
                    <!-- Badge -->
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-[#475569] text-[10px] text-white rounded-full font-bold whitespace-nowrap z-30">
                        Win {{ topThree[1].monthly_stats?.win_rate || 0 }}%
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-800 text-lg leading-tight line-clamp-1">{{ topThree[1].user.full_name }}</h3>
                    <div class="text-[#4F80FF] font-bold text-xl">{{ topThree[1].vndupr_score }}</div>
                </div>
            </div>

            <!-- Rank 1 (Gold) -->
            <div v-if="topThree[0]" class="flex flex-col items-center flex-1 max-w-[160px] relative -mt-8">
                <div class="text-[#D72D36] font-black text-base mb-1 uppercase tracking-wide">Top 1</div>
                <div class="relative w-40 h-40 sm:w-48 sm:h-48 flex items-center justify-center mb-4">
                    <!-- Glow -->
                    <div class="absolute inset-0 rounded-full animate-pulse-subtle"></div>
                    <!-- Frame -->
                    <img src="@/assets/ranking/gold.png" alt="Gold Frame"
                        class="absolute inset-0 w-full h-full object-contain z-20 pointer-events-none" />
                    <!-- Avatar -->
                    <img :src="topThree[0].user.avatar_url || defaultAvatar" alt="Rank 1"
                        class="absolute rounded-full object-cover z-10"
                        style="width: 48%; height: 48%; top: 57%; left: 50%; transform: translate(-50%, -62%);" />
                        
                    <!-- Badge -->
                    <div class="absolute bottom-1 left-1/2 -translate-x-1/2 px-4 py-1 bg-[#ca8a04] text-[10px] text-white rounded-full font-black whitespace-nowrap z-30 border border-yellow-300">
                        Win {{ topThree[0].monthly_stats?.win_rate || 0 }}%
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-800 text-xl leading-tight line-clamp-1">{{ topThree[0].user.full_name }}</h3>
                    <div class="text-[#D72D36] font-bold text-2xl">{{ topThree[0].vndupr_score }}</div>
                </div>
            </div>

            <!-- Rank 3 (Bronze) -->
            <div v-if="topThree[2]" class="flex flex-col items-center flex-1 max-w-[120px] relative">
                <div class="text-[#FFB84F] font-bold text-sm mb-1 uppercase tracking-wide">Top 3</div>
                <div class="relative w-32 h-32 sm:w-36 sm:h-36 flex items-center justify-center mb-2">
                    <!-- Frame -->
                    <img src="@/assets/ranking/bronze.png" alt="Bronze Frame"
                        class="absolute inset-0 w-full h-full object-contain z-20 pointer-events-none" />
                    <!-- Avatar -->
                    <img :src="topThree[2].user.avatar_url || defaultAvatar" alt="Rank 3"
                        class="absolute rounded-full object-cover z-10"
                        style="width: 47%; height: 47%; top: 57%; left: 50%; transform: translate(-50%, -60%);" />
                    <!-- Badge -->
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-[#92400e] text-[10px] text-white rounded-full font-bold whitespace-nowrap z-30">
                        Win {{ topThree[2].monthly_stats?.win_rate || 0 }}%
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-800 text-lg leading-tight line-clamp-1">{{ topThree[2].user.full_name }}</h3>
                    <div class="text-[#FFB84F] font-bold text-xl">{{ topThree[2].vndupr_score }}</div>
                </div>
            </div>
        </div>

        <!-- Ranking List (>= 4 or Paginated) -->
        <div class="relative min-h-[300px]">
             <!-- Loading Overlay -->
            <div v-if="loading" 
                class="absolute inset-0 z-10 flex justify-center items-start pt-12 bg-white/60 backdrop-blur-[1px] transition-all duration-300 rounded-xl">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>

            <div :class="{ 'opacity-40 pointer-events-none': loading }" class="transition-opacity duration-300">
                <div class="space-y-4 mb-8">
                    <template v-if="leaderboard.length > 0">
                        <div v-for="item in leaderboard" :key="item.member_id"
                            class="flex items-center justify-between pb-4 border-b border-gray-100 last:border-0 rounded-xl transition-colors px-2">
                            <div class="flex items-center gap-4">
                                <span class="text-lg font-bold text-gray-400 w-6 text-center">{{ item.rank }}</span>
                                <div class="relative">
                                    <img :src="item.user.avatar_url || defaultAvatar" :alt="item.user.full_name"
                                        class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm" />
                                    <div
                                        class="absolute -bottom-1 -left-1 w-5 h-5 bg-[#4F80FF] text-[9px] text-white rounded-full flex items-center justify-center font-bold border-2 border-white">
                                        {{ Number(item.vndupr_score).toFixed(1) }}
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 line-clamp-1">{{ item.user.full_name }}</h4>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-400">{{ item.monthly_stats?.matches_played || 0 }} Trận • Win {{ item.monthly_stats?.win_rate || 0 }}%</span>
                                        <div v-if="item.monthly_stats?.score_change" :class="[
                                            'px-1.5 py-0.5 rounded-full text-[10px] font-bold flex items-center gap-0.5',
                                            item.monthly_stats.score_change >= 0 ? 'bg-[#00B377] text-white' : 'bg-[#D72D36] text-white'
                                        ]">
                                            <component :is="item.monthly_stats.score_change >= 0 ? TriangleUp : TriangleDown" class="w-2 h-2" />
                                            {{ Math.abs(item.monthly_stats.score_change) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-[#3E414C] text-lg">{{ item.vndupr_score }}</div>
                            </div>
                        </div>
                    </template>

                    <div v-if="leaderboard.length === 0 && topThree.length === 0" class="text-center py-12 text-gray-400">
                        Chưa có dữ liệu xếp hạng
                    </div>
                </div>

                <!-- Pagination -->
                <Pagination :meta="meta" @page-change="changePage" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import TriangleUp from '@/assets/images/triangle_up.svg'
import TriangleDown from '@/assets/images/triangle_down.svg'
import { ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid'
import Pagination from '@/components/molecules/Pagination.vue'
import dayjs from 'dayjs'

const props = defineProps({
    topThree: {
        type: Array,
        default: () => []
    },
    leaderboard: {
        type: Array,
        default: () => []
    },
    meta: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            total: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            month: dayjs().month() + 1,
            year: dayjs().year()
        })
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['filter', 'page-change'])

const defaultAvatar = 'https://picki.vn/images/default-avatar.png'
const showMonthPicker = ref(false)
const tempMonth = ref(props.filters.month)
const tempYear = ref(props.filters.year)

const formattedUpdateTime = computed(() => {
    return dayjs().format('HH:mm')
})

const selectMonth = (month) => {
    tempMonth.value = month
    emit('filter', { month: tempMonth.value, year: tempYear.value })
    showMonthPicker.value = false
}

const changeYear = (delta) => {
    tempYear.value += delta
}

const changePage = (page) => {
    if (page >= 1 && page <= props.meta.last_page) {
        emit('page-change', page)
    }
}

watch(() => props.filters, (newFilters) => {
    tempMonth.value = newFilters.month
    tempYear.value = newFilters.year
}, { deep: true })
</script>

<style scoped>
@keyframes pulse-subtle {
    0%, 100% { opacity: 0.8; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.02); }
}

.animate-pulse-subtle {
    animation: pulse-subtle 3s ease-in-out infinite;
}

.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>