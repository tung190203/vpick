<template>
    <Transition name="fade">
        <div v-if="isOpen" 
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click.self="closeModal">
            <Transition name="scale">
                <div v-if="isOpen" 
                    class="bg-[#F8F9FD] rounded-2xl w-full max-w-[1000px] h-[90vh] max-h-[800px] transition-all duration-300 flex flex-col relative shadow-2xl overflow-hidden">
                    
                    <!-- Modal Header -->
                    <div class="p-6 px-8 flex items-center justify-between border-b border-gray-100 bg-white flex-shrink-0">
                        <h2 class="text-xl font-bold text-[#2D3139]">Chi Tiết Đợt Thu</h2>
                        <button 
                            @click="closeModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="flex flex-1 min-h-0">
                        <!-- Left Sidebar: Collections List -->
                        <div v-if="fundCollections && fundCollections.length > 0" class="w-[320px] bg-white border-r border-gray-100 flex flex-col overflow-hidden">
                            <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                                <div 
                                    v-for="collection in fundCollections" 
                                    :key="collection.id"
                                    @click="selectCollection(collection.id)"
                                    :class="[
                                        'p-4 rounded cursor-pointer transition-all duration-200 relative group flex items-center',
                                        selectedCollectionId === collection.id 
                                            ? 'bg-gray-50 shadow-sm' 
                                            : 'bg-white hover:bg-gray-50'
                                    ]"
                                >
                                    <div class="flex-1 flex flex-col min-w-0">
                                        <div class="flex justify-between items-start mb-1">
                                            <h3 :class="[
                                                'font-bold text-[15px] flex-1 mr-2 transition-colors line-clamp-1 max-w-[200px]',
                                                selectedCollectionId === collection.id ? 'text-[#1F2937]' : 'text-gray-500'
                                            ]" v-tooltip="collection.title">
                                                {{ collection.title }}
                                            </h3>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-[14px] font-bold text-[#D72D36]">{{ formatCurrency(collection.amount_per_member) }}đ</span>
                                            <span class="text-[12px] text-gray-400">/người</span>
                                        </div>
                                    </div>
                                    <ChevronRightIcon class="w-4 h-4 text-gray-400 group-hover:translate-x-0.5 transition-transform flex-shrink-0" />
                                </div>
                            </div>
                        </div>

                        <!-- Right Content: Detailed Stats & Members -->
                        <div :class="['flex-1 bg-[#F8F9FD] overflow-y-auto p-4 custom-scrollbar', { 'w-full': !fundCollections || fundCollections.length === 0 }]">
                            <div v-if="isLoading" class="flex flex-col items-center justify-center h-full space-y-4">
                                <div class="animate-spin rounded-full h-12 w-12 border-4 border-[#D72D36] border-t-transparent"></div>
                                <p class="text-gray-500 font-medium">Đang tải dữ liệu...</p>
                            </div>

                            <template v-else-if="details">
                                <div :class="['space-y-6', fundCollections && fundCollections.length > 0 ? 'max-w-3xl mx-auto' : 'w-full px-4']">
                                    <!-- Summary Section (Top) -->
                                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                                        <div class="p-4">
                                            <div class="mb-4">
                                                <h3 class="text-xl font-semibold text-[#3E414C] mb-2 line-clamp-1" v-tooltip="details.collection.title">{{ details.collection.title }}</h3>
                                                <div class="flex items-baseline space-x-1">
                                                    <span class="text-xl font-semibold text-[#D72D36]">{{ formatCurrency(details.collection.amount_per_member) }}đ</span>
                                                    <span class="text-sm text-[#838799] font-normal">/người</span>
                                                </div>
                                            </div>

                                            <div class="space-y-1">
                                                <div class="flex justify-between items-center text-[15px] font-medium">
                                                    <span class="text-sm text-[#838799] font-semibold">Tiến độ thu</span>
                                                    <span class="text-[#D72D36] font-semibold text-sm">{{ Number(details.collection.progress_percentage).toFixed(2) }}%</span>
                                                </div>
                                                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div 
                                                        class="h-full bg-[#D72D36] rounded-full transition-all duration-700 ease-out"
                                                        :style="{ width: details.collection.progress_percentage + '%' }"
                                                    ></div>
                                                </div>
                                                <div class="flex justify-between text-xs text-[#838799] font-normal">
                                                    <span>0đ</span>
                                                    <span>Đã thu: {{ details.collection.confirmed_count }}/{{ details.collection.assigned_members_count }} người</span>
                                                    <span>Mục tiêu</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-3 border-t border-gray-100 bg-[#FFFFFF]">
                                            <div class="my-5 flex flex-col items-center justify-center">
                                                <p class="text-sm font-normal text-[#838799] uppercase tracking-wider mb-2">Đã thu</p>
                                                <p class="font-bold text-[#00B377]">{{ formatShortCurrency(details.collection.collected_amount) }}</p>
                                            </div>
                                            <div class="my-5 flex flex-col items-center justify-center border-x border-gray-100">
                                                <p class="text-sm font-normal text-[#838799] uppercase tracking-wider mb-2">Còn thiếu</p>
                                                <p class="font-bold text-[#D72D36]">{{ formatShortCurrency(details.collection.target_amount - details.collection.collected_amount) }}</p>
                                            </div>
                                            <div class="my-5 flex flex-col items-center justify-center">
                                                <p class="text-sm font-normal text-[#838799] uppercase tracking-wider mb-2">Hạn chót</p>
                                                <p class="font-bold text-[#3E414C]">{{ formatDate(details.collection.end_date) }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tabs & Member Lists -->
                                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                        <!-- Tabs Navigation -->
                                        <div class="flex border-b border-gray-50 p-2 bg-gray-50/30">
                                            <button 
                                                v-for="tab in [{k: 'paid', l: `Đã nộp (${details.summary.approved_count || 0})`}, {k: 'unpaid', l: `Chưa nộp (${(details.summary.waiting_approval_count || 0) + (details.summary.no_payment_count || 0)})`}]"
                                                :key="tab.k"
                                                @click="activeTab = tab.k"
                                                :class="[
                                                    'flex-1 py-4 px-6 text-sm font-bold transition-all relative',
                                                    activeTab === tab.k 
                                                        ? 'text-[#D72D36]' 
                                                        : 'text-gray-400 hover:text-gray-600'
                                                ]"
                                            >
                                                {{ tab.l }}
                                                <div v-if="activeTab === tab.k" class="absolute bottom-0 left-8 right-8 h-1 bg-[#D72D36]"></div>
                                            </button>
                                        </div>

                                        <!-- Members List List -->
                                        <div class="divide-y divide-gray-50">
                                            <div v-if="activeTab === 'paid'">
                                                <div v-if="!details.approved_payments?.length" class="text-center py-16">
                                                    <DocumentMagnifyingGlassIcon class="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                                    <p class="text-gray-400 text-sm font-medium">Chưa có ai nộp</p>
                                                </div>
                                                <div v-for="member in details.approved_payments" :key="member.id" 
                                                    class="flex items-center justify-between p-5 hover:bg-gray-50 group transition-colors">
                                                    <div class="flex items-center space-x-4">
                                                        <div class="relative">
                                                            <img :src="member.user?.avatar_url || 'https://ui-avatars.com/api/?name=' + member.user?.full_name" class="w-14 h-14 rounded-full border-2 border-gray-50 group-hover:border-white transition-all shadow-sm" />
                                                        </div>
                                                        <div>
                                                            <p class="font-bold text-[16px] text-[#1F2937]">{{ member.user?.full_name }}</p>
                                                            <p class="text-xs text-gray-400 mt-0.5">Xác nhận: {{ formatDatetime(member.paid_at) }}</p>
                                                        </div>
                                                    </div>
                                                    <span class="text-[12px] font-bold text-[#10B981] bg-[#10B981]/5 px-4 py-1.5 rounded-full border border-[#10B981]/10">Đã xác nhận</span>
                                                </div>
                                            </div>

                                            <div v-else>
                                                <!-- Need Confirmation Section -->
                                                <div v-if="details.waiting_approval_payments?.length">
                                                    <div class="px-5 py-4 bg-gray-50/50 flex items-center space-x-2">
                                                        <span class="text-[13px] font-bold text-[#838799] uppercase tracking-wider">Cần xác nhận</span>
                                                        <span class="text-[#838799]">•</span>
                                                        <span class="text-[13px] font-bold text-[#838799]">{{ details.waiting_approval_payments.length }}</span>
                                                    </div>
                                                    <div v-for="member in details.waiting_approval_payments" :key="'pending-' + member.user.id" 
                                                        class="flex items-center justify-between p-5 hover:bg-gray-50 group transition-colors">
                                                        <div class="flex items-center space-x-4">
                                                            <div class="relative">
                                                                <img :src="member.user?.avatar_url || 'https://ui-avatars.com/api/?name=' + member.user?.full_name" class="w-14 h-14 rounded-full border-2 border-gray-50 group-hover:border-white transition-all shadow-sm" />
                                                            </div>
                                                            <div>
                                                                <p class="font-bold text-[16px] text-[#1F2937]">{{ member.user?.full_name }}</p>
                                                                <p class="text-xs text-[#838799] mt-0.5 font-normal">Đã ck, check ảnh!</p>
                                                            </div>
                                                        </div>
                                                        <button 
                                                            @click="onApprove(member)"
                                                            class="bg-[#10B981] text-white px-5 py-1.5 rounded-full text-sm font-bold hover:bg-[#059669] transition-all active:scale-95">
                                                            Duyệt
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Unpaid Section -->
                                                <div v-if="details.no_payment_yet?.length">
                                                    <div class="px-5 py-4 bg-gray-50/50 flex items-center space-x-2 border-t border-gray-50">
                                                        <span class="text-[13px] font-bold text-[#838799] uppercase tracking-wider">Chưa thanh toán</span>
                                                        <span class="text-[#838799]">•</span>
                                                        <span class="text-[13px] font-bold text-[#838799]">{{ details.no_payment_yet.length }}</span>
                                                    </div>
                                                    <div v-for="member in details.no_payment_yet" :key="'unpaid-' + member.user.id" 
                                                        class="flex items-center justify-between p-5 hover:bg-gray-50 group transition-colors">
                                                        <div class="flex items-center space-x-4">
                                                            <div class="relative">
                                                                <img :src="member.user?.avatar_url || 'https://ui-avatars.com/api/?name=' + member.user?.full_name" class="w-14 h-14 rounded-full border-2 border-gray-50 group-hover:border-white transition-all shadow-sm" />
                                                            </div>
                                                            <div>
                                                                <p class="font-bold text-[16px] text-[#1F2937]">{{ member.user?.full_name }}</p>
                                                                <p v-if="isOverdue(details.collection.end_date)" class="text-xs text-red-500 font-bold mt-0.5 flex items-center">
                                                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                                                                    Trễ {{ overdueDays(details.collection.end_date) }} ngày
                                                                </p>
                                                                <p v-else class="text-xs text-[#838799] mt-0.5 font-normal">Chưa thanh toán</p>
                                                            </div>
                                                        </div>
                                                        <button class="bg-[#F6E4C8] text-[#E0A243] px-5 py-1.5 rounded-full text-sm font-bold hover:bg-[#D48D3B] hover:text-white transition-all active:scale-95">
                                                            Nhắc
                                                        </button>
                                                    </div>
                                                </div>

                                                <div v-if="!details.waiting_approval_payments?.length && !details.no_payment_yet?.length" class="text-center py-16">
                                                    <CheckCircleIcon class="w-12 h-12 text-[#10B981]/20 mx-auto mb-3" />
                                                    <p class="text-gray-400 text-sm font-medium">Tất cả đã hoàn thành</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div v-else-if="!isLoading && !details" class="flex flex-col items-center justify-center h-full text-gray-400">
                                <DocumentMagnifyingGlassIcon class="w-16 h-16 mb-4 opacity-20" />
                                <p>Vui lòng chọn một đợt thu để xem chi tiết</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup>
import { 
    XMarkIcon, 
    ChevronRightIcon, 
    CheckCircleIcon,
    DocumentMagnifyingGlassIcon
} from '@heroicons/vue/24/outline'
import { ref, watch, onMounted } from 'vue'
import * as ClubService from '@/service/club.js'
import { formatCurrency } from '@/composables/formatCurrency'
import dayjs from 'dayjs'
import { formatDatetime } from '@/composables/formatDatetime'
import { toast } from 'vue3-toastify'

const props = defineProps({
    isOpen: Boolean,
    clubId: [String, Number],
    fundCollections: {
        type: Array,
        default: () => []
    },
    initialCollectionId: [String, Number]
})

const emit = defineEmits(['update:isOpen'])

const selectedCollectionId = ref(null)
const details = ref(null)
const isLoading = ref(false)
const activeTab = ref('paid') // paid | unpaid

const closeModal = () => {
    emit('update:isOpen', false)
}

const selectCollection = async (id) => {
    selectedCollectionId.value = id
    await fetchDetails()
}

const fetchDetails = async () => {
    if (!selectedCollectionId.value || !props.clubId) return
    
    try {
        isLoading.value = true
        activeTab.value = 'paid'
        const response = await ClubService.getFundCollectionDetail(props.clubId, selectedCollectionId.value)
        details.value = response.data
    } catch (error) {
        toast.error('Không thể tải thông tin chi tiết đợt thu')
    } finally {
        isLoading.value = false
    }
}

const onApprove = async (member) => {
    if (!member.contribution?.id) return
    
    try {
        await ClubService.confirmFundContribution(props.clubId, selectedCollectionId.value, member.contribution.id)
        toast.success('Đã xác nhận thanh toán')
        await fetchDetails()
    } catch (error) {
        toast.error('Không thể xác nhận thanh toán')
    }
}

const formatShortCurrency = (amount) => {
    if (!amount) return '0đ'
    if (amount >= 1000000) return (amount / 1000000).toFixed(1) + 'tr'
    if (amount >= 1000) return (amount / 1000).toFixed(0) + 'k'
    return amount + 'đ'
}

const formatDate = (date) => {
    if (!date) return '--/--'
    return dayjs(date).format('DD/MM')
}

const isOverdue = (endDate) => {
    if (!endDate) return false
    return dayjs().isAfter(dayjs(endDate))
}

const overdueDays = (endDate) => {
    if (!endDate) return 0
    return dayjs().diff(dayjs(endDate), 'day')
}

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.initialCollectionId) {
            selectedCollectionId.value = props.initialCollectionId
        } else if (props.fundCollections.length > 0) {
            selectedCollectionId.value = props.fundCollections[0].id
        }
        fetchDetails()
    }
})

watch(() => props.initialCollectionId, (newId) => {
    if (newId) {
        selectedCollectionId.value = newId
        if (props.isOpen) fetchDetails()
    }
})
</script>

<style scoped>
.scale-enter-active,
.scale-leave-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.scale-enter-from,
.scale-leave-to {
    opacity: 0;
    transform: scale(0.9) translateY(20px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #E5E7EB;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #D1D5DB;
}
</style>
