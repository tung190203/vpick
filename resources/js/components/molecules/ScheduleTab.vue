<template>
    <div class="flex items-center justify-between border-b border-[#BBBFCC] px-3 py-4 mb-4" v-if="isCreator">
        <p class="font-semibold uppercase">Người chơi tự nhập điểm</p>
        <button @click="$emit('handleToggle')"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
            :class="toggle ? 'bg-[#D72D36]' : 'bg-gray-300'">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                :class="toggle ? 'translate-x-6' : 'translate-x-1'" />
        </button>
    </div>
    <div class="flex justify-start gap-2 mb-4">
        <button v-for="tab in scheduleTabs" :key="tab.id" @click="scheduleActiveTab = tab.id" :class="[
            'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
            scheduleActiveTab === tab.id
                ? 'bg-[#D72D36] text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        ]">
            {{ tab.label }}
        </button>
    </div>
    <template v-if="scheduleActiveTab === 'ranking'">
        <template v-if="data.tournament_types?.[0]?.format === 2 || data.tournament_types?.[0]?.format === 3">
            <div class="rounded-md bg-[#dcdee6] shadow-md border border-[#dcdee6] mx-2" v-if="rank && rank.rankings">
                <div class="flex justify-between items-center px-4 py-2 text-[#838799]">
                    <p class="font-semibold text-sm">Đội</p>
                    <p class="font-semibold text-sm">Điểm</p>
                </div>
                <div class="rounded-md bg-[#EDEEF2]">
                    <div v-for="(team, index) in rank.rankings" :key="team.team_id"
                        class="px-4 py-2 flex justify-between items-center text-[#6B6F80] hover:text-[#4392E0] hover:bg-blue-100 cursor-pointer"
                        :class="{ 'rounded-tl-md rounded-tr-md': index === 0 }">
                        <div class="flex items-center gap-3">
                            <span class="text-sm">{{ index + 1 }}</span>
                            <div class="flex items-center gap-2">
                                <img :src="team.logo || 'https://placehold.co/400x400'" class="w-8 h-8 rounded-full"
                                    alt="logo team" />
                                <p class="font-medium text-sm">{{ team.team_name }}</p>
                            </div>
                        </div>
                        <p class="font-semibold text-[20px]">{{ team.total_set_points }}</p>
                    </div>
                </div>
            </div>
        </template>
        <template v-else-if="data.tournament_types?.[0]?.format === 1">
            <div v-for="group in rank.group_rankings" :key="group.group_id"
                class="rounded-md bg-[#dcdee6] shadow-md border border-[#dcdee6] mx-2 mb-4">
                <div class="flex justify-between items-center px-4 py-2 text-[#838799]">
                    <p class="font-semibold text-sm">{{ group.group_name }}</p>
                    <p class="font-semibold text-sm">Điểm</p>
                </div>
                <div class="rounded-md bg-[#EDEEF2]">
                    <div v-for="(team, index) in group.rankings" :key="team.team_id"
                        class="px-4 py-2 flex justify-between items-center text-[#6B6F80] hover:text-[#4392E0] hover:bg-blue-100 cursor-pointer"
                        :class="{ 'rounded-tl-md rounded-tr-md': index === 0 }">
                        <div class="flex items-center gap-3">
                            <span class="text-sm">{{ index + 1 }}</span>
                            <div class="flex items-center gap-2">
                                <img :src="team.logo || 'https://placehold.co/400x400'" class="w-8 h-8 rounded-full"
                                    alt="logo team" />
                                <p class="font-medium text-sm">{{ team.team_name }}</p>
                            </div>
                        </div>
                        <p class="font-semibold text-[20px]">{{ team.total_set_points }}</p>
                    </div>
                </div>
            </div>
        </template>
        <template v-else>
            <p class="text-center text-gray-500">Không có dữ liệu bảng xếp hạng.</p>
        </template>
    </template>
    <template v-else-if="scheduleActiveTab === 'matches'">
        <p class="text-center text-gray-500">Chức năng lịch thi đấu đang được phát triển.</p>
    </template>
</template>

<script setup>
import { ref } from 'vue'
import { SCHEDULE_TABS } from '@/data/tournament/index.js'
const scheduleTabs = SCHEDULE_TABS

const scheduleActiveTab = ref('ranking')

const props = defineProps({
    isCreator: {
        type: Boolean,
        default: false
    },
    toggle: {
        type: Boolean,
        required: true
    },
    rank: {
        type: Object,
        required: true
    },
    data: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['handleToggle'])
</script>