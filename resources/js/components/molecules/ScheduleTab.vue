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
                ? 'bg-red-500 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        ]">
            {{ tab.label }}
        </button>
    </div>
    <template v-if="scheduleActiveTab === 'ranking'">
        <div class="rounded-md bg-[#dcdee6] border border-[#dcdee6]">
            <div class="flex justify-between items-center px-4 py-2">
                <p class="font-semibold text-sm">Đội</p>
                <div>Điểm</div>
            </div>

            <div class="bg-[#EDEEF2] rounded-md px-4 py-2 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="text-sm">1</span>
                    <div class="flex items-center gap-2">
                        <img src="https://placehold.co/400x400" class="w-8 h-8 rounded-full" alt="logo team" />
                        <p class="font-medium text-sm">Team A</p>
                    </div>
                </div>

                <p class="font-semibold text-[20px] text-[#4392E0]">10</p>
            </div>
        </div>
    </template>
    <template v-else-if="scheduleActiveTab === 'match'">
        <slot name="match"></slot>
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
    }
})

const emit = defineEmits(['handleToggle'])
</script>