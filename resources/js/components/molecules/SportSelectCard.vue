<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
                @click.self="closeModal">
                <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between p-6 border-b">
                        <h2 class="text-2xl font-semibold text-gray-900">Chọn môn thể thao</h2>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 200px)">
                        <div class="mb-6">
                            <input v-model="searchQuery" type="text" placeholder="Tìm kiếm..."
                                class="w-full px-4 py-3 bg-gray-100 border-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div class="md:col-span-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                                    Môn thể thao của tôi • {{ selectedSportsWithLevel.length }}
                                </h3>
                                <div class="grid grid-cols-2 gap-3 h-72 overflow-y-scroll"> 
                                    <div v-for="item in selectedSportsDisplay" :key="item.sport.id"
                                        class="bg-red-600 text-white rounded-xl py-4 px-3 flex flex-col items-center justify-center cursor-pointer hover:bg-red-700 transition-all aspect-square relative"
                                        @click="removeSport(item.sport)">
                                        
                                        <div class="w-12 h-12 mb-2 flex items-center justify-center">
                                            <img v-if="item.sport.icon" :src="item.sport.icon" alt=""
                                                class="w-full h-full object-contain filter invert" />
                                            <svg v-else class="w-10 h-10" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor">
                                                <circle cx="12" cy="12" r="10" stroke-width="2" />
                                                <path
                                                    d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                                                    stroke-width="2" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-center leading-snug">{{ item.sport.name }}</p>
                                        <p class="text-xs whitespace-nowrap mt-3">Người mới / {{ item.score }}</p>
                                    </div>
                                    
                                    <div v-if="selectedSportsWithLevel.length === 0" class="col-span-2 text-center py-8 text-gray-400 text-sm">
                                        Chưa có môn thể thao
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-8">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                                    Những môn khác • {{ filteredOtherSports.length }}
                                </h3>
                                <div class="h-72 overflow-y-scroll">
                                    <div v-if="filteredOtherSports.length > 0" class="grid grid-cols-4 gap-3">
                                        <div v-for="sport in filteredOtherSports" :key="sport.id"
                                            class="bg-white border-2 border-gray-200 rounded-xl p-4 flex flex-col items-center justify-center cursor-pointer hover:border-red-500 hover:bg-red-50 transition-all aspect-square"
                                            @click="selectSport(sport)">
                                            
                                            <div class="w-12 h-12 mb-2 flex items-center justify-center">
                                                <img v-if="sport.icon" :src="sport.icon" alt=""
                                                    class="w-full h-full object-contain" />
                                                <svg v-else class="w-10 h-10 text-gray-400" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor">
                                                    <circle cx="12" cy="12" r="10" stroke-width="2" />
                                                    <path
                                                        d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                                                        stroke-width="2" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-semibold text-center text-gray-900 leading-snug">{{ sport.name }}</p>
                                        </div>
                                    </div>
                                    
                                    <div v-else class="text-center py-8 text-gray-400 text-sm">
                                        Không có môn thể thao nào thỏa mãn tìm kiếm.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-6">
                            <p class="text-gray-500 mb-2">Không tìm thấy môn yêu thích của bạn?</p>
                            <button class="text-[#4392E0] text-sm hover:underline">
                                Yêu cầu môn thể thao
                            </button>
                        </div>
                    </div>

                    <div class="p-6 flex justify-center">
                        <button @click="showConfirmModal = true"
                            class="bg-red-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors shadow-lg">
                            Lưu thay đổi
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="showLevelModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black bg-opacity-50"
                @click.self="closeLevelModal">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full">
                    <div class="p-6 text-center border-b">
                        <h2 class="text-2xl font-semibold text-gray-900">
                            Chọn trình độ {{ selectedSportForLevel?.name }}
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-5 gap-3 mb-4">
                            <button v-for="level in levels" :key="level"
                                class="border-2 border-gray-300 rounded-xl py-3 text-xl font-bold text-gray-900 hover:border-red-500 hover:bg-red-50 transition-all"
                                :class="{ 'border-red-500 bg-red-50': selectedLevel === level }"
                                @click="confirmLevel(level)">
                                {{ level }}
                            </button>
                        </div>

                        <button @click="closeLevelModal"
                            class="w-full border-2 border-gray-300 rounded-xl py-4 text-lg font-semibold text-gray-900 hover:bg-gray-50 transition-all">
                            Hủy
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black bg-opacity-50"
                @click.self="closeConfirmModal">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center">

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Xác nhận lưu thay đổi
                    </h2>

                    <p class="text-gray-600 mb-6">
                        Bạn có chắc chắn muốn lưu các thay đổi môn thể thao?
                    </p>

                    <div class="flex gap-3">
                        <button @click="closeConfirmModal"
                            class="flex-1 border-2 border-gray-300 rounded-xl py-3 font-semibold text-gray-900 hover:bg-gray-50">
                            Hủy
                        </button>

                        <button @click="confirmSave"
                            class="flex-1 bg-red-600 text-white rounded-xl py-3 font-semibold hover:bg-red-700">
                            Xác nhận
                        </button>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { XMarkIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    mySports: { type: Array, default: () => [] },
    allSports: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'save']);

