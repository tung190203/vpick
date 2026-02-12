<template>
    <div class="mt-8 bg-white rounded-2xl shadow-sm flex flex-col">
        <div class="flex border-b border-gray-200 mx-2">
            <button v-for="tab in tabs" :key="tab.id" @click="handleTabClick(tab.id)"
                class="flex-1 py-4 text-center font-semibold transition-all relative"
                :class="activeTab === tab.id ? 'text-[#D72D36]' : 'text-[#838799]'">
                {{ tab.name }}
                <div v-if="activeTab === tab.id" class="absolute bottom-0 left-0 w-full h-1 bg-[#D72D36]"></div>
            </button>
        </div>

        <div class="relative">
            <div class="p-8 transition-all duration-500 ease-in-out"
                :class="{ 'overflow-hidden': shouldLimitHeight }"
                :style="{ maxHeight: shouldLimitHeight ? (isExpanded ? contentHeight + 'px' : '300px') : 'none' }"
                ref="contentWrapper">
                <!-- Tab Content -->
                <div v-show="activeTab === 'intro'" ref="introContent" class="min-h-[200px] h-[200px]">
                    <div v-if="club?.profile?.description" class="space-y-6 h-full overflow-y-auto">
                        {{ club.profile.description }}
                    </div>

                    <div v-else class="flex items-center justify-center h-full text-gray-400 text-sm italic">
                        Chưa có mô tả
                    </div>
                </div>

                <div v-show="activeTab === 'members'" class="text-gray-400">
                    <template v-if="hasMembersTabBeenActive">
                        <ClubMember v-if="club?.id" :club-id="club.id" :isJoined="isJoined" :currentUserRole="currentUserRole" @refresh-club="$emit('refresh-club')" />
                        <div v-else class="text-center py-12">
                            <p class="text-gray-400">Đang tải...</p>
                        </div>
                    </template>
                </div>

                <div v-show="activeTab === 'ranking'">
                    <ClubRanking 
                        :top-three="topThree"
                        :leaderboard="leaderboard"
                        :meta="leaderboardMeta"
                        :filters="leaderboardFilters"
                        :loading="leaderboardLoading"
                        @filter="$emit('leaderboard-filter', $event)"
                        @page-change="$emit('leaderboard-page-change', $event)"
                    />
                </div>
            </div>

            <!-- Fade effect when collapsed - only for intro tab -->
            <div v-if="!isExpanded && needsExpand && activeTab === 'intro'"
                class="absolute bottom-0 left-0 w-full h-24 bg-gradient-to-t from-white to-transparent pointer-events-none">
            </div>
        </div>

        <!-- Expand/Collapse button - only for intro tab -->
        <div v-if="needsExpand && activeTab === 'intro'" class="px-8 pb-8 flex justify-start">
            <button @click="toggleExpand"
                class="text-[#D72D36] font-semibold flex items-center gap-1 hover:underline transition-colors">
                {{ isExpanded ? 'Ẩn bớt' : 'Xem thêm' }}
                <component :is="isExpanded ? ChevronUpIcon : ChevronDownIcon" class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/24/outline'
import ClubMember from '@/components/molecules/ClubMember.vue'
import ClubRanking from '@/components/molecules/ClubRanking.vue'

const activeTab = ref('intro')
const isExpanded = ref(false)
const contentHeight = ref(1000)
const contentWrapper = ref(null)
const introContent = ref(null)
const hasMembersTabBeenActive = ref(false)

const props = defineProps({
    club: {
        type: Object,
        default: () => ({})
    },
    isJoined: {
        type: Boolean,
        default: false
    },
    currentUserRole: {
        type: String,
        default: null
    },
    topThree: {
        type: Array,
        default: () => []
    },
    leaderboard: {
        type: Array,
        default: () => []
    },
    leaderboardMeta: {
        type: Object,
        default: () => ({})
    },
    leaderboardFilters: {
        type: Object,
        default: () => ({})
    },
    leaderboardLoading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['leaderboard-filter', 'leaderboard-page-change', 'tab-change', 'refresh-club'])

const tabs = computed(() => [
    { id: 'intro', name: 'Giới thiệu' },
    { id: 'members', name: `Thành viên (${props.club?.quantity_members || 0})` },
    { id: 'ranking', name: 'BXH' }
])

// Only apply height limit to intro tab
const shouldLimitHeight = computed(() => {
    return activeTab.value === 'intro'
})

const needsExpand = computed(() => {
    return activeTab.value === 'intro' && contentHeight.value > 300
})

const updateContentHeight = async () => {
    await nextTick()
    if (activeTab.value === 'intro' && introContent.value) {
        contentHeight.value = introContent.value.scrollHeight + 64
    }
}

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value
}

const handleTabClick = (tabId) => {
    activeTab.value = tabId
    if (tabId === 'members') {
        hasMembersTabBeenActive.value = true
    }
    emit('tab-change', tabId)
}

watch(activeTab, () => {
    isExpanded.value = false
    updateContentHeight()
})

onMounted(() => {
    updateContentHeight()
    window.addEventListener('resize', updateContentHeight)
})
</script>