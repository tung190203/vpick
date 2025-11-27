<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isOpen"
                class="fixed inset-0 bg-black backdrop-blur-[1px] bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="closeModal">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl h-[95vh] max-h-[95vh] flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <h2 class="text-2xl font-semibold text-gray-800">{{ data.round_name || 'Trận đấu' }}</h2>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>
                    
                    <!-- Body - Scrollable -->
                    <div class="p-6 overflow-y-auto flex-1">
                        <div class="grid grid-cols-[2fr_3fr] gap-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <!-- Court Selection -->
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

                                <!-- Date and Time -->
                                <div class="flex items-center gap-2 text-gray-700 mb-1">
                                    <CalendarDaysIcon class="w-5 h-5" />
                                    <span>{{ formatEventDate(tournament.start_date) }}</span>
                                </div>

                                <!-- Location -->
                                <div class="flex items-center gap-2 text-gray-700">
                                    <MapPinIcon class="w-5 h-5" />
                                    <span class="truncate w-64">{{ tournament.competition_location.name }}</span>
                                </div>

                                <!-- QR Code Info -->
                                <div>
                                    <p class="text-sm text-gray-600 mb-3">
                                        Kết quả kèo đấu được ghi nhận khi tất cả người chơi đã quét mã QR
                                    </p>
                                    <div
                                        class="w-full h-auto p-3 rounded-lg flex items-center justify-center">
                                        <qrcode-vue value="https:://google.com" :size="250" level="H" />
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Chọn đội</label>
                                <div class="grid grid-cols-[2fr_1fr_2fr] gap-4 items-stretch mb-6">
                                    <!-- Team A -->
                                    <div class="border border-1 border-[#DCDEE6] bg-[#F2F7FC] rounded-lg p-3 flex flex-col">
                                        <p class="text-center mb-4">{{ data.home_team?.name || 'Team A' }}</p>
                                        <div class="flex gap-2 justify-around items-stretch">
                                            <UserCard v-for="member in data.home_team?.members || []" :key="member.id"
                                                :name="member.name" :avatar="member.avatar" :size="12" :badgeSize="5" class="cursor-pointer" />
                                            <UserCard v-for="n in emptySlots('home')" :key="'empty-home-' + n" empty
                                                :size="12" :badgeSize="5" class="cursor-pointer" />
                                        </div>
                                    </div>

                                    <!-- VS -->
                                    <div class="flex justify-center items-center">
                                        <span class="text-sm font-bold">VS</span>
                                    </div>

                                    <!-- Team B -->
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
                                
                                <!-- Danh sách các set -->
                                <div v-for="(score, index) in scores" :key="index" class="mb-4">
                                    <div class="grid grid-cols-[2fr_1fr_2fr] gap-4 items-center">
                                        <!-- Team A Score -->
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
                                        
                                        <!-- Set Label -->
                                        <div class="flex justify-center">
                                            <span class="text-sm font-semibold">Set {{ index + 1 }}</span>
                                        </div>
                                        
                                        <!-- Team B Score -->
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
                                
                                <!-- Thêm set -->
                                <button @click="addSet"
                                    class="w-full flex justify-center items-center gap-2 border p-3 rounded-lg text-[#838799] hover:bg-gray-100 transition-colors mb-6">
                                    <PlusIcon class="w-5 h-5" />
                                    <span class="text-sm font-semibold">Thêm set</span>
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <!-- <div class="flex gap-3 mt-6">
                            <button @click="addMatch"
                                class="px-4 bg-red-500 text-white py-3 rounded font-medium hover:bg-red-600 transition-colors">
                                Thêm trận đấu
                            </button>
                        </div> -->
                    </div>
                    
                    <!-- Footer - Sticky -->
                    <div class="px-4 py-4 bg-white rounded-b-lg">
                        <div class="flex gap-3">
                            <button @click="addMatch"
                                class="px-12 py-3 bg-red-500 text-white rounded font-medium hover:bg-red-600 transition-colors">
                                Lưu
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

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    data: { type: Object, default: () => ({}) },
    tournament: { type: Object, default: () => ({ player_per_team: 0 }) }
})

const emit = defineEmits(['update:modelValue', 'create'])

const isOpen = computed({
    get: () => props.modelValue,
    set: val => emit('update:modelValue', val)
})

const closeModal = () => { isOpen.value = false }

const courtNumber = ref(props.data.court || 1)

watch(() => props.data.court, (newCourt) => {
    courtNumber.value = newCourt || 1
})

const incrementCourt = () => { courtNumber.value++ }
const decrementCourt = () => { if (courtNumber.value > 1) courtNumber.value-- }

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
}, { deep: true })

const incrementScore = (setIndex, team) => {
    if (team === 'A') {
        scores.value[setIndex].teamA++
    } else {
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
    scores.value.push({ teamA: 0, teamB: 0 })
}

const addPlayer = () => { emit('create', { action: 'add-player' }) }
const addMatch = () => {
    emit('create', {
        action: 'add-match',
        data: { courtNumber: courtNumber.value, scores: scores.value }
    })
}

// Hàm tính số slot trống
const emptySlots = (team) => {
    const members = team === 'home' ? props.data.home_team?.members?.length || 0 : props.data.away_team?.members?.length || 0
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