<template>
    <div class="m-4 max-w-8xl h-[calc(100vh-7rem)] rounded-md flex flex-col">
        <div class="bg-club-default text-white rounded-[8px] shadow-lg p-6 relative overflow-hidden flex flex-col justify-between"
            :style="{ backgroundImage: `url(${Background})` }">
            <div class="flex items-center justify-between">
                <div>
                    <ArrowLeftIcon class="w-6 h-6 cursor-pointer text-white" @click="goBack" />
                </div>
                <div class="flex items-center space-x-1 relative">
                    <ShareIcon class="w-6 h-6 cursor-pointer text-white" @click="shareClub" />
                    <EllipsisVerticalIcon class="w-9 h-9 cursor-pointer text-white" @click="toggleMenu" />

                    <!-- Dropdown Menu -->
                    <div v-if="isMenuOpen"
                        class="absolute right-0 top-10 w-56 bg-white rounded-xl shadow-2xl py-2 z-[10000] text-gray-800 border border-gray-100 animate-in fade-in zoom-in duration-200">
                        <button class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors"
                            @click="openNotification" v-if="is_joined">
                            <BellIcon class="w-5 h-5 text-gray-500" />
                            <span class="font-medium">Thông báo</span>
                        </button>
                        <button
                            class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors">
                            <InformationCircleIcon class="w-5 h-5 text-gray-500" />
                            <span class="font-medium">Báo cáo CLB</span>
                        </button>
                        <div class="h-px bg-gray-100 my-1 mx-2"></div>
                        <button class="w-full flex items-center space-x-3 px-4 py-3 transition-colors rounded-lg" 
                            @click="leaveClub"
                            :class="is_joined
                                ? 'hover:bg-red-50 text-red-600 cursor-pointer'
                                : 'text-gray-400 bg-gray-100 cursor-not-allowed'
                                " :disabled="!is_joined">
                            <ArrowLeftOnRectangleIcon class="w-5 h-5"
                                :class="is_joined ? 'text-red-600' : 'text-gray-400'" />
                            <span class="font-medium">
                                Rời câu lạc bộ
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Backdrop -->
            <div v-if="isMenuOpen" class="fixed inset-0 z-[9999] bg-black/50 backdrop-blur-sm" @click="closeMenu"></div>
            <div class="flex items-center">
                <div>
                    <div v-if="club.profile?.address"
                        class="flex items-center space-x-2 relative rounded-full overflow-hidden bg-white w-fit text-black py-1 px-2">
                        <MapPinIcon class="w-5 h-5" />
                        <div class="text-sm font-semibold">{{ club.profile.address }}</div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="text-[44px] font-bold">{{ club.name }}</div>
                        <div v-if="club.is_verified" class="bg-[#4392E0] rounded-full p-1">
                            <VerifyIcon class="w-6 h-6 text-white" />
                        </div>
                    </div>
                    <div class="flex items-center space-x-2" v-if="!is_joined">
                        <template v-if="!club.has_pending_request">
                            <Button size="md" color="danger"
                                class="px-[75px] bg-[#D72D36] border border-[#D72D36] text-white hover:bg-white hover:text-[#D72D36] flex gap-2"
                                @click.stop="joinClubRequest">
                                <PlusIcon class="w-5 h-5" />
                                Tham gia CLB
                            </Button>
                        </template>
                        <template v-else>
                            <Button size="md" color="danger"
                                class="px-[75px] bg-[#D72D36] border border-[#D72D36] text-white hover:bg-white hover:text-[#D72D36] flex gap-2"
                                @click.stop="cancelJoinRequest">
                                Hủy tham gia
                            </Button>
                        </template>
                        <Button size="md" color="white" class="bg-[#FBEAEB] rounded-full p-2">
                            <MessageIcon class="w-6.5 h-6.5 text-[#D72D36]" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-12 gap-4 py-4">
            <div class="col-span-8">
                <div v-if="is_joined">
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-2xl text-[#838799] font-semibold uppercase mb-4">Thông báo</h2>
                        <p class="text-[#D72D36] font-semibold cursor-pointer">Xem tất cả</p>
                    </div>
                    <template v-if="notifications.length > 0">
                        <NotificationCard v-for="(notification, index) in notifications.slice(0, 3)" :key="index" :data="notification" />
                    </template>
                    <div v-else class="p-4 text-center">
                        <p class="text-[#838799]">Hiện chưa có thông báo</p>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-2xl text-[#838799] font-semibold uppercase mb-4">Lịch hoạt động</h2>
                        <p class="text-[#D72D36] font-semibold cursor-pointer" @click="openActivityModal">Xem tất cả</p>
                    </div>
                    <template v-if="activities.length > 0">
                        <ActivityScheduleCard v-for="(activity, index) in activities" :key="index" v-bind="activity" />
                    </template>
                    <div v-else class="p-4 text-center">
                        <p class="text-[#838799]">Hiện chưa có lịch thi đấu</p>
                    </div>
                </div>
                <ClubInfoTabs :club="club" :isJoined="is_joined" />
            </div>
            <div class="col-span-4 space-y-4">
                <div class="max-w-3xl mx-auto">
                    <div class="bg-white rounded-2xl shadow-md px-6 py-5">
                        <div class="grid grid-cols-3 divide-x divide-gray-200 text-center">
                            <div v-for="(stat, index) in clubStats" :key="index"
                                class="flex flex-col items-center gap-2">
                                <div class="text-[#D72D36] h-12 flex items-center justify-center">
                                    <component :is="stat.icon" class="w-12 h-12" />
                                </div>
                                <div class="font-semibold text-gray-800">{{ statsValue[stat.key] }}</div>
                                <div class="text-sm text-[#838799]">{{ stat.label }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="max-w-3xl mx-auto" v-if="is_joined">
                    <div class="bg-white rounded-2xl shadow-md px-2 py-5">
                        <div class="grid grid-cols-4 text-center">
                            <div v-for="(module, index) in clubModules" :key="index"
                                class="flex flex-col items-center gap-2">
                                <div class="text-[#D72D36] rounded-md bg-[#FBEAEB] p-4 cursor-pointer">
                                    <component :is="module.icon" class="w-6 h-6" />
                                </div>
                                <div class="text-sm text-[#3E414C]">{{ module.label }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Modal -->
        <div v-if="isNotificationModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <!-- Backdrop with blur -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeNotification"></div>

            <div
                class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-[10000] overflow-hidden animate-in fade-in zoom-in duration-300 h-[calc(100vh-7rem)] flex flex-col">
                <!-- Fixed Header -->
                <div class="p-6 pb-2">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-[28px] font-bold text-[#3E414C]">Thông báo</h3>
                        <button @click="closeNotification" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <XMarkIcon class="w-8 h-8" stroke-width="2.5" />
                        </button>
                    </div>
                </div>
                <div v-if="notifications.length === 0" class="flex items-start justify-center mt-4">
                    <p class="text-[#838799]">Hiện chưa có thông báo</p>
                </div>

                <!-- Scrollable Content -->
                <div class="p-6 pt-2 flex-1 overflow-y-auto custom-scrollbar" v-else>
                    <!-- Category: TODAY -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-bold text-[#838799] uppercase tracking-wider">Hôm nay</span>
                            <button class="text-[#D72D36] text-sm font-semibold hover:opacity-80">Đánh dấu đã
                                đọc</button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(notification, index) in notifications.filter(n => n.category === 'today')"
                                :key="index"
                                :class="['flex gap-4 p-4 rounded-2xl transition-colors', !notification.isRead ? 'bg-[#F8F9FB]' : 'bg-transparent']">
                                <div class="relative flex-shrink-0">
                                    <div
                                        :class="['w-14 h-14 rounded-xl flex items-center justify-center', notification.colorClass]">
                                        <component :is="notification.icon" class="w-7 h-7" />
                                    </div>
                                    <div v-if="!notification.isRead"
                                        class="absolute -right-1 bottom-0 w-4 h-4 bg-[#D72D36] border-2 border-white rounded-full">
                                    </div>
                                    <div v-if="notification.type === 'payment' && !notification.isRead"
                                        class="absolute -right-1 bottom-0 w-4 h-4 bg-[#10B981] border-2 border-white rounded-full">
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <h4 class="font-semibold text-base text-[#3E414C] truncate">{{
                                            notification.title }}
                                        </h4>
                                        <span class="text-[#838799] text-xs whitespace-nowrap pt-1">{{
                                            notification.timeAgo
                                        }}</span>
                                    </div>
                                    <p class="text-xs text-[#838799] leading-4 line-clamp-2">{{ notification.content }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category: PREVIOUS -->
                    <div>
                        <div class="mb-4">
                            <span class="text-sm font-bold text-[#838799] uppercase tracking-wider">Trước đó</span>
                        </div>
                        <div class="space-y-6">
                            <div v-for="(notification, index) in notifications.filter(n => n.category === 'previous')"
                                :key="index" class="flex gap-4 px-4 transition-colors">
                                <div class="relative flex-shrink-0">
                                    <div
                                        :class="['w-14 h-14 rounded-xl flex items-center justify-center', notification.colorClass]">
                                        <component :is="notification.icon" class="w-7 h-7" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <h4 class="font-bold text-base text-[#3E414C] truncate">{{ notification.title }}
                                        </h4>
                                        <span class="text-[#838799] text-xs whitespace-nowrap pt-1">{{
                                            notification.timeAgo
                                        }}</span>
                                    </div>
                                    <p class="text-sm text-[#838799] leading-6 line-clamp-2">{{ notification.content }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Schedule Modal -->
        <ClubActivityModal :is-open="isActivityModalOpen" :thumbnail="Thumbnail"
            :upcoming-activities="upcomingActivities" :history-activities="historyActivities"
            @close="closeActivityModal" />
    </div>
</template>

<script setup>
import Background from '@/assets/images/club-default-thumbnail.svg?url'
import Thumbnail from "@/assets/images/dashboard-bg.svg?url";
import {
    ArrowLeftIcon,
    ShareIcon,
    EllipsisVerticalIcon,
    MapPinIcon,
    PlusIcon,
    BellIcon,
    ArrowLeftOnRectangleIcon,
    InformationCircleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'
import { useRouter, useRoute } from 'vue-router'
import { computed, onMounted, ref } from 'vue'
import VerifyIcon from "@/assets/images/verify-icon.svg";
import Button from '@/components/atoms/Button.vue';
import MessageIcon from "@/assets/images/message.svg";
import ActivityScheduleCard from '@/components/molecules/ActivityScheduleCard.vue';
import NotificationCard from '@/components/molecules/NotificationCard.vue';
import ClubInfoTabs from '@/components/organisms/ClubInfoTabs.vue';
import { CLUB_STATS, CLUB_MODULES } from '@/data/club/index.js';
import * as ClubService from '@/service/club.js'
import { toast } from 'vue3-toastify';
import { useUserStore } from '@/store/auth'
import { storeToRefs } from 'pinia'

import ClubActivityModal from './ClubActivityModal.vue';

const router = useRouter()
const route = useRoute()
const clubStats = CLUB_STATS;
const clubModules = CLUB_MODULES;
const isMenuOpen = ref(false)
const isNotificationModalOpen = ref(false)
const isActivityModalOpen = ref(false)
const club = ref([]);
const clubId = route.params.id
const userStore = useUserStore()
const { getUser } = storeToRefs(userStore)
const notifications = ref([])
const currentNotificationPage = ref(1)
const notificationPerPage = ref(15)

const statsValue = computed(() => ({
    members: club.value?.quantity_members ?? 0,
    level: club.value?.skill_level?.min + ' - ' + club.value?.skill_level?.max ?? '1-5',
    price: club.value?.guest_fee ?? '50K'
}));

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value
}

const closeMenu = () => {
    isMenuOpen.value = false
}

const shareClub = () => {
    console.log('share club')
}

const openNotification = () => {
    isNotificationModalOpen.value = true
    isMenuOpen.value = false
}

const closeNotification = () => {
    isNotificationModalOpen.value = false
}

const openActivityModal = () => {
    isActivityModalOpen.value = true
}

const closeActivityModal = () => {
    isActivityModalOpen.value = false
}

const activities = [
    {
        day: 'T3',
        date: '24',
        title: 'Kèo cố định 3-5-7 (Sắp diễn ra)',
        time: '18:00 - 20:00',
        participants: '10/12',
        status: 'open',
        buttonText: 'Đăng ký',
        type: 'danger',
        disabled: false
    },
    {
        day: 'T3',
        date: '24',
        title: 'Kèo cố định 3-5-7 (Đang diễn ra)',
        time: '18:00 - 20:00',
        participants: '10/12',
        status: 'private',
        buttonText: 'Check-in',
        type: 'primary',
        disabled: false
    },
    {
        day: 'T5',
        date: '26',
        title: 'Kèo cố định 3-5-7 (Đã xong)',
        time: '18:00 - 20:00',
        location: 'Sân VSpace',
        participants: '0/12',
        status: 'private',
        buttonText: 'Check-in',
        type: 'secondary',
        disabled: true
    }
]

const upcomingActivities = [
    {
        day: 'T3',
        date: '24',
        title: 'Kèo cố định 3-5-7',
        time: '18:00 - 20:00',
        participants: '10/12 thành viên',
        status: 'open',
        statusText: 'Mở đăng kí',
        buttonText: 'Đăng ký',
        type: 'danger',
        disabled: false
    },
    {
        day: 'T3',
        date: '24',
        title: 'Kèo cố định 3-5-7',
        time: '18:00 - 20:00',
        participants: '10/12 thành viên',
        status: 'private',
        statusText: 'Mở check-in',
        buttonText: 'Check-in',
        type: 'primary',
        disabled: false
    }
]

const historyActivities = [
    {
        day: 'CN',
        date: '21',
        title: 'Kèo cuối tuần',
        time: '18:00 - 20:00',
        result: 'Kết quả: Thắng (21-08, 21-15)',
        status: 'Hoàn tất'
    }
]

// const notifications = [
//     {
//         title: 'Lịch đấu mới: Kèo cố định 3-5-7',
//         content: 'Sân Pickleball Quận 7 đã mở đăng ký cho buổi tối nay. Đăng ký ngay để giữ chỗ!',
//         timeAgo: 'Vừa xong',
//         type: 'match',
//         category: 'today',
//         isRead: false,
//         icon: CalendarIcon,
//         colorClass: 'bg-[#FEE2E2] text-[#EF4444]'
//     },
//     {
//         title: 'Xác nhận thanh toán',
//         content: 'Khoản đóng quỹ 50.000 của bạn cho buổi tập ngày 24/10 đã được ghi nhận.',
//         timeAgo: '2 giờ trước',
//         type: 'payment',
//         category: 'today',
//         isRead: false,
//         icon: WalletIcon,
//         colorClass: 'bg-[#DCFCE7] text-[#10B981]'
//     },
//     {
//         title: 'Cập nhật ứng dụng',
//         content: 'Chúng tôi vừa cập nhật tính năng "Check-in QR" để bạn vào sân nhanh chóng hơn.',
//         timeAgo: '6 giờ trước',
//         type: 'app',
//         category: 'today',
//         isRead: true,
//         icon: ArrowPathIcon,
//         colorClass: 'bg-gray-100 text-gray-500'
//     },
//     {
//         title: 'Thành viên mới',
//         content: 'Chào mừng Hoàng Anh đã gia nhập CLB Pickleball Sài Gòn Phố',
//         timeAgo: 'hôm qua',
//         type: 'member',
//         category: 'previous',
//         isRead: true,
//         icon: UserPlusIcon,
//         colorClass: 'bg-gray-100 text-gray-400'
//     },
//     {
//         title: 'Buổi tập bị hủy',
//         content: 'Rất tiếc, buổi tập T5 26/10 bị hủy do thời tiết không thuận lợi.',
//         timeAgo: '2 ngày trước',
//         type: 'cancel',
//         category: 'previous',
//         isRead: true,
//         icon: CalendarDaysIcon,
//         colorClass: 'bg-gray-100 text-gray-400'
//     },
//     {
//         title: 'Thăng hạng trình độ',
//         content: 'Chúc mừng! Bạn đã được Admin nâng mức trình độ lên 3.0 dựa trên kết quả thi đấu.',
//         timeAgo: '3 ngày trước',
//         type: 'rank',
//         category: 'previous',
//         isRead: true,
//         icon: AcademicCapIcon,
//         colorClass: 'bg-[#FEF3C7] text-[#D97706]'
//     }
// ]

const getClubDetail = async () => {
    try {
        const response = await ClubService.clubDetail(clubId)
        club.value = response
    } catch (error) {
        toast.error(error.response.data.message || 'Có lỗi xảy ra khi lấy thông tin câu lạc bộ')
    }
}

const joinClubRequest = async () => {
    try {
        await ClubService.joinRequest(clubId)
        await getClubDetail(clubId)
        toast.success('Yêu cầu tham gia đã được gửi thành công')
    } catch (error) {
        toast.error(error.response.data.message || 'Có lỗi xảy ra khi gửi yêu cầu tham gia')
    }
}

const cancelJoinRequest = async () => {
    try {
        await ClubService.cancelJoinRequest(clubId)
        await getClubDetail(clubId)
        toast.success('Đã huỷ yêu cầu tham gia')
    } catch (error) {
        toast.error(error.response.data.message || 'Có lỗi xảy ra khi huỷ yêu cầu tham gia')
    }
}

const leaveClub = async () => {
    try {
        await ClubService.leaveClub(clubId)
        await getClubDetail(clubId)
        isMenuOpen.value = false
        toast.success('Đã rời câu lạc bộ')
    } catch (error) {
        toast.error(error.response.data.message || 'Có lỗi xảy ra khi rời câu lạc bộ')
    }
}

const getClubNotification = async () => {
    try {
        const response = await ClubService.clubNotification(clubId, {
            page: currentNotificationPage.value,
            per_page:notificationPerPage.value,
            status:'sent',
            is_pinned:1
        })
        notifications.value = response.data.notifications
    } catch (error) {
        toast.error(error.response.data.message || 'Có lỗi xảy ra khi lấy thông tin thông báo')
    }
}

const is_joined = computed(() => {
    return club.value?.members?.some(member => member.user_id === getUser.value.id && member.status == 'active') ?? false
})

const goBack = () => {
    router.back()
}

onMounted(async () => {
    if (!clubId) return;
    Promise.all([
        getClubDetail(),
        getClubNotification()
    ])
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
</style>