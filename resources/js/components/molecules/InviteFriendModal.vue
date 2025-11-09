<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isOpen"
                class="fixed inset-0 bg-black backdrop-blur-[1px] bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="closeModal">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-lg h-[90%] flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6">
                        <h2 class="text-xl font-semibold text-gray-800">Mời bạn bè</h2>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>
                    <!-- Search and Filter -->
                    <div class="grid grid-cols-1 gap-3 px-6 py-4">
                        <div class="relative flex items-center">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2" />
                            <input v-model="searchQuery" type="text" placeholder="Tìm kiếm" @input="onSearch"
                                class="w-full pl-10 pr-4 py-2 h-10 border border-[#EDEEF2] bg-[#EDEEF2] rounded focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder:text-[#838799]" />
                        </div>
                    </div>

                    <!-- User List -->
                    <div class="flex-1 overflow-y-auto px-6 pb-6">
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
                                    <span class="text-white font-bold text-[9px]">{{ convertLevel(user.level) }}</span>
                                </div>
                            </div>

                            <!-- User Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="font-semibold text-gray-800">{{ user.name }}</span>
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        user.status === 'open'
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'bg-green-100 text-green-700'
                                    ]">
                                        {{ user.visibility === 'open' ? 'Open' : 'Friend-Only' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-500 mt-0.5">
                                    <img :src="maleIcon" alt="male icon" class="w-4 h-4" />
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
                        <div v-if="hasMore" class="flex justify-center mt-4">
                            <button
                            @click="loadMore"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium"
                            >
                            Xem thêm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { XMarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import maleIcon from '@/assets/images/male.svg';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    data: {
        type: Object,
        default: () => ({})
    },
    hasMore: Boolean,
})

const emit = defineEmits(['update:modelValue', 'invite', 'loadMore', 'search'])

const convertLevel = (level) => {
    return parseFloat(level).toFixed(1)
}

const loadMore = () => {
  emit('loadMore')
}

const onSearch = () => {
  emit('search', searchQuery.value)
}

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const closeModal = () => {
    isOpen.value = false
}

const searchQuery = ref('')

const filteredUsers = computed(() => {
    return props.data.filter(user =>
        user.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

const inviteUser = (userId) => {
    const user = props.data.find(u => u.id === userId)
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