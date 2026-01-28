<template>
    <div>
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div class="relative">
                <button @click="showMonthPicker = !showMonthPicker"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                    Tháng 10/2024
                    <ChevronDownIcon class="w-4 h-4 text-gray-400" />
                </button>
            </div>
            <span class="text-xs text-gray-400 italic">Cập nhật: 10p trước</span>
        </div>

        <!-- Top 3 Section -->
        <div class="flex justify-center items-end gap-6 mb-12 py-4">
            <!-- Rank 2 -->
            <div class="flex flex-col items-center flex-1 max-w-[120px]">
                <span class="text-xl font-bold text-[#4F80FF] mb-2">Top 2</span>
                <div class="relative mb-3">
                    <img src="https://i.pravatar.cc/150?u=2" alt="Rank 2"
                        class="w-20 h-20 rounded-full border-2 border-[#4F80FF] shadow-lg object-cover" />
                    <div
                        class="absolute -bottom-24 left-1/2 -translate-x-1/2 px-3 py-1 bg-[#4F80FF] text-[10px] text-white rounded-full font-bold whitespace-nowrap">
                        Win 65%
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-bold text-gray-800 text-lg leading-tight">Hoàng Lan</h3>
                    <div class="text-[#4F80FF] font-bold text-xl">1480</div>
                </div>
            </div>

            <!-- Rank 1 -->
            <div class="flex flex-col items-center flex-1 max-w-[140px] -mt-6">
                <span class="text-xl font-bold text-[#D72D36] mb-2">Top 1</span>
                <div class="relative mb-3">
                    <div class="absolute top-20 left-1/2 -translate-x-1/2">
                        <div
                            class="bg-[#D72D36] text-[10px] text-white px-3 py-1 rounded-full font-bold shadow-sm whitespace-nowrap">
                            Xuất sắc
                        </div>
                    </div>
                    <img src="https://i.pravatar.cc/150?u=1" alt="Rank 1"
                        class="w-24 h-24 rounded-full border-2 border-[#D72D36] shadow-xl object-cover" />
                    <div
                        class="absolute -bottom-[103px] left-1/2 -translate-x-1/2 px-3 py-1 bg-[#D72D36] text-[10px] text-white rounded-full font-bold whitespace-nowrap">
                        Win 72%
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-bold text-gray-800 text-xl leading-tight">Tuấn Anh</h3>
                    <div class="text-[#D72D36] font-bold text-2xl">1550</div>
                </div>
            </div>

            <!-- Rank 3 -->
            <div class="flex flex-col items-center flex-1 max-w-[120px]">
                <span class="text-xl font-bold text-[#FFB84F] mb-2">Top 3</span>
                <div class="relative mb-3">
                    <img src="https://i.pravatar.cc/150?u=3" alt="Rank 3"
                        class="w-20 h-20 rounded-full border-2 border-[#FFB84F] shadow-lg object-cover" />
                    <div
                        class="absolute -bottom-24 left-1/2 -translate-x-1/2 px-3 py-1 bg-[#FFB84F] text-[10px] text-white rounded-full font-bold whitespace-nowrap">
                        Win 60%
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-bold text-gray-800 text-lg leading-tight">Thảo Nhi</h3>
                    <div class="text-[#FFB84F] font-bold text-xl">1425</div>
                </div>
            </div>
        </div>

        <!-- Ranking List (4-10) -->
        <div class="space-y-4">
            <div v-for="item in rankingList" :key="item.rank"
                class="flex items-center justify-between pb-4 border-b border-gray-100 last:border-0 rounded-xl transition-colors px-2">
                <div class="flex items-center gap-4">
                    <span class="text-lg font-bold text-gray-400 w-6 text-center">{{ item.rank }}</span>
                    <div class="relative">
                        <img :src="item.avatar" :alt="item.name"
                            class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm" />
                        <div
                            class="absolute -bottom-1 -left-1 w-5 h-5 bg-[#4F80FF] text-[9px] text-white rounded-full flex items-center justify-center font-bold border-2 border-white">
                            4.5
                        </div>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">{{ item.name }}</h4>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400">{{ item.stats }}</span>
                            <div v-if="item.change" :class="[
                                'px-1.5 py-0.5 rounded-full text-[10px] font-bold flex items-center gap-0.5',
                                item.change > 0 ? 'bg-[#00B377] text-white' : 'bg-[#D72D36] text-white'
                            ]">
                                <component :is="item.change > 0 ? TriangleUp : TriangleDown" class="w-2 h-2" alt="" />
                                {{ Math.abs(item.change) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-bold text-gray-800 text-lg">{{ item.points }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import TriangleUp from '@/assets/images/triangle_up.svg'
import TriangleDown from '@/assets/images/triangle_down.svg'
import { ChevronDownIcon } from '@heroicons/vue/16/solid'

const showMonthPicker = ref(false)

const rankingList = [
    { rank: 4, name: 'Lê Văn Cường', avatar: 'https://i.pravatar.cc/150?u=4', stats: '18 Trận • Win 55%', points: '1390', change: 1 },
    { rank: 5, name: 'Vân Anh', avatar: 'https://i.pravatar.cc/150?u=5', stats: '18 Trận • Win 55%', points: '1390', change: 0 },
    { rank: 6, name: 'Trần Tuấn', avatar: 'https://i.pravatar.cc/150?u=6', stats: '18 Trận • Win 55%', points: '1390', change: -2 },
    { rank: 7, name: 'Vân Anh', avatar: 'https://i.pravatar.cc/150?u=7', stats: '18 Trận • Win 55%', points: '1390', change: 0 },
    { rank: 8, name: 'Vân Anh', avatar: 'https://i.pravatar.cc/150?u=8', stats: '18 Trận • Win 55%', points: '1390', change: 0 },
    { rank: 9, name: 'Vân Anh', avatar: 'https://i.pravatar.cc/150?u=9', stats: '18 Trận • Win 55%', points: '1390', change: 0 },
    { rank: 10, name: 'Vân Anh', avatar: 'https://i.pravatar.cc/150?u=10', stats: '18 Trận • Win 55%', points: '1390', change: 0 }
]
</script>
