<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isOpen"
                class="fixed inset-0 bg-black backdrop-blur-[1px] bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="closeModal">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl h-[95vh] max-h-[95vh] flex flex-col">
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <h2 class="text-2xl font-semibold text-gray-800">{{ data.round_name || 'Trận đấu' }}</h2>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>
                    
                    <div class="p-6 overflow-y-auto flex-1">
                        <div class="grid grid-cols-[2fr_3fr] gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Chọn sân</label>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <ClipboardIcon class="w-6 h-6" />
                                            <span class="text-gray-700">Sân số</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button @click="decrementCourt"
                                                class="w-8 h-8 flex items-center justify-center bg-gray-800 text-white rounded hover:bg-gray-700 transition-colors">
                                                <span class="text-lg">−</span>
                                            </button>
                                            <input v-model.number="courtNumber" type="text"
                                                class="w-16 text-center border border-gray-300 rounded px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                            <button @click="incrementCourt"
                                                class="w-8 h-8 flex items-center justify-center bg-gray-800 text-white rounded hover:bg-gray-700 transition-colors">
                                                <span class="text-lg">+</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-gray-700 mb-1">
                                    <CalendarDaysIcon class="w-5 h-5" />
                                    <span>{{ formatEventDate(tournament.start_date) }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-gray-700">
                                    <MapPinIcon class="w-5 h-5" />
                                    <span class="truncate w-64">{{ tournament.competition_location?.name }}</span>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-600 mb-3">
                                        Kết quả kèo đấu được ghi nhận khi tất cả người chơi đã quét mã QR
                                    </p>
                                    <div class="w-full h-auto p-3 rounded-lg flex items-center justify-center">
                                        <qrcode-vue :value="qrCodeUrl" :size="250" level="H" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Chọn đội</label>
                                <div class="grid grid-cols-[2fr_1fr_2fr] gap-4 items-stretch mb-6">
                                    <div class="border border-1 border-[#DCDEE6] bg-[#F2F7FC] rounded-lg p-3 flex flex-col">
                                        <p class="text-center mb-4">{{ data.home_team?.name || 'Team A' }}</p>
                                        <div class="flex gap-2 justify-around items-stretch">
                                            <UserCard v-for="member in data.home_team?.members || []" :key="member.id"
                                                :name="member.name" :avatar="member.avatar" :size="12" :badgeSize="5" class="cursor-pointer" />
                                            <UserCard v-for="n in emptySlots('home')" :key="'empty-home-' + n" empty
                                                :size="12" :badgeSize="5" class="cursor-pointer" />
                                        </div>
                                    </div>

                                    <div class="flex justify-center items-center">
                                        <span class="text-sm font-bold">VS</span>
                                    </div>

                                    <div class="border border-1 border-[#DCDEE6] bg-[#F2F7FC] rounded-lg p-3 flex flex-col">
                                        <p class="text-center mb-4">{{ data.away_team?.name || 'Team B' }}</p>
                                        <div class="flex gap-2 justify-around items-stretch">
                                            <UserCard v-for="member in data.away_team?.members || []" :key="member.id"
                                                :name="member.name" :avatar="member.avatar" :size="12" :badgeSize="5" class="cursor-pointer" />
                                            <UserCard v-for="n in emptySlots('away')" :key="'empty-away-' + n" empty
                                                :size="12" :badgeSize="5" class="cursor-pointer" />
                                        </div>
                                    </div>
                                </div>
                                
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Kết quả</label>
                                
                                <div v-for="(score, index) in scores" :key="index" class="mb-4">
                                    <div class="grid grid-cols-[2fr_1fr_2fr] gap-4 items-center">
                                        <div class="border border-1 border-[#DCDEE6] rounded-lg p-3">
                                            <button @click="incrementScore(index, 'A')"
                                                class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors mb-2 flex items-center justify-center">
                                                <PlusIcon class="w-5 h-5" />
                                            </button>
                                            <div class="text-center text-2xl font-bold mb-2">{{ score.teamA }}</div>
                                            <button @click="decrementScore(index, 'A')"
                                                class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors flex items-center justify-center">
                                                <MinusIcon class="w-5 h-5" />
                                            </button>
                                        </div>
                                        
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="text-sm font-semibold">Set {{ index + 1 }}</span>
                                            <button v-if="scores.length > 1" @click="removeSet(index)"
                                                class="text-red-500 hover:text-red-700 transition-colors">
                                                <XMarkIcon class="w-5 h-5" />
                                            </button>
                                        </div>
                                        
                                        <div class="border border-1 border-[#DCDEE6] rounded-lg p-3">
                                            <button @click="incrementScore(index, 'B')"
                                                class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors mb-2 flex items-center justify-center">
                                                <PlusIcon class="w-5 h-5" />
                                            </button>
                                            <div class="text-center text-2xl font-bold mb-2">{{ score.teamB }}</div>
                                            <button @click="decrementScore(index, 'B')"
                                                class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors flex items-center justify-center">
                                                <MinusIcon class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <button @click="addSet" :disabled="isMaxSets"
                                    class="w-full flex justify-center items-center gap-2 border p-3 rounded-lg text-[#838799] hover:bg-gray-100 transition-colors mb-6 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <PlusIcon class="w-5 h-5" />
                                    <span class="text-sm font-semibold">
                                        Thêm set {{ isMaxSets ? `(Tối đa ${maxSets} sets)` : '' }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-4 py-4 bg-white rounded-b-lg border-t">
                        <div class="flex gap-3">
                            <button @click="saveMatch" :disabled="isSaving"
                                class="px-12 py-3 bg-red-500 text-white rounded font-medium hover:bg-red-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ isSaving && !canConfirmMatch ? 'Đang lưu...' : 'Lưu' }}
                            </button>

                            <button @click="confirmMatchResult" 
                                :disabled="isSaving || !canConfirmMatch"
                                class="px-12 py-3 bg-green-500 text-white rounded font-medium hover:bg-green-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ isSaving && canConfirmMatch ? 'Đang xác nhận...' : 'Xác nhận kết quả' }}
                            </button>
                            
                            <button @click="closeModal" :disabled="isSaving"
                                class="px-12 py-3 bg-gray-200 text-gray-700 rounded font-medium hover:bg-gray-300 transition-colors disabled:opacity-50">
                                Hủy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { MinusIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/solid'
import { ClipboardIcon, CalendarDaysIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import UserCard from './UserCard.vue'
import { formatEventDate } from '@/composables/formatDatetime.js'
import QrcodeVue from 'qrcode.vue'
import { toast } from 'vue3-toastify'
import * as MatchesServices from '@/service/match.js'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    data: { type: Object, default: () => ({}) },
    tournament: { type: Object, default: () => ({ player_per_team: 0 }) }
})

const emit = defineEmits(['update:modelValue', 'updated'])

const isOpen = computed({
    get: () => props.modelValue,
    set: val => emit('update:modelValue', val)
})

const isSaving = ref(false)
const courtNumber = ref(props.data.court || 1)

// Lấy số set tối đa từ tournament rules
const maxSets = computed(() => {
    return props.tournament?.tournament_type?.match_rules?.sets_per_match || 3
})

const isMaxSets = computed(() => scores.value.length >= maxSets.value)

// *** Computed Property QUAN TRỌNG: Kiểm tra điều kiện để kích hoạt nút Xác nhận ***
const canConfirmMatch = computed(() => {
    // 1. Phải có legs (kết quả đã lưu)
    if (!props.data.legs || props.data.legs.length === 0) {
        return false
    }

    // 2. Kiểm tra xem ít nhất một set trong các legs đã lưu có điểm số khác 0-0 hay không
    const leg = props.data.legs[0]
    const sets = leg.sets

    let hasNonZeroScore = false
    
    // Duyệt qua tất cả các set đã lưu
    // Ví dụ: sets = { 'set_1': { 'Set 1': [ {team_id: 1, score: 0}, {team_id: 2, score: 0} ] } }
    Object.keys(sets).forEach(setKey => {
        const setData = sets[setKey]
        // Lấy mảng scores (là giá trị của 'Set 1')
        const scoresArray = Object.values(setData)[0] 
        
        // Tìm điểm của team A và team B
        const homeScore = scoresArray.find(s => s.team_id === props.data.home_team?.id)?.score || 0
        const awayScore = scoresArray.find(s => s.team_id === props.data.away_team?.id)?.score || 0
        
        // Nếu tổng điểm khác 0, coi là đã có kết quả thực tế
        if (homeScore > 0 || awayScore > 0) {
            hasNonZeroScore = true
        }
    })

    return hasNonZeroScore // Trả về true nếu có ít nhất một set có điểm khác 0-0
})

// URL cho QR Code
const qrCodeUrl = computed(() => {
    return `${window.location.origin}/match/${props.data.id}/verify`
})

watch(() => props.data.court, (newCourt) => {
    courtNumber.value = newCourt || 1
})

const incrementCourt = () => { courtNumber.value++ }
const decrementCourt = () => { if (courtNumber.value > 1) courtNumber.value-- }

const closeModal = () => { 
    if (!isSaving.value) {
        isOpen.value = false 
    }
}

// Khởi tạo scores từ data.legs hoặc tạo set mặc định
const initializeScores = () => {
    if (props.data.legs && props.data.legs.length > 0) {
        const leg = props.data.legs[0]
        const sets = leg.sets
        const setArray = []
        
        Object.keys(sets).forEach(setKey => {
            const setData = sets[setKey]
            const setName = Object.keys(setData)[0]
            const scores = setData[setName]
            
            const homeScore = scores.find(s => s.team_id === props.data.home_team?.id)
            const awayScore = scores.find(s => s.team_id === props.data.away_team?.id)
            
            setArray.push({
                teamA: homeScore?.score || 0,
                teamB: awayScore?.score || 0
            })
        })
        
        return setArray.length > 0 ? setArray : [{ teamA: 0, teamB: 0 }]
    }
    return [{ teamA: 0, teamB: 0 }]
}

const scores = ref(initializeScores())

// Cập nhật scores khi data thay đổi
watch(() => props.data, () => {
    scores.value = initializeScores()
    courtNumber.value = props.data.court || 1
}, { deep: true })

const incrementScore = (setIndex, team) => {
    const maxPoints = props.tournament?.tournament_type?.match_rules?.max_points || 11
    if (team === 'A' && scores.value[setIndex].teamA < maxPoints) {
        scores.value[setIndex].teamA++
    } else if (team === 'B' && scores.value[setIndex].teamB < maxPoints) {
        scores.value[setIndex].teamB++
    }
}

const decrementScore = (setIndex, team) => {
    if (team === 'A' && scores.value[setIndex].teamA > 0) {
        scores.value[setIndex].teamA--
    } else if (team === 'B' && scores.value[setIndex].teamB > 0) {
        scores.value[setIndex].teamB--
    }
}

const addSet = () => {
    if (!isMaxSets.value) {
        scores.value.push({ teamA: 0, teamB: 0 })
    }
}

const removeSet = (index) => {
    if (scores.value.length > 1) {
        scores.value.splice(index, 1)
    }
}

// Chuyển đổi scores thành format API
const formatResultsForAPI = () => {
    const results = []
    
    scores.value.forEach((score, index) => {
        // Kết quả cho team A (home_team)
        results.push({
            set_number: index + 1,
            team_id: props.data.home_team.id,
            score: score.teamA
        })
        
        // Kết quả cho team B (away_team)
        results.push({
            set_number: index + 1,
            team_id: props.data.away_team.id,
            score: score.teamB
        })
    })
    
    return results
}

const saveMatch = async () => {
    if (isSaving.value) return
    
    try {
        isSaving.value = true
        
        const payload = {
            court: courtNumber.value,
            results: formatResultsForAPI()
        }
        
        const response = await MatchesServices.updateMatches(props.data.id, payload)
        
        toast.success('Cập nhật kết quả trận đấu thành công!')
        emit('updated', response.data)
        isOpen.value = false;
    } catch (error) {
        console.error('Error updating match:', error)
        toast.error(error.response?.data?.message || 'Lỗi khi cập nhật trận đấu')
    } finally {
        isSaving.value = false
    }
}

// Hàm Xác nhận Kết quả
const confirmMatchResult = async () => {
    if (isSaving.value || !canConfirmMatch.value) return
    
    try {
        isSaving.value = true
        
        const response = await MatchesServices.confirmResults(props.data.id)
        
        toast.success('Xác nhận kết quả trận đấu thành công!')
        emit('updated', response.data)
        isOpen.value = false;
        
    } catch (error) {
        console.error('Error confirming match:', error)
        toast.error(error.response?.data?.message || 'Lỗi khi xác nhận kết quả')
    } finally {
        isSaving.value = false
    }
}

// Hàm tính số slot trống
const emptySlots = (team) => {
    const members = team === 'home' 
        ? props.data.home_team?.members?.length || 0 
        : props.data.away_team?.members?.length || 0
    const slots = props.tournament.player_per_team - members
    return slots > 0 ? Array.from({ length: slots }, (_, i) => i + 1) : []
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
    transition: transform 0.3s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
    transform: scale(0.9);
}
</style>