<template>
    <div class="m-4 max-w-8xl h-[calc(100vh-7rem)] rounded-md flex flex-col relative">
        <!-- Header Section -->
        <div class="bg-club-default text-white rounded-[16px] shadow-lg p-8 relative overflow-hidden flex flex-col justify-between"
            :style="{ backgroundImage: `url(${Background})` }">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center space-x-3">
                    <ArrowLeftIcon class="w-6 h-6 cursor-pointer text-white" @click="goBack" />
                    <p class="text-2xl font-semibold">Thu Chi</p>
                </div>
                <div class="flex items-center space-x-1 relative rounded-[8px] bg-[#3E414C] p-3 cursor-pointer" @click="showQRModal = true">
                    <QRCodeIcon class="w-6 h-6 text-white" />
                </div>
            </div>
            
            <div class="flex items-end justify-between">
                <div>
                    <p class="text-sm font-medium text-white opacity-60 mb-1">Quỹ chung hiện có</p>
                    <div class="flex items-baseline space-x-2">
                        <p class="text-[64px] font-bold leading-tight">5.240.000</p>
                        <p class="text-xs font-semibold text-[#00B377]">VND</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <div class="flex flex-col items-start bg-[#3E414C]/80 backdrop-blur-sm p-6 rounded-2xl min-w-[280px] space-y-2 border border-white/10">
                        <div class="flex items-center space-x-2 text-[#4ADE80] font-semibold text-sm">
                            <ArrowDownIcon class="w-4 h-4" />
                            <p>Thu tháng này</p>
                        </div>
                        <div class="flex items-baseline space-x-1">
                            <p class="text-3xl font-bold">+ 8.400K</p>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-start bg-[#3E414C]/80 backdrop-blur-sm p-6 rounded-2xl min-w-[280px] space-y-2 border border-white/10">
                        <div class="flex items-center space-x-2 text-[#F87171] font-semibold text-sm">
                            <ArrowUpIcon class="w-4 h-4" />
                            <p>Chi tháng này</p>
                        </div>
                        <div class="flex items-baseline space-x-1">
                            <p class="text-3xl font-bold">- 3.160K</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-12 gap-6 py-6 flex-1 overflow-hidden">
            <!-- Left Column: CẦN THANH TOÁN -->
            <div class="col-span-4 flex flex-col h-full overflow-hidden">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full overflow-hidden">
                    <!-- Section Header -->
                    <div class="p-6 pb-4">
                        <div class="flex items-center space-x-2 text-[#838799] font-semibold tracking-wide">
                            <span>CẦN THANH TOÁN</span>
                            <span class="text-[#838799]">•</span>
                            <span class="text-[#D72D36] tracking-normal">({{ 1 }})</span>
                        </div>
                    </div>

                    <!-- Items Container -->
                    <div class="flex-1 overflow-y-auto px-6 space-y-6">
                        <!-- Actionable Payment Item -->
                        <div class="space-y-4 border-b border-[#dcdee6] pb-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-[#1F2937]">Quỹ tháng 10/2024</h3>
                                    <div class="flex items-center space-x-1.5 mt-1 text-[#D72D36]">
                                        <CalendarIcon class="w-4 h-4" />
                                        <span class="text-sm font-semibold">Hạn chót: Hôm nay</span>
                                    </div>
                                </div>
                                <span class="text-[#D72D36] font-semibold">200.000đ</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <button class="py-2.5 px-4 bg-[#F2F3F5] text-[#2D3139] rounded-[4px] font-bold text-sm hover:bg-gray-200 transition-colors">
                                    Chi tiết
                                </button>
                                <button class="py-2.5 px-4 bg-[#2D3139] text-white rounded-[4px] font-bold text-sm hover:bg-black transition-colors">
                                    Thanh toán ngay
                                </button>
                            </div>
                        </div>

                        <!-- Pending Approval Card -->
                        <div class="bg-[#FDF2E2] rounded-lg p-4 border-l-[3px] border-[#F0AC3A] relative flex flex-col space-y-4 shadow-sm mb-6">
                            <div class="flex justify-between items-start">
                                <h3 class="font-semibold text-[#1F2937]">Quỹ tháng 10/2024</h3>
                                <span class="text-[#1F2937] font-semibold">200.000đ</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-[12px] text-[#A6753A] font-medium">
                                    <span>Đã chuyển khoản</span>
                                    <span class="mx-1.5 text-[#A6753A]">•</span>
                                    <span>Chờ admin xác nhận</span>
                                </div>
                                <div class="bg-[#F0A31D] text-white px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider">Chờ duyệt</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: LỊCH SỬ CỦA TÔI -->
            <div class="col-span-8 flex flex-col h-full overflow-hidden">
                <div class="bg-white rounded-[24px] shadow-sm border border-gray-50 flex-1 flex flex-col overflow-hidden">
                    <!-- Section Header -->
                    <div class="px-6 pt-6 pb-2">
                        <div class="text-[#838799] font-bold text-[15px] tracking-wide uppercase">
                            Lịch sử của tôi
                        </div>
                    </div>
                    
                    <!-- Search & Filter -->
                    <div class="px-6 py-4">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="w-5 h-5 text-[#838799]" />
                            </div>
                            <input 
                                type="text" 
                                placeholder="Tìm kiếm lịch sử giao dịch"
                                class="w-full bg-[#EDEEF2] border-none rounded-2xl py-3.5 pl-12 pr-12 text-sm focus:ring-0 placeholder:text-[#9EA2B3] placeholder:font-normal"
                            />
                            <div class="absolute inset-y-0 right-4 flex items-center">
                                <FunnelIcon class="w-5 h-5 text-[#838799] cursor-pointer" />
                            </div>
                        </div>
                    </div>

                    <!-- History List -->
                    <div class="flex-1 overflow-y-auto">
                        <div v-for="i in 2" :key="i" 
                            class="flex items-center justify-between mx-6 py-5 border-b border-[#dcdee6] hover:bg-gray-50/30 transition-colors cursor-pointer last:border-b-0">
                            <div class="flex items-center space-x-4">
                                <div class="w-11 h-11 rounded-full bg-[#E5F7ED] flex items-center justify-center text-[#10B981]">
                                    <div class="relative">
                                        <div class="w-6 h-6 border-2 border-[#10B981] rounded-full flex items-center justify-center">
                                            <span class="font-bold text-[13px]">$</span>
                                        </div>
                                        <div class="absolute -bottom-0.5 -right-0.5 bg-white rounded-full">
                                            <CheckCircleIcon class="w-4 h-4 text-[#10B981]" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p class="font-bold text-[#1F2937] text-[15px]">Quỹ tháng {{ 11 - i }}/2024</p>
                                    <p class="text-[12px] text-[#10B981] font-medium mt-0.5">Hoàn tất: 05/0{{ 10 - i }}/2024</p>
                                </div>
                            </div>
                            <span class="font-bold text-[#1F2937] text-[15px]">200.000đ</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Modal -->
        <Transition name="fade">
            <div v-if="showQRModal" 
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showQRModal = false">
                <Transition name="scale">
                    <div v-if="showQRModal" 
                        class="bg-white rounded-[24px] w-full max-w-[390px] h-[85vh] flex flex-col p-8 relative shadow-2xl overflow-hidden">
                        <!-- Modal Close -->
                        <button 
                            @click="showQRModal = false"
                            class="absolute right-8 top-8 text-gray-400 hover:text-gray-600 transition-colors z-10">
                            <XMarkIcon class="w-7 h-7" />
                        </button>

                        <!-- Modal Header -->
                        <div class="mb-6 flex-shrink-0">
                            <h2 class="text-[22px] font-bold text-[#2D3139] mb-4">Mã QR</h2>
                            <div class="flex items-center space-x-2 text-[#838799] text-sm font-semibold tracking-wider uppercase">
                                <span>MÃ QR HIỆN CÓ</span>
                                <span>•</span>
                                <span>{{ qrList.length }}</span>
                            </div>
                        </div>

                        <!-- Swiper Container -->
                        <div class="flex-1 overflow-hidden relative mb-6">
                            <div 
                                class="flex h-full transition-transform duration-500 ease-out"
                                :style="{ transform: `translateX(-${currentIndex * 100}%)` }">
                                <div v-for="(qr, index) in qrList" :key="index" class="min-w-full h-full p-1">
                                    <!-- QR Content Card -->
                                    <div class="bg-white border border-[#F2F3F5] rounded-[24px] p-6 h-full flex flex-col items-center shadow-sm">
                                        <h3 class="text-[20px] font-bold text-[#1F2937] mb-4 truncate w-full text-center">{{ qr.title }}</h3>
                                        
                                        <!-- QR Placeholder -->
                                        <div class="w-full aspect-square bg-[#F8FAFC] rounded-2xl flex items-center justify-center mb-4 overflow-hidden border border-gray-100 flex-shrink min-h-0">
                                            <img src="@/assets/images/qr_code.svg" class="w-2/3 h-2/3 opacity-90" :alt="qr.title" />
                                        </div>

                                        <div class="text-center mb-4 flex-shrink-0">
                                            <div class="flex items-center justify-center space-x-1.5 mb-1">
                                                <span class="text-[14px] font-normal text-[#1F2937]">VNĐ</span>
                                                <span class="text-[20px] font-bold text-[#4392E0]">{{ qr.amount }}</span>
                                            </div>
                                            <p class="text-[14px] text-[#838799] font-normal">{{ qr.description }}</p>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex items-center justify-center space-x-4 w-full mt-auto">
                                            <button class="w-12 h-12 rounded-full bg-[#F3F4F6] flex items-center justify-center text-[#141519] hover:bg-gray-200 transition-colors">
                                                <ArrowDownTrayIcon class="w-5 h-5" />
                                            </button>
                                            <button class="w-12 h-12 rounded-full bg-[#F3F4F6] flex items-center justify-center text-[#141519] hover:bg-gray-200 transition-colors">
                                                <ShareIcon class="w-5 h-5" />
                                            </button>
                                            <button class="w-12 h-12 rounded-full bg-[#F3F4F6] flex items-center justify-center hover:bg-gray-200 transition-colors text-[#141519]">
                                                <TrashIcon class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination & Navigation -->
                        <div class="flex items-center justify-center space-x-6 flex-shrink-0">
                            <button 
                                @click="prevQR"
                                :disabled="currentIndex === 0"
                                :class="['transition-opacity duration-300', currentIndex === 0 ? 'opacity-20 cursor-not-allowed' : 'text-[#D72D36] hover:opacity-70']">
                                <ChevronLeftIcon class="w-6 h-6" />
                            </button>
                            <div class="flex space-x-2">
                                <div 
                                    v-for="(_, index) in qrList" 
                                    :key="index"
                                    @click="currentIndex = index"
                                    :class="[
                                        'h-1.5 rounded-full transition-all duration-300 cursor-pointer',
                                        currentIndex === index ? 'w-8 bg-[#D72D36]' : 'w-1.5 bg-[#EDEEF2]'
                                    ]">
                                </div>
                            </div>
                            <button 
                                @click="nextQR"
                                :disabled="currentIndex === qrList.length - 1"
                                :class="['transition-opacity duration-300', currentIndex === qrList.length - 1 ? 'opacity-20 cursor-not-allowed' : 'text-[#D72D36] hover:opacity-70']">
                                <ChevronRightIcon class="w-6 h-6" />
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- Floating Action Buttons -->
        <div class="fixed bottom-8 right-8 flex flex-col space-y-4">
            <button class="w-12 h-12 bg-[#2D3139] text-white rounded-full flex items-center justify-center shadow-lg hover:bg-black transition-all">
                <MinusIcon class="w-6 h-6" />
            </button>
            <button class="w-12 h-12 bg-[#E36C72] text-white rounded-full flex items-center justify-center shadow-lg hover:bg-[#d05a60] transition-all">
                <PlusIcon class="w-6 h-6" />
            </button>
        </div>
    </div>
</template>

<script setup>
import Background from '@/assets/images/club-default-thumbnail.svg?url'
import {
    ArrowDownIcon,
    ArrowLeftIcon,
    ArrowUpIcon,
    CalendarIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    CheckCircleIcon,
    PlusIcon,
    MinusIcon,
    BanknotesIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
    ShareIcon,
    TrashIcon,
    ChevronLeftIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline'
import { useRouter, useRoute } from 'vue-router'
import { onMounted, ref } from 'vue'
import QRCodeIcon from "@/assets/images/qr_code.svg";

const router = useRouter()
const route = useRoute()
const clubId = route.params.id

const showQRModal = ref(false)
const currentIndex = ref(0)

const qrList = ref([
    {
        title: 'Quỹ tháng 10/2024',
        amount: '200.000',
        description: 'Pickleball SGP - Quy thang 10'
    },
    {
        title: 'Quỹ tháng 11/2024',
        amount: '200.000',
        description: 'Pickleball SGP - Quy thang 11'
    },
    {
        title: 'Quỹ tháng 12/2024',
        amount: '200.000',
        description: 'Pickleball SGP - Quy thang 12'
    }
])

const nextQR = () => {
    if (currentIndex.value < qrList.value.length - 1) {
        currentIndex.value++
    }
}

const prevQR = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--
    }
}

const goBack = () => {
    router.back()
}

onMounted(async () => {
    if (!clubId) return;
})
</script>
<style scoped>
.bg-club-default {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-color: #000;
    min-height: 286px;
}

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