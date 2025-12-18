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

                    <!-- Tabs -->
                    <div class="flex justify-center gap-2 px-3">
                        <button v-for="tab in tabs" :key="tab.id" @click="setActiveTab(tab.id)" :class="[
                            'px-3 py-2 rounded-full text-sm font-medium transition-colors',
                            activeTab === tab.id
                                ? 'bg-red-500 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]">
                            {{ tab.label }}
                        </button>
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
                                        <img :src="user.avatar"
                                            alt="User Avatar" class="w-full h-full object-cover" />
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
                                        <span>{{ user.age_group ?? 'Chưa rõ' }}</span>
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

const props = defineProps({
    modelValue: Boolean,
    data: Object,
    clubs: Array,
    searchQuery: String,
})

const emit = defineEmits(['update:modelValue', 'invite', 'change-scope', 'change-club', 'update:searchQuery'])

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const closeModal = () => {
    isOpen.value = false
}

const tabs = [
    { id: 'club', label: 'Trong CLB của bạn' },
    { id: 'friends', label: 'Bạn bè của bạn' },
    { id: 'area', label: 'Trong khu vực của bạn' }
]

const activeTab = ref('club')
const selectedClub = ref('')

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
</style>