const searchQuery = ref('');
const selectedSportsWithLevel = ref([]);
const showLevelModal = ref(false);
const selectedSportForLevel = ref(null);
const selectedLevel = ref(null);
const showConfirmModal = ref(false);
const levels = [1, 2, 3, 4, 5];

// Khởi tạo từ mySports (bao gồm cả score hiện tại)
watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        selectedSportsWithLevel.value = props.mySports.map(s => {
            const personalScore = 
                s.scores?.find(x => x.personal_score?.score_value)?.personal_score?.score_value ||
                s.scores?.find(x => x.score_type === 'personal_score')?.score_value ||
                1;
            return {
                sport_id: s.sport_id || s.id,
                score_value: personalScore
            };
        });
    }
});

// Computed để hiển thị môn đã chọn với thông tin đầy đủ
const selectedSportsDisplay = computed(() => {
    return selectedSportsWithLevel.value.map(item => {
        const sport = props.allSports.find(s => s.id === item.sport_id);
        return { sport: sport || {}, score: item.score_value };
    }).filter(item => item.sport.id);
});

// Lọc môn chưa chọn
const filteredOtherSports = computed(() => {
    const query = searchQuery.value.toLowerCase();
    const selectedIds = selectedSportsWithLevel.value.map(s => s.sport_id);
    return props.allSports.filter(sport =>
        !selectedIds.includes(sport.id) &&
        sport.name.toLowerCase().includes(query)
    );
});

const selectSport = (sport) => {
    selectedSportForLevel.value = sport;
    selectedLevel.value = null;
    showLevelModal.value = true;
};

const confirmLevel = (level) => {
    if (selectedSportForLevel.value) {
        selectedSportsWithLevel.value.push({
            sport_id: selectedSportForLevel.value.id,
            score_value: level
        });
        closeLevelModal();
    }
};

const removeSport = (sport) => {
    selectedSportsWithLevel.value = selectedSportsWithLevel.value.filter(
        item => item.sport_id !== sport.id
    );
};

const closeModal = () => {
    searchQuery.value = '';
    emit('close');
};

const closeLevelModal = () => {
    showLevelModal.value = false;
    selectedSportForLevel.value = null;
    selectedLevel.value = null;
};

const closeConfirmModal = () => {
    showConfirmModal.value = false;
};

const confirmSave = () => {
    const sportIds = selectedSportsWithLevel.value.map(s => s.sport_id);
    const scoreValues = selectedSportsWithLevel.value.map(s => s.score_value);
    
    emit('save', { sport_ids: sportIds, score_value: scoreValues });
    showConfirmModal.value = false;
    closeModal();
};
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
    transform: scale(0.95);
}
</style>