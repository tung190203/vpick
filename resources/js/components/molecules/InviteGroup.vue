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
                        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                            'px-3 py-2 rounded-full text-sm font-medium transition-colors',
                            activeTab === tab.id
                                ? 'bg-red-500 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]">
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Search and Filter -->
                    <div class="grid grid-cols-2 gap-3 px-6 py-4">
                        <div class="relative flex items-center">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2" />
                            <input v-model="searchQuery" type="text" placeholder="Tìm kiếm"
                                class="w-full pl-10 pr-4 py-2 h-10 border border-[#EDEEF2] bg-[#EDEEF2] rounded focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder:text-[#838799]" />
                        </div>
                        <div class="flex items-center">
                            <select v-model="selectedClub"
                                class="w-full px-4 py-2 h-10 border border-[#EDEEF2] bg-[#EDEEF2] rounded focus:outline-none focus:ring-2 focus:ring-blue-500 text-[#838799]">
                                <option value="">Chọn CLB của bạn</option>
                                <option value="club1">CLB 1</option>
                                <option value="club2">CLB 2</option>
                            </select>
                        </div>
                    </div>

                    <!-- User List -->
                    <div class="flex-1 overflow-y-auto px-6 pb-6">
                        <!-- Tab 1: Trong CLB của bạn -->
                        <template v-if="activeTab === 'your-club'">
                            <div v-for="user in filteredUsers" :key="user.id"
                                class="flex items-center gap-3 py-3 border-b border-gray-100 last:border-b-0 cursor-pointer hover:bg-gray-50">
                                <!-- Avatar -->
                                <div class="relative flex-shrink-0">
                                    <div
                                        class="w-16 h-16 bg-red-300 rounded-full flex items-center justify-center overflow-hidden">
                                        <img src="https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8cHJvZmlsZSUyMGltYWdlfGVufDB8fDB8fHww&auto=format&fit=crop&w=500&q=60"
                                            alt="User Avatar" class="w-full h-full object-cover" />
                                    </div>
                                    <div
                                        class="absolute -bottom-1 -left-1 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center border border-1 border-white">
                                        <span class="text-white font-bold text-[9px]">{{ user.rating }}</span>
                                    </div>
                                </div>

                                <!-- User Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-semibold text-gray-800">{{ user.name }}</span>
                                        <span :class="[
                                            'px-2 py-0.5 rounded text-xs font-medium',
                                            user.status === 'open'
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-green-100 text-green-700'
                                        ]">
                                            {{ user.status === 'open' ? 'Open' : 'Friend-Only' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>{{ user.gender }}</span>
                                        <span>•</span>
                                        <span>{{ user.type }}</span>
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

                        <template v-else-if="activeTab === 'friends'">
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Coming Soon</h3>
                            </div>
                        </template>
                        <template v-else-if="activeTab === 'area'">
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Coming Soon</h3>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { XMarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'invite'])

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const closeModal = () => {
    isOpen.value = false
}

const tabs = [
    { id: 'your-club', label: 'Trong CLB của bạn' },
    { id: 'friends', label: 'Bạn bè của bạn' },
    { id: 'area', label: 'Trong khu vực của bạn' }
]

const activeTab = ref('your-club')
const searchQuery = ref('')
const selectedClub = ref('')

const users = ref([
    { id: 1, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'open', invited: false },
    { id: 2, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'open', invited: false },
    { id: 3, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'open', invited: false },
    { id: 4, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'friend-only', invited: false },
    { id: 5, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'friend-only', invited: false },
    { id: 6, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'friend-only', invited: false },
    { id: 7, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'friend-only', invited: false },
    { id: 8, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'friend-only', invited: false },
    { id: 9, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'friend-only', invited: false },
    { id: 10, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'friend-only', invited: false },
    { id: 11, name: 'ABC XYZ', rating: 4.5, gender: 'Nam', type: 'Người lớn', status: 'friend-only', invited: false },
])

const filteredUsers = computed(() => {
    return users.value.filter(user =>
        user.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

const inviteUser = (userId) => {
    const user = users.value.find(u => u.id === userId)
    if (user) {
        user.invited = true
        emit('invite', user)
    }
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