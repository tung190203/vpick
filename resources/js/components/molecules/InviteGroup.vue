<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isOpen"
                class="fixed inset-0 bg-black backdrop-blur-[1px] bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="closeModal">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-lg h-[90%] flex flex-col">

                    <!-- Header -->
                    <div class="flex items-center justify-between p-6">
                        <h2 class="text-xl font-semibold text-gray-800">Mời nhóm</h2>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- Tabs with Swiper -->
                    <div class="px-6 pb-4">
                        <Swiper 
                            :slides-per-view="'auto'" 
                            :space-between="8" 
                            :freeMode="true"
                            :mousewheel="{ forceToAxis: true }" 
                            :modules="modules" 
                            class="swiper-container">
                            <SwiperSlide v-for="tab in tabs" :key="tab.id" class="!w-auto">
                                <button 
                                    @click="setActiveTab(tab.id)" 
                                    :class="[
                                        'px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition select-none whitespace-nowrap',
                                        activeTab === tab.id
                                            ? 'bg-red-500 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    ]">
                                    {{ tab.label }}
                                </button>
                            </SwiperSlide>
                        </Swiper>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 px-6 py-4">
                        <div :class="activeTab === 'club' ? '' : 'md:col-span-2'" class="relative flex items-center">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2" />
                            <input
                                v-model="localSearchQuery"
                                @input="onSearch"
                                type="text"
                                placeholder="Tìm kiếm"
                                class="w-full pl-10 pr-4 py-2 h-10 border border-[#EDEEF2] bg-[#EDEEF2] rounded focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder:text-[#838799]" />
                        </div>
                        <div v-if="activeTab === 'club'" class="flex items-center">
                            <select v-model="selectedClub" @change="$emit('change-club', selectedClub)"
                                class="w-full px-4 py-2 h-10 border border-[#EDEEF2] bg-[#EDEEF2] rounded focus:outline-none focus:ring-2 focus:ring-blue-500 text-[#838799]">
                                <option value="">Chọn CLB</option>
                                <option v-for="club in clubs" :key="club.id" :value="club.id">{{ club.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 pb-6">
                        <template v-if="filteredUsers.length === 0">
                            <div class="text-center text-gray-400 mt-10">
                                Không tìm thấy người dùng.
                            </div>
                        </template>

                        <template v-else>
                            <div v-for="user in filteredUsers" :key="user.id"
                                class="flex items-center gap-3 py-3 border-b border-gray-100 last:border-b-0 cursor-pointer hover:bg-gray-50">
                                <!-- Avatar -->
                                <div class="relative flex-shrink-0">
                                    <div
                                        class="w-16 h-16 bg-red-300 rounded-full flex items-center justify-center overflow-hidden">
                                        <img :src="user.avatar_url || defaultAvatar"
                                            @error="e => e.target.src = defaultAvatar"
                                            alt="User Avatar" class="w-full h-full object-cover" />
                                        <div class="absolute -bottom-1 -left-1 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center border border-1 border-white">
                                            <span class="text-white font-bold text-[9px]">{{ convertLevel(user) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- User Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-semibold text-gray-800">{{ user.name }}</span>
                                        <span :class="[
                                            'px-2 py-0.5 rounded text-xs font-medium',
                                            user.visibility === 'open'
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-green-100 text-green-700'
                                        ]">
                                            {{ user.visibility === 'open' ? 'Open' : 'Friend-Only' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-0.5">
                                        <img :src="maleIcon" alt="male icon" class="w-4 h-4" v-if="user.gender == 1"/>
                                        <img :src="femaleIcon" alt="female icon" class="w-4 h-4" v-else-if="user.gender == 2"/>
                                        <img src="" alt="" v-else>
                                        <span>{{ user.gender_text }}</span>
                                    </div>
                                </div>

                                <!-- Invite Button -->
                                <button @click="inviteUser(user.id)" :disabled="user.invited" :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors flex-shrink-0',
                                    user.invited
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-blue-500 text-white hover:bg-blue-600'
                                ]">
                                    {{ user.invited ? 'Đã mời' : 'Mời bạn' }}
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { XMarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { FreeMode, Mousewheel } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/free-mode'
import maleIcon from '@/assets/images/male.svg'
import femaleIcon from '@/assets/images/female.svg'

const defaultAvatar = "/images/default-avatar.png"
const modules = [FreeMode, Mousewheel]

const props = defineProps({
    modelValue: Boolean,
    data: Object,
    clubs: Array,
    searchQuery: String,
    activeScope: {
        type: String,
        default: 'all'
    }
})

const emit = defineEmits(['update:modelValue', 'invite', 'change-scope', 'change-club', 'update:searchQuery'])

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const closeModal = () => {
    isOpen.value = false
}

const convertLevel = (user, sportId = 1) => {
    if (!user?.sports || user.sports.length === 0) return '0'

    const sport = user.sports.find(s => s.sport_id === sportId)
    if (!sport?.scores?.vndupr_score) return '0'

    return parseFloat(sport.scores.vndupr_score).toFixed(1)
}

const tabs = [
    { id: 'all', label: 'Tất cả' },
    { id: 'club', label: 'Trong CLB của bạn' },
    { id: 'friends', label: 'Bạn bè của bạn' },
    { id: 'area', label: 'Trong khu vực của bạn' }
]

const activeTab = ref(props.activeScope || 'all')
const selectedClub = ref('')

// Watch prop để sync khi parent thay đổi
watch(() => props.activeScope, (newScope) => {
    if (newScope) {
        activeTab.value = newScope
    }
})

const localSearchQuery = ref(props.searchQuery || '')
watch(() => props.searchQuery, val => localSearchQuery.value = val)
const onSearch = () => {
    emit('update:searchQuery', localSearchQuery.value)
}

const filteredUsers = computed(() => {
    return (props.data.result || []).filter(user =>
        user.name.toLowerCase().includes(localSearchQuery.value.toLowerCase())
    )
})

const inviteUser = (userId) => {
    const user = (props.data.result || []).find(u => u.id === userId)
    if (user) {
        user.invited = true
        emit('invite', user)
    }
}

const setActiveTab = (tabId) => {
    activeTab.value = tabId
    emit('change-scope', tabId)
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

/* Swiper custom styles - Fix overflow */
.swiper-container {
    overflow: hidden;
    margin: 0;
    padding: 0;
}

:deep(.swiper-wrapper) {
    display: flex;
    align-items: center;
}

:deep(.swiper-slide) {
    width: auto !important;
    flex-shrink: 0;
}
</style>