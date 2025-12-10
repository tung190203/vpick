<template>
    <div class="container mx-auto py-12 px-4 sm:px-6 lg:px-8 min-h-screen bg-gray-50">

        <div class="max-w-xl mx-auto bg-white rounded-xl shadow-lg p-8 text-center border border-gray-200">

            <template v-if="isLoading">
                <h2 class="text-2xl font-semibold text-gray-800">Đang tải thông tin...</h2>
            </template>

            <template v-else-if="matchData">
                <h2 class="text-3xl font-bold text-green-600 mb-4">Xác Nhận Kết Quả Trận Đấu</h2>
                <p class="text-lg text-gray-700 mb-6">Bạn vui lòng kiểm tra và xác nhận kết quả đã nhập:</p>

                <div class="bg-gray-100 p-4 rounded-lg mb-8 border border-gray-300">
                    <p class="text-xl font-bold text-gray-800">{{ matchData.home_team?.name }} vs {{
                        matchData.away_team?.name }}</p>
                    <p class="text-sm text-gray-500 mb-3">{{ matchData.round_name }}</p>
                    <div class="mt-2 text-md flex justify-center flex-wrap gap-x-4">
                        <span v-for="(set, index) in formattedScores" :key="index" class="font-medium">
                            Set {{ index + 1 }}: **{{ set.home }} - {{ set.away }}**
                        </span>
                    </div>
                </div>

                <div class="flex gap-4 justify-center">
                    <button @click="confirmMatch" :disabled="isConfirming"
                        class="flex-1 px-6 py-3 text-lg bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors disabled:opacity-50">
                        {{ isConfirming ? 'Đang gửi...' : 'Xác nhận kết quả' }}
                    </button>
                    <button @click="cancelConfirmation" :disabled="isConfirming"
                        class="flex-1 px-6 py-3 text-lg bg-gray-300 text-gray-800 rounded-lg font-medium hover:bg-gray-400 transition-colors disabled:opacity-50">
                        Hủy
                    </button>
                </div>
            </template>

            <template v-else>
                <h2 class="text-2xl font-semibold text-red-600">
                    {{ statusMessage }}
                </h2>
                <button @click="router.push({ name: 'dashboard' })"
                    class="mt-6 px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                    Về Trang Chủ
                </button>
            </template>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import * as MatchesServices from '@/service/match.js'

const route = useRoute()
const router = useRouter()
const matchId = route.params.id
const isLoading = ref(true)
const isConfirming = ref(false)
const matchData = ref(null)
const statusMessage = ref('')

// Hàm lấy dữ liệu trận đấu và kết quả đã nhập (ASYNC/AWAIT)
const fetchMatchData = async () => {
    try {
        isLoading.value = true
        const response = await MatchesServices.detailMatches(matchId)
        matchData.value = response.data
        if (matchData.value && !matchData.value.is_verifiable) {
            statusMessage.value = 'Trận đấu đã được xác nhận hoặc không cần xác nhận.'
        }
    } catch (error) {
        console.error("Error fetching match data:", error)
        statusMessage.value = error.response?.data?.message || 'Không thể tải thông tin trận đấu. Vui lòng kiểm tra lại đường link.'
        // Thiết lập matchData = null để hiển thị trạng thái lỗi
        matchData.value = null;
    } finally {
        isLoading.value = false
    }
}

// Hàm format điểm số (chuyển đổi cấu trúc API thành mảng Set: [home, away])
const formattedScores = computed(() => {
    if (!matchData.value || !matchData.value.legs || matchData.value.legs.length === 0) {
        return []
    }

    // Logic này phải khớp chính xác với cấu trúc dữ liệu JSON từ API của bạn.
    const leg = matchData.value.legs[0];
    const sets = leg.sets;

    const scoresArray = [];

    Object.keys(sets).forEach(setKey => {
        const setData = sets[setKey];
        const scores = setData[Object.keys(setData)[0]]; // Lấy mảng score

        const homeScore = scores.find(s => s.team_id === matchData.value.home_team?.id)?.score || 0;
        const awayScore = scores.find(s => s.team_id === matchData.value.away_team?.id)?.score || 0;

        scoresArray.push({ home: homeScore, away: awayScore });
    });

    return scoresArray;
})

// Hàm gửi xác nhận trận đấu
const confirmMatch = async () => {
    if (isConfirming.value) return

    try {
        isConfirming.value = true
        await MatchesServices.confirmResults(matchId)
        toast.success('Xác nhận kết quả trận đấu thành công!')

    } catch (error) {
        toast.error(error.response?.data?.message || 'Không thể xác nhận trận đấu. Vui lòng thử lại.')
    } finally {
        isConfirming.value = false
    }
}
const cancelConfirmation = () => {
    router.push({ name: 'dashboard' })
}

onMounted(async() => {
    if (matchId) {
        await fetchMatchData()
    } else {
        isLoading.value = false
        statusMessage.value = 'Trận đấu cần xác nhận không tồn tại hoặc có lỗi xảy ra.'
    }
})
</script>