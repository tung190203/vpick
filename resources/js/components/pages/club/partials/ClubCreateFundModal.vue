<template>
    <Transition name="fade">
        <div v-if="isOpen" 
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click.self="close">
            <Transition name="scale">
                <div v-if="isOpen" 
                    class="bg-white rounded-[24px] w-full max-w-[850px] max-h-[90vh] transition-all duration-300 flex flex-col p-6 md:p-8 relative shadow-2xl overflow-hidden">
                    <!-- Modal Close -->
                    <button 
                        @click="close"
                        class="absolute right-8 top-8 text-gray-400 hover:text-gray-600 transition-colors z-10">
                        <XMarkIcon class="w-7 h-7" />
                    </button>

                    <!-- Modal Header -->
                    <div class="mb-8 flex-shrink-0">
                        <h2 class="text-[20px] font-bold text-[#1F2937]">Tạo Khoản Thu</h2>
                    </div>

                    <div class="flex flex-col md:flex-row gap-6 md:gap-10 min-h-0 flex-1 overflow-y-auto md:overflow-visible custom-scrollbar">
                        <!-- Left Column: Form Info -->
                        <div class="w-full md:w-[45%] flex flex-col space-y-6 md:space-y-8">
                            <div class="flex flex-col items-center">
                                <p class="font-semibold text-[#838799] uppercase tracking-wider mb-4">SỐ TIỀN MỖI NGƯỜI</p>
                                <div class="flex items-center justify-center space-x-1 border-b-2 rounded-[4px] border-[#E36C72] pb-2 px-8 w-fit mx-auto">
                                    <input 
                                        type="text" 
                                        :value="fundAmount"
                                        @input="onAmountInput"
                                        placeholder="0"
                                        class="text-[36px] md:text-[48px] font-bold text-[#2D3139] bg-transparent border-none p-0 focus:outline-none w-40 md:w-48 text-center"
                                    />
                                    <span class="text-[20px] md:text-[24px] font-bold text-[#838799]">đ</span>
                                </div>
                            </div>

                            <div class="bg-white border border-[#F2F3F5] rounded-xl shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-[#F2F3F5]">
                                    <label class="block text-[11px] font-bold text-[#838799] uppercase tracking-wider mb-2">NỘI DUNG THU</label>
                                    <input 
                                        type="text" 
                                        v-model="fundTitle"
                                        class="w-full font-semibold text-[#3E414C] bg-transparent border-none p-0 focus:outline-none placeholder:text-gray-300"
                                        placeholder="VD: Quỹ tháng 11/2024"
                                    />
                                </div>

                                <div class="p-5 flex items-center justify-between">
                                    <div>
                                        <label class="block text-[11px] font-bold text-[#838799] uppercase tracking-wider mb-2 whitespace-nowrap">HẠN CHÓT (DEADLINE)</label>
                                        <div class="flex items-center space-x-2">
                                            <CalendarIcon class="w-5 h-5 text-[#D72D36]" />
                                            <span class="font-semibold text-[#3E414C]">{{ formattedFundDeadline }}</span>
                                        </div>
                                    </div>
                                    <VueDatePicker 
                                        v-model="fundDeadline" 
                                        :enable-time-picker="false" 
                                        auto-apply
                                        :format="'dd/MM/yyyy'" 
                                        teleport="body"
                                        position="right"
                                        class="w-auto flex justify-end"
                                    >
                                        <template #trigger>
                                            <button class="p-2 bg-[#F2F3F5] rounded-[4px] hover:bg-gray-200 transition-colors">
                                                <PencilIcon class="w-5 h-5 text-[#3E414C]" />
                                            </button>
                                        </template>
                                    </VueDatePicker>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Member Selection -->
                        <div class="w-full md:w-[55%] flex flex-col min-h-[300px] md:min-h-0 md:border-l border-[#F2F3F5] md:pl-5">
                            <div class="flex items-center justify-between mb-6 flex-shrink-0">
                                <div class="flex items-center space-x-2 font-semibold text-[#838799]">
                                    <span class="uppercase tracking-wider">ÁP DỤNG CHO</span>
                                    <span>•</span>
                                    <span>{{ selectedMemberIds.length }}/{{ club.members?.length || 0 }}</span>
                                </div>
                                <button 
                                    @click="selectAllMembers"
                                    class="text-[#D72D36] text-sm font-semibold"
                                >
                                    Chọn tất cả
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-2">
                                <div 
                                    v-for="member in club.members" 
                                    :key="member.user_id"
                                    @click="toggleMemberSelection(member.user_id)"
                                    class="flex items-center justify-between p-3 hover:bg-[#F8FAFC] rounded-xl transition-colors cursor-pointer group"
                                >
                                    <div class="flex items-center space-x-4">
                                        <div class="relative">
                                            <div class="w-14 h-14 rounded-full border-2 border-white shadow-sm overflow-hidden bg-gray-100">
                                                <img :src="member.user?.avatar_url || `https://ui-avatars.com/api/?name=${member.user?.full_name || 'User'}&background=random`" class="w-full h-full object-cover" />
                                            </div>
                                            <div class="absolute bottom-0 left-0 w-4 h-4 bg-[#4392E0] text-white text-[8px] font-semibold rounded-full border border-white flex items-center justify-center">
                                                {{ Number(member.user?.sports[0].scores?.vndupr_score).toFixed(1) || 0 }}
                                            </div>
                                        </div>
                                        <span class="font-bold text-[#1F2937] text-[15px] truncate max-w-[150px] md:max-w-none">{{ member.user?.full_name || 'Thành viên' }}</span>
                                    </div>
                                    <div 
                                        class="w-6 h-6 rounded-full border-2 transition-all flex items-center justify-center"
                                        :class="selectedMemberIds.includes(member.user_id) ? 'bg-[#D72D36] border-[#D72D36]' : 'border-gray-200 group-hover:border-[#D72D36]'"
                                    >
                                        <CheckIcon v-if="selectedMemberIds.includes(member.user_id)" class="w-4 h-4 text-white stroke-[3px]" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="pt-6 md:pt-8 border-t border-[#F2F3F5] flex flex-col md:flex-row items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-normal text-[#6B6F80] tracking-wider mb-1">Tổng thu dự kiến</p>
                            <div class="flex items-baseline space-x-1">
                                <span class="text-[28px] font-bold text-[#1F2937]">{{ totalExpectedAmount }}</span>
                                <span class="text-[20px] font-bold text-[#1F2937]">đ</span>
                            </div>
                        </div>
                        <button 
                            @click="submitCreateFund"
                            class="bg-[#2D3139] text-white px-10 py-4 rounded-[4px] font-semibold hover:bg-black transition-all shadow-lg active:scale-[0.98]"
                        >
                            Gửi yêu cầu
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
    XMarkIcon,
    CalendarIcon,
    PencilIcon,
    CheckIcon
} from '@heroicons/vue/24/outline'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import dayjs from 'dayjs'

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    club: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['update:isOpen', 'submit'])

const fundAmount = ref('')
const fundTitle = ref('Quỹ tháng 11/2024')
const fundDeadline = ref(new Date())
const selectedMemberIds = ref([])

const formattedFundDeadline = computed(() => {
    return dayjs(fundDeadline.value).format('DD/MM/YYYY')
})

const close = () => {
    emit('update:isOpen', false)
}

const toggleMemberSelection = (memberId) => {
    const index = selectedMemberIds.value.indexOf(memberId)
    if (index === -1) {
        selectedMemberIds.value.push(memberId)
    } else {
        selectedMemberIds.value.splice(index, 1)
    }
}

const selectAllMembers = () => {
    if (selectedMemberIds.value.length === props.club.members?.length) {
        selectedMemberIds.value = []
    } else {
        selectedMemberIds.value = props.club.members?.map(m => m.user_id) || []
    }
}

const formatPrice = (value) => {
    if (!value) return '0'
    return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")
}

const onAmountInput = (event) => {
    // Remove all non-numeric characters
    let value = event.target.value.replace(/\D/g, '')

    // Format with dots
    if (value) {
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".")
    }

    fundAmount.value = value
}

const totalExpectedAmount = computed(() => {
    const amount = parseInt(fundAmount.value.replace(/\./g, '')) || 0
    return formatPrice(amount * selectedMemberIds.value.length)
})

const submitCreateFund = () => {
    const rawAmount = parseInt(fundAmount.value.replace(/\./g, '')) || 0
    emit('submit', {
        title: fundTitle.value,
        amount: rawAmount,
        deadline: fundDeadline.value,
        members: selectedMemberIds.value
    })
    close()
}
</script>

<style scoped>
/* Custom scrollbar for a cleaner look */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.scale-enter-active,
.scale-leave-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.scale-enter-from,
.scale-leave-to {
    opacity: 0;
    transform: scale(0.9) translateY(20px);
}
</style>
