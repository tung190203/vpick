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
                    <div v-if="isMenuOpen" class="absolute right-0 top-10 w-56 bg-white rounded-xl shadow-2xl py-2 z-[10000] text-gray-800 border border-gray-100 animate-in fade-in zoom-in duration-200">
                        <button class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors">
                            <BellIcon class="w-5 h-5 text-gray-500" />
                            <span class="font-medium">Thông báo</span>
                        </button>
                        <button class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors">
                            <InformationCircleIcon class="w-5 h-5 text-gray-500" />
                            <span class="font-medium">Báo cáo CLB</span>
                        </button>
                        <div class="h-px bg-gray-100 my-1 mx-2"></div>
                        <button class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-red-50 text-red-600 transition-colors">
                            <ArrowLeftOnRectangleIcon class="w-5 h-5" />
                            <span class="font-medium">Rời câu lạc bộ</span>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Backdrop -->
            <div v-if="isMenuOpen" class="fixed inset-0 z-[9999] bg-black/50 backdrop-blur-sm" @click="closeMenu"></div>
            <div class="flex items-center">
                <div>
                    <div
                        class="flex items-center space-x-2 relative rounded-full overflow-hidden bg-white w-fit text-black py-1 px-2">
                        <MapPinIcon class="w-5 h-5" />
                        <div class="text-sm font-semibold">Pickleball sài gòn phố</div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="text-[44px] font-bold">Pickleball sài gòn phố</div>
                        <div class="bg-[#4392E0] rounded-full p-1">
                            <img :src="VerifyIcon" alt="Verify Icon" class="w-6 h-6 text-white" />
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button size="md" color="danger"
                            class="px-[75px] bg-[#D72D36] border border-[#D72D36] text-white hover:bg-white hover:text-[#D72D36] flex gap-2">
                            <PlusIcon class="w-5 h-5" />
                            Tham gia CLB
                        </Button>
                        <Button size="md" color="white" class="bg-[#FBEAEB] rounded-full p-2">
                            <img :src="MessageIcon" alt="Message Icon" class="w-6.5 h-6.5" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-12 gap-4 py-4">
            <div class="col-span-8">
                <div>
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-2xl text-[#838799] font-semibold uppercase mb-4">Thông báo</h2>
                        <p class="text-[#D72D36] font-semibold cursor-pointer">Xem tất cả</p>
                    </div>
                    <NotificationCard v-for="(notification, index) in notifications" :key="index" v-bind="notification" />
                </div>
                <div>
                    <h2 class="text-2xl text-[#838799] font-semibold uppercase mb-4">Lịch hoạt động</h2>
                    <ActivityScheduleCard v-for="(activity, index) in activities" :key="index" v-bind="activity" />
                </div>
                <ClubInfoTabs />
            </div>
            <div class="col-span-4 space-y-4">
                <div class="max-w-3xl mx-auto">
                    <div class="bg-white rounded-2xl shadow-md px-6 py-5">
                        <div class="grid grid-cols-3 divide-x divide-gray-200 text-center">
                            <div v-for="(stat, index) in clubStats" :key="index" class="flex flex-col items-center gap-2">
                                <div class="text-red-500">
                                    <img :src="stat.icon" :alt="stat.alt" class="w-12 h-12">
                                </div>
                                <div class="font-semibold text-gray-800">{{ stat.value }}</div>
                                <div class="text-sm text-[#838799]">{{ stat.label }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="max-w-3xl mx-auto">
                    <div class="bg-white rounded-2xl shadow-md px-2 py-5">
                        <div class="grid grid-cols-4 text-center">
                            <div v-for="(module, index) in clubModules" :key="index" class="flex flex-col items-center gap-2">
                                <div class="text-red-500 rounded-md bg-[#FBEAEB] p-4 cursor-pointer">
                                    <img :src="module.icon" :alt="module.alt" class="w-6 h-6">
                                </div>
                                <div class="text-sm text-[#3E414C]">{{ module.label }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import Background from '@/assets/images/club-default-thumbnail.svg'
import { ArrowLeftIcon, ShareIcon, EllipsisVerticalIcon, MapPinIcon, PlusIcon, BellIcon, FlagIcon, ArrowLeftOnRectangleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import { useRouter } from 'vue-router'
import { ref } from 'vue'
import VerifyIcon from "@/assets/images/verify-icon.svg";
import Button from '@/components/atoms/Button.vue';
import MessageIcon from "@/assets/images/message.svg";
import ActivityScheduleCard from '@/components/molecules/ActivityScheduleCard.vue';
import NotificationCard from '@/components/molecules/NotificationCard.vue';
import ClubInfoTabs from '@/components/organisms/ClubInfoTabs.vue';
import { CLUB_STATS, CLUB_MODULES } from '@/data/club/index.js';

const router = useRouter()
const clubStats = CLUB_STATS;
const clubModules = CLUB_MODULES;

const isMenuOpen = ref(false)

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value
}

const closeMenu = () => {
    isMenuOpen.value = false
}

const shareClub = () => {
    console.log('share club')
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

const notifications = [
    {
        title: 'Thông báo nghỉ sân ngày 26/10',
        content: 'Do điều kiện thời tiết mưa lớn, sân bị ngập. BQL quyết định hủy bỏ kèo đấu tối ngày hôm nay, tiền sân sẽ được lưu ký tự động vào quỹ để thanh toán bù cho buổi sau hoặc hoàn trả theo yêu cầu của thành viên.',
        author: 'Đăng bởi Admin',
        timeAgo: '2h trước'
    }
]

const goBack = () => {
    router.back()
}
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