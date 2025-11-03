<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isOpen"
                class="fixed inset-0 bg-black backdrop-blur-[1px] bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="closeModal">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[95vh] flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6">
                        <h2 class="text-2xl font-semibold text-gray-800">Trận đấu số 1</h2>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto">
                        <div class="grid grid-cols-[2fr_3fr] gap-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <!-- Court Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Chọn sân</label>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
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
                                <div>
                                    <div class="flex items-center gap-2 text-gray-700 mb-1">
                                        <CalendarDaysIcon class="w-5 h-5" />
                                        <span>T4 20 Tháng 8 lúc 17:00</span>
                                    </div>
                                </div>

                                <!-- Location -->
                                <div>
                                    <div class="flex items-center gap-2 text-gray-700">
                                        <MapPinIcon class="w-5 h-5" />
                                        <span>Sân Pickleball Thăng Long</span>
                                    </div>
                                </div>

                                <!-- QR Code Info -->
                                <div>
                                    <p class="text-sm text-gray-600 mb-3">
                                        Kết quả kèo đấu được ghi nhận khi tất cả người chơi đã quét mã QR
                                    </p>
                                    <div
                                        class="bg-blue-500 w-full h-auto p-6 rounded-lg flex items-center justify-center">
                                        <QrCodeIcon class="w-full h-auto text-white" />
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Chọn đội</label>
                                <div class="grid grid-cols-[2fr_1fr_2fr] gap-4 items-center mb-6">
                                    <div class="border border-1 border-[#DCDEE6] bg-[#F2F7FC] rounded-lg p-3">
                                        <p class="text-center mb-4">Team A</p>
                                        <div>
                                            <UserCard @click="addPlayer" empty :size="12" :badgeSize="5"
                                                class="cursor-pointer" />
                                        </div>
                                    </div>
                                    <div class="flex justify-center">
                                        <span class="text-sm font-bold">VS</span>
                                    </div>
                                    <div class="border border-1 border-[#DCDEE6] bg-[#F2F7FC] rounded-lg p-3">
                                        <p class="text-center mb-4">Team B</p>
                                        <UserCard @click="addPlayer" empty :size="12" :badgeSize="5"
                                            class="cursor-pointer" />
                                    </div>
                                </div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Kết quả</label>
                                <div class="grid grid-cols-[2fr_1fr_2fr] gap-4 items-center mb-6">
                                    <div class=" border border-1 border-[#DCDEE6] rounded-lg p-3">
                                        <button
                                            class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors mb-2 flex items-center justify-center">
                                            <PlusIcon class="w-5 h-5" />
                                        </button>
                                        <div class="text-center text-2xl font-bold mb-2">{{ scores[0].teamA
                                        }}</div>
                                        <button
                                            class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors flex items-center justify-center">
                                            <MinusIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <div class="flex justify-center">
                                        <span class="text-sm font-semibold">Set</span>
                                    </div>
                                    <div class=" border border-1 border-[#DCDEE6] rounded-lg p-3">
                                        <button
                                            class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors mb-2 flex items-center justify-center">
                                            <PlusIcon class="w-5 h-5" />
                                        </button>
                                        <div class="text-center text-2xl font-bold mb-2">{{ scores[0].teamA
                                        }}</div>
                                        <button
                                            class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors flex items-center justify-center">
                                            <MinusIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-[2fr_1fr_2fr] gap-4 items-center mb-6">
                                    <div class=" border border-1 border-[#DCDEE6] rounded-lg p-3">
                                        <button
                                            class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors mb-2 flex items-center justify-center">
                                            <PlusIcon class="w-5 h-5" />
                                        </button>
                                        <div class="text-center text-2xl font-bold mb-2">{{ scores[0].teamA
                                        }}</div>
                                        <button
                                            class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors flex items-center justify-center">
                                            <MinusIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <div class="flex justify-center">
                                        <span class="text-sm font-semibold">Set</span>
                                    </div>
                                    <div class=" border border-1 border-[#DCDEE6] rounded-lg p-3">
                                        <button
                                            class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors mb-2 flex items-center justify-center">
                                            <PlusIcon class="w-5 h-5" />
                                        </button>
                                        <div class="text-center text-2xl font-bold mb-2">{{ scores[0].teamA
                                        }}</div>
                                        <button
                                            class="w-full bg-[#EDEEF2] rounded px-3 py-2 text-gray-600 hover:bg-gray-300 transition-colors flex items-center justify-center">
                                            <MinusIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                                <button class="w-full flex justify-center items-center gap-2 border p-3 rounded-lg text-[#838799] hover:bg-gray-100 transition-colors">
                                    <PlusIcon class="w-5 h-5" />
                                    <span class="text-sm font-semibold">Thêm hiệp</span>
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3 mt-6">
                            <button @click="addMatch"
                                class="px-4 bg-red-500 text-white py-3 rounded font-medium hover:bg-red-600 transition-colors">
                                Thêm trận đấu
                            </button>
                            <button @click="shareMatch"
                                class="px-4 bg-gray-200 text-gray-700 py-3 rounded font-medium hover:bg-gray-300 transition-colors">
                                Chia sẻ link trận đấu
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { MinusIcon, PlusIcon, QrCodeIcon } from '@heroicons/vue/24/solid'
import { CalendarDaysIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import { ref, computed } from 'vue'
import UserCard from './UserCard.vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'create'])

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const closeModal = () => {
    isOpen.value = false
}

const courtNumber = ref(1)
const scores = ref([
    { teamA: 0, teamB: 15 },
    { teamA: 0, teamB: 12 }
])

const incrementCourt = () => {
    courtNumber.value++
}

const decrementCourt = () => {
    if (courtNumber.value > 1) {
        courtNumber.value--
    }
}

const addPlayer = () => {
    emit('create', { action: 'add-player' })
}

const addMatch = () => {
    emit('create', {
        action: 'add-match',
        data: {
            courtNumber: courtNumber.value,
            scores: scores.value
        }
    })
}

const shareMatch = () => {
    // Emit event to share match
    emit('create', { action: 'share-match' })
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