<template>
    <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <!-- Backdrop with blur -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')"></div>

        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl relative z-[10000] overflow-hidden animate-in fade-in zoom-in duration-300 h-[calc(100vh-7rem)] flex flex-col">

            <!-- Header -->
            <div class="p-6 pb-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-[28px] font-bold text-[#3E414C]">Lịch đấu</h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <XMarkIcon class="w-8 h-8" stroke-width="2.5" />
                    </button>
                </div>
            </div>

            <!-- Featured Card (Fixed) -->
            <div class="px-6 pb-4">
                <div class="relative w-full rounded-2xl overflow-hidden shadow-md">
                    <div class="absolute inset-0 bg-[#D72D36]" :style="{ backgroundImage: `url(${thumbnail})` }">
                    </div>

                    <div class="relative p-6 text-white">
                        <div class="flex justify-between items-start mb-4">
                            <span
                                class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide border border-white/30">Trận
                                tiếp theo</span>
                            <div class="flex items-center gap-1 text-sm font-medium">
                                <ClockIcon class="w-4 h-4" />
                                <span>Bắt đầu sau: 45:12</span>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h2 class="text-2xl font-bold mb-1">Kèo cố định Sân số 4</h2>
                            <div class="flex items-center gap-2 opacity-90">
                                <MapPinIcon class="w-4 h-4" />
                                <span class="text-sm font-medium">Pickleball Sài Gòn Phố, Quận 7</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex -space-x-3">
                                <!-- Avatar Placeholders -->
                                <div
                                    class="w-10 h-10 rounded-full border-2 border-[#D72D36] bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold overflow-hidden">
                                    <img src="https://i.pravatar.cc/150?img=1" alt="avatar"
                                        class="w-full h-full object-cover">
                                </div>
                                <div
                                    class="w-10 h-10 rounded-full border-2 border-[#D72D36] bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold overflow-hidden">
                                    <img src="https://i.pravatar.cc/150?img=5" alt="avatar"
                                        class="w-full h-full object-cover">
                                </div>
                                <div
                                    class="w-10 h-10 rounded-full border-2 border-[#D72D36] bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold overflow-hidden">
                                    <img src="https://i.pravatar.cc/150?img=9" alt="avatar"
                                        class="w-full h-full object-cover">
                                </div>
                                <div
                                    class="w-10 h-10 rounded-full border-2 border-[#D72D36] bg-white flex items-center justify-center text-[#D72D36] text-xs font-bold">
                                    +9
                                </div>
                            </div>
                            <button
                                class="bg-white text-[#D72D36] hover:bg-gray-50 px-6 py-2.5 rounded-lg font-bold shadow-sm transition-colors">
                                Check-in ngay
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Container -->
            <div class="px-6 pb-6 flex-1 min-h-0">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 h-full">
                    <!-- Upcoming Activities -->
                    <div class="flex flex-col h-full overflow-hidden">
                        <h4 class="text-sm font-bold text-[#838799] uppercase tracking-wider mb-4 flex-shrink-0">Sắp
                            diễn ra</h4>
                        <div class="space-y-4 flex-1 overflow-y-auto custom-scrollbar pr-2">
                            <ActivitySmallCard v-for="(activity, index) in upcomingActivities" :key="index"
                                v-bind="activity" />
                            <!-- Additional mocked items to match image if needed -->
                            <div v-if="upcomingActivities.length === 0"
                                class="flex flex-col items-center justify-center py-8 text-[#838799]">
                                <CalendarIcon class="w-10 h-10 mb-2 opacity-50" />
                                <span>Chưa có lịch thi đấu sắp tới</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent History -->
                    <div class="flex flex-col h-full overflow-hidden">
                        <h4 class="text-sm font-bold text-[#838799] uppercase tracking-wider mb-4 flex-shrink-0">Lịch sử
                            gần đây
                        </h4>
                        <div class="space-y-4 flex-1 overflow-y-auto custom-scrollbar pr-2">
                            <div v-for="(history, index) in historyActivities" :key="index"
                                class="flex items-stretch justify-between p-4 bg-white rounded-lg shadow-sm border border-[#E5E7EB] transition hover:border-gray-300">
                                <div class="flex items-center space-x-4">
                                    <!-- Date Section -->
                                    <div
                                        class="flex flex-col items-center justify-center w-16 h-16 rounded-md border bg-gray-50 border-[#E5E7EB] text-[#838799]">
                                        <span class="text-xs font-bold uppercase">{{ history.day }}</span>
                                        <span class="text-2xl font-bold">{{ history.date }}</span>
                                    </div>

                                    <!-- Info Section -->
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-lg font-semibold text-[#3E414C]">{{ history.title }}
                                            </h3>
                                        </div>

                                        <div class="flex items-center space-x-4 text-sm text-[#838799]">
                                            <div class="flex items-center space-x-1">
                                                <ClockIcon class="w-4 h-4" />
                                                <span>{{ history.time }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-1 text-sm text-[#838799]">
                                            <CalendarDaysIcon class="w-4 h-4" />
                                            <span>{{ history.result }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span
                                    class="text-[10px] bg-gray-100 text-gray-500 font-bold px-2 py-0.5 rounded uppercase border border-gray-200 h-fit">
                                    {{ history.status }}
                                </span>
                            </div>

                            <div v-if="historyActivities.length === 0"
                                class="flex flex-col items-center justify-center py-8 text-[#838799]">
                                <ClockIcon class="w-10 h-10 mb-2 opacity-50" />
                                <span>Chưa có lịch sử thi đấu</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import {
    XMarkIcon,
    ClockIcon,
    MapPinIcon,
    CalendarIcon,
    CalendarDaysIcon,
} from '@heroicons/vue/24/outline'
import ActivitySmallCard from '@/components/molecules/ActivitySmallCard.vue';

defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    thumbnail: {
        type: String,
        default: ''
    },
    upcomingActivities: {
        type: Array,
        default: () => []
    },
    historyActivities: {
        type: Array,
        default: () => []
    }
})

defineEmits(['close'])
</script>
