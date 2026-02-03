<template>
    <div class="m-4 max-w-8xl h-[calc(100vh-7rem)] rounded-md flex flex-col">
        <!-- Loading Skeleton -->
        <div v-if="isInitialLoading" class="animate-pulse">
            <!-- Header Skeleton -->
            <div class="bg-gray-200 rounded-[8px] px-6 pt-4 pb-6 min-h-[290px] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="bg-gray-300 p-5 rounded-full w-10 h-10"></div>
                        <div class="flex flex-col space-y-2">
                            <div class="h-8 w-48 bg-gray-300 rounded"></div>
                            <div class="h-4 w-24 bg-gray-300 rounded"></div>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                        <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                    </div>
                </div>
                <!-- Stats Skeleton -->
                <div class="grid grid-cols-3 gap-4 mt-8">
                    <div v-for="i in 3" :key="i" class="bg-gray-300/50 rounded-2xl p-6 h-28"></div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 py-4">
                <div class="col-span-8 space-y-6">
                    <div class="h-8 w-40 bg-gray-200 rounded"></div>
                    <div v-for="i in 2" :key="i" class="h-24 bg-gray-100 rounded-xl"></div>
                    <div class="h-8 w-40 bg-gray-200 rounded mt-8"></div>
                    <div v-for="i in 3" :key="i" class="h-20 bg-gray-100 rounded-xl"></div>
                </div>
                <div class="col-span-4 space-y-4">
                    <div class="bg-gray-100 rounded-2xl h-32 w-full"></div>
                    <div class="bg-gray-100 rounded-2xl h-32 w-full"></div>
                </div>
            </div>
        </div>

        <template v-else>
            <!-- Admin/Staff View -->
            <div v-if="isAdminView"
                class=" text-white rounded-[8px] shadow-lg px-6 pt-4 pb-6 relative overflow-hidden flex flex-col justify-between min-h-[290px]"
                :style="{ backgroundImage: `url(${Background})` }">
                <div class="flex items-center justify-between relative z-20">
                    <div class="flex items-center space-x-4">
                        <div class="bg-white/10 p-2 rounded-full cursor-pointer hover:bg-white/20 transition-colors"
                            @click="goBack">
                            <ArrowLeftIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center space-x-2">
                                <h1 class="text-4xl font-bold leading-tight">{{ club.name }}</h1>
                                <div v-if="club.is_verified" class="bg-[#4392E0] rounded-full p-1">
                                    <VerifyIcon class="w-5 h-5 text-white" />
                                </div>
                            </div>
                            <p class="text-gray-400 text-sm font-medium">Quản trị viên</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="p-2 cursor-pointer hover:bg-white/10 rounded-full transition-colors">
                            <ChangeCircleIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="p-2 cursor-pointer hover:bg-white/10 rounded-full transition-colors"
                            @click="toggleMenu">
                            <EllipsisVerticalIcon class="w-6 h-6 text-white" />
                        </div>

                        <!-- Dropdown Menu (Same as member) -->
                        <div v-if="isMenuOpen"
                            class="absolute right-0 top-14 w-56 bg-white rounded-xl shadow-2xl py-2 z-50 text-gray-800 border border-gray-100 animate-in fade-in zoom-in duration-200">
                            <!-- Add admin specific menu items if needed, for now reuse existing -->
                            <button
                                class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors"
                                @click="openEditModal">
                                <EditNoteIcon class="w-5 h-5 text-gray-500" />
                                <span class="font-medium">Chỉnh sửa</span>
                            </button>
                            <button
                                class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors"
                                @click="openZaloModal">
                                <ZaloIcon class="w-5 h-5 text-gray-500" />
                                <span class="font-medium">Thêm nhóm Zalo</span>
                            </button>
                            <button
                                class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors"
                                @click="openNotification">
                                <CompareArrowsIcon class="w-5 h-5 text-gray-500" />
                                <span class="font-medium">Nhượng CLB</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-3 gap-4 mt-8 relative z-10">
                    <div v-for="(stat, index) in statsAdmin" :key="index"
                        class="bg-[#3E414C]/80 backdrop-blur-md rounded-2xl p-6 border border-white/5 shadow-inner">
                        <p class="text-sm font-semibold text-gray-300 mb-2 uppercase tracking-wide">{{ stat.label }}</p>
                        <div class="flex items-baseline space-x-1">
                            <span class="text-4xl font-bold">{{ stat.value }}</span>
                            <span class="text-sm font-medium opacity-60 ml-2" :class="stat.unitClass">{{ stat.unit
                                }}</span>
                        </div>
                    </div>
                </div>

                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-10 pointer-events-none">
                    <div class="absolute inset-0 bg-repeat bg-center"
                        style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;20&quot; height=&quot;20&quot; viewBox=&quot;0 0 20 20&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cpath d=&quot;M0 0h10v10H0zM10 10h10v10H10z&quot; fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.1&quot;/%3E%3C/svg%3E')">
                    </div>
                </div>
            </div>

            <!-- Member/Guest View (Existing) -->
            <div v-else
                class="bg-club-default text-white rounded-[8px] shadow-lg p-6 relative overflow-hidden flex flex-col justify-between"
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
                            <button
                                class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors"
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
                                @click="leaveClub" :class="is_joined
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
                <div v-if="isMenuOpen" class="fixed inset-0 z-[9999] bg-black/50 backdrop-blur-sm" @click="closeMenu">
                </div>
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
                            <NotificationCard v-for="(notification, index) in notifications.slice(0, 3)" :key="index"
                                :data="notification" />
                        </template>
                        <div v-else class="p-4 text-center">
                            <p class="text-[#838799]">Hiện chưa có thông báo</p>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline justify-between">
                            <h2 class="text-2xl text-[#838799] font-semibold uppercase mb-4">Lịch hoạt động</h2>
                            <p class="text-[#D72D36] font-semibold cursor-pointer" @click="openActivityModal">Xem tất cả
                            </p>
                        </div>
                        <template v-if="activities.length > 0">
                            <ActivityScheduleCard v-for="(activity, index) in activities" :key="index" v-bind="activity"
                                @click-card="goToActivityDetail(activity)" />
                        </template>
                        <div v-else class="p-4 text-center">
                            <p class="text-[#838799]">Hiện chưa có lịch thi đấu</p>
                        </div>
                    </div>
                    <ClubInfoTabs :club="club" :isJoined="is_joined" :top-three="topThree" :leaderboard="leaderboard"
                        :leaderboard-meta="leaderboardMeta" :leaderboard-filters="leaderboardFilters"
                        :leaderboard-loading="isLeaderboardLoading" @leaderboard-filter="handleLeaderboardFilter"
                        @leaderboard-page-change="handleLeaderboardPageChange" />
                </div>
                <div class="col-span-4 space-y-4">
                    <div class="max-w-3xl mx-auto" v-if="!isAdminView">
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
                    <div class="max-w-3xl mx-auto" v-if="isAdminView">
                        <div class="bg-white rounded-2xl shadow-md p-6">
                            <div class="flex items-center gap-2 mb-6">
                                <p class="uppercase font-bold text-[#838799] text-sm">Yêu cầu tham gia</p>
                                <span class="w-1 h-1 rounded-full bg-[#3E414C]"></span>
                                <span class="font-bold text-[#D72D36] text-sm">({{ joiningRequests.length }})</span>
                            </div>

                            <div v-if="joiningRequests.length > 0" class="space-y-6">
                                <template v-for="(request, index) in joiningRequests" :key="request.id">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <img :src="request.user.avatar_url" alt="Avatar" class="w-12 h-12 rounded-full object-cover border border-gray-100" />
                                            <div>
                                                <h4 class="font-bold text-[#3E414C] text-base">{{ request.user.full_name }}</h4>
                                                <p class="text-xs text-[#838799] mt-1">
                                                    Trình {{ Number(request.user.sports[0].scores.vndupr_score).toFixed(1) }}
                                                    {{ request.message ? `• ${request.message}` : '' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <button 
                                                class="w-10 h-10 rounded-full bg-[#D72D36] flex items-center justify-center hover:bg-[#c4252e] transition-colors"
                                                @click="rejectJoinRequest(request.id)"
                                            >
                                                <XMarkIcon class="w-5 h-5 text-white" stroke-width="2.5" />
                                            </button>
                                            <button 
                                                class="w-10 h-10 rounded-full bg-[#00B377] flex items-center justify-center hover:bg-[#00a16b] transition-colors"
                                                @click="approveJoinRequest(request.id)"
                                            >
                                                <CheckIcon class="w-5 h-5 text-white" stroke-width="2.5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="index < joiningRequests.length - 1" class="h-px bg-gray-100"></div>
                                </template>
                            </div>
                            <div v-else class="p-4 text-center">
                                <p class="text-[#838799]">Hiện chưa có yêu cầu tham gia nào</p>
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
                            <button @click="closeNotification"
                                class="text-gray-400 hover:text-gray-600 transition-colors">
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
                                        <p class="text-xs text-[#838799] leading-4 line-clamp-2">{{ notification.content
                                            }}
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
                                            <h4 class="font-bold text-base text-[#3E414C] truncate">{{
                                                notification.title }}
                                            </h4>
                                            <span class="text-[#838799] text-xs whitespace-nowrap pt-1">{{
                                                notification.timeAgo
                                                }}</span>
                                        </div>
                                        <p class="text-sm text-[#838799] leading-6 line-clamp-2">{{ notification.content
                                            }}
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
                :next-match="nextMatch" :countdown="countdownText" @close="closeActivityModal" />

            <!-- Edit Club Modal -->
            <ClubEditModal 
                v-model="isEditModalOpen" 
                :club="club" 
                :is-loading="isUpdatingClub"
                @save="handleUpdateClub" 
            />

            <!-- Zalo Modal -->
            <ClubZaloModal 
                v-model="isZaloModalOpen" 
                :club="club" 
                @save="handleUpdateZalo" 
            />
        </template>
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
    XMarkIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline'
import { useRouter, useRoute } from 'vue-router'
import { computed, onMounted, ref } from 'vue'
import VerifyIcon from "@/assets/images/verify-icon.svg";
import Button from '@/components/atoms/Button.vue';
import MessageIcon from "@/assets/images/message.svg";
import ChangeCircleIcon from "@/assets/images/change_circle.svg";
import CompareArrowsIcon from "@/assets/images/compare_arrows.svg";
import EditNoteIcon from "@/assets/images/edit_note.svg";
import ZaloIcon from "@/assets/images/zalo.svg";
import ActivityScheduleCard from '@/components/molecules/ActivityScheduleCard.vue';
import NotificationCard from '@/components/molecules/NotificationCard.vue';
import ClubInfoTabs from '@/components/organisms/ClubInfoTabs.vue';
import ClubEditModal from '@/components/organisms/ClubEditModal.vue';
import ClubZaloModal from '@/components/organisms/ClubZaloModal.vue';
import { CLUB_STATS, CLUB_MODULES } from '@/data/club/index.js';
import * as ClubService from '@/service/club.js'
import { toast } from 'vue3-toastify';
import { useUserStore } from '@/store/auth'
import { storeToRefs } from 'pinia'
import ClubActivityModal from '@/components/organisms/ClubActivityModal.vue';

import dayjs from 'dayjs'
import 'dayjs/locale/vi'

dayjs.locale('vi')

const router = useRouter()
const route = useRoute()
const clubStats = CLUB_STATS;
const clubModules = CLUB_MODULES;
const isMenuOpen = ref(false)
const isNotificationModalOpen = ref(false)
const isActivityModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isZaloModalOpen = ref(false)
const isUpdatingClub = ref(false)
const club = ref([]);
const clubId = route.params.id
const userStore = useUserStore()
const { getUser } = storeToRefs(userStore)
const notifications = ref([])
const currentNotificationPage = ref(1)
const notificationPerPage = ref(15)

const activities = ref([])
const upcomingActivities = ref([])
const historyActivities = ref([])
const currentActivityPage = ref(1)
const activityPerPage = ref(20)
const fund = ref([])

const topThree = ref([])
const leaderboard = ref([])
const leaderboardMeta = ref({})
const leaderboardFilters = ref({
    month: dayjs().month() + 1,
    year: dayjs().year(),
    page: 1,
    per_page: 10
})
const isInitialLoading = ref(true)
const isLeaderboardLoading = ref(false)

const joiningRequests = ref([
    {
        id: 1,
        name: 'Trần Văn Hậu',
        level: 'Trình 3.5',
        source: 'Qua giới thiệu',
        avatar: 'https://i.pravatar.cc/150?u=1'
    },
    {
        id: 2,
        name: 'Ngô Thanh Vân',
        level: 'Newbie',
        source: 'Tìm sân tập',
        avatar: 'https://i.pravatar.cc/150?u=2'
    }
])

const countdownText = ref('')
let countdownInterval = null

const currentUserMember = computed(() => {
    return club.value?.members?.find(member => member.user_id === getUser.value.id) || null
})

const isAdminView = computed(() => {
    return currentUserMember.value && currentUserMember.value.role !== 'member'
})

const statsAdmin = computed(() => [
    {
        label: 'Quỹ hiện tại',
        value: fund.value?.balance?.toLocaleString() || 0,
        unit: fund.value?.currency,
        unitClass: 'text-[#00B377]'
    },
    {
        label: 'Thành viên',
        value: club.value?.quantity_members ?? 0,
        unit: 'người'
    },
    {
        label: 'Hoạt động tuần này',
        value: activities.value.filter(a => dayjs(a.start_time).isSame(dayjs(), 'week')).length || 0,
        unit: 'Buổi'
    }
])

const nextMatch = computed(() => {
    const now = dayjs()
    const oneHourLater = now.add(1, 'hour')

    const registered = activities.value.filter(a => {
        const startTime = dayjs(a.start_time)

        return (
            a.status === 'private' &&
            startTime.isAfter(now) &&
            startTime.isBefore(oneHourLater)
        )
    })

    if (registered.length === 0) return null

    return registered.sort(
        (a, b) => dayjs(a.start_time).diff(dayjs(b.start_time))
    )[0]
})

const startCountdown = () => {
    if (countdownInterval) clearInterval(countdownInterval)

    const update = () => {
        if (!nextMatch.value) {
            countdownText.value = ''
            return
        }

        const now = dayjs()
        const start = dayjs(nextMatch.value.start_time)
        const diff = start.diff(now)

        if (diff <= 0) {
            countdownText.value = 'Đang diễn ra'
            return
        }

        const hours = Math.floor(diff / (1000 * 60 * 60))
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
        const seconds = Math.floor((diff % (1000 * 60)) / 1000)

        countdownText.value = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
    }

    update()
    countdownInterval = setInterval(update, 1000)
}

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

const openEditModal = () => {
    isEditModalOpen.value = true
    isMenuOpen.value = false
}

const openZaloModal = () => {
    isZaloModalOpen.value = true
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

const goToActivityDetail = (activity) => {
    router.push({
        name: 'club-detail-activity',
        params: { id: clubId },
        query: { activityId: activity.id }
    })
}

const getVietnameseDay = (date) => {
    const days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
    return days[dayjs(date).day()]
}

const formatActivity = (item) => {
    const isCompleted = item.status === 'completed' || dayjs().isAfter(dayjs(item.end_time))
    const isRegistered = item.participants?.some(p => p.user_id === getUser.value.id)

    // Determine type (border color)
    let type = 'danger' // Default to red (not registered)
    if (isCompleted) {
        type = 'secondary' // Gray
    } else if (isRegistered) {
        type = 'primary' // Blue (registered)
    }

    // Determine status badge (colors) - ActivityScheduleCard & ActivitySmallCard
    let status = item.status === 'scheduled' ? 'open' : (item.status === 'ongoing' ? 'private' : 'Hoàn tất')
    if (isRegistered && !isCompleted) status = 'private'

    // Determine statusText (label) - ActivitySmallCard & updated ActivityScheduleCard
    let statusText = item.status === 'scheduled' ? 'Mở đăng ký' : (item.status === 'ongoing' ? 'Mở check-in' : 'Hoàn tất')
    if (isRegistered && !isCompleted) statusText = 'Đã đăng ký'

    // Determine button text
    let buttonText = 'Đăng ký'
    if (isCompleted) {
        buttonText = 'Đã xong'
    } else if (isRegistered) {
        buttonText = 'Check-in ngay'
    }

    return {
        ...item,
        day: getVietnameseDay(item.start_time),
        date: dayjs(item.start_time).format('DD'),
        time: `${dayjs(item.start_time).format('HH:mm')} - ${dayjs(item.end_time).format('HH:mm')}`,
        participants: `${item.participants_count}/${item.max_participants || '∞'}`,
        status,
        statusText,
        buttonText,
        type,
        disabled: isCompleted,
        location: item.location,
        participants_list: item.participants || [],
        result: isCompleted ? `Địa điểm: ${item.location}` : null
    }
}

const getClubDetail = async () => {
    try {
        const response = await ClubService.clubDetail(clubId)
        club.value = response
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin câu lạc bộ')
    }
}

const handleUpdateClub = async (data) => {
    isUpdatingClub.value = true
    try {
        const formData = new FormData()
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                 if (key === 'cover_file' && data[key]) {
                    formData.append('cover_image_url', data[key])
                 } else if (key === 'logo_file' && data[key]) {
                    formData.append('logo_url', data[key])
                 } else if (!['cover_image_url', 'logo_url', 'cover_file', 'logo_file'].includes(key)) {
                    formData.append(key, data[key])
                 }
            }
        })
        
        await ClubService.updateClub(clubId, formData)
        await getClubDetail()
        isEditModalOpen.value = false
        toast.success('Cập nhật thông tin CLB thành công')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi cập nhật thông tin')
    } finally {
        isUpdatingClub.value = false
    }
}

const handleUpdateZalo = async (data) => {
    try {
         const formData = new FormData()
         if (data.zalo_url) formData.append('zalo_url', data.zalo_url)
         if (!data.enable_zalo_link) formData.append('zalo_url', '')

         if (data.qr_file) {
             formData.append('qr_code', data.qr_file)
         }

         await ClubService.updateClub(clubId, formData)
         await getClubDetail()
         toast.success('Cập nhật thông tin Zalo thành công')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi cập nhật thông tin')
    }
}

const joinClubRequest = async () => {
    try {
        await ClubService.joinRequest(clubId)
        await getClubDetail(clubId)
        toast.success('Yêu cầu tham gia đã được gửi thành công')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi gửi yêu cầu tham gia')
    }
}

const cancelJoinRequest = async () => {
    try {
        await ClubService.cancelJoinRequest(clubId)
        await getClubDetail(clubId)
        toast.success('Đã huỷ yêu cầu tham gia')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi huỷ yêu cầu tham gia')
    }
}

const leaveClub = async () => {
    try {
        await ClubService.leaveClub(clubId)
        await getClubDetail(clubId)
        isMenuOpen.value = false
        toast.success('Đã rời câu lạc bộ')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi rời câu lạc bộ')
    }
}

const getClubNotification = async () => {
    try {
        const response = await ClubService.clubNotification(clubId, {
            page: currentNotificationPage.value,
            per_page: notificationPerPage.value,
            status: 'sent',
            is_pinned: 1
        })
        notifications.value = response.data.notifications
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin thông báo')
    }
}

const is_joined = computed(() => {
    return club.value?.members?.some(member => member.user_id === getUser.value.id && member.status == 'active') ?? false
})

const goBack = () => {
    router.back()
}

const getClubActivities = async () => {
    try {
        const response = await ClubService.getClubActivities(clubId, {
            page: currentActivityPage.value,
            per_page: activityPerPage.value,
        })
        const allActivities = (response.data.activities || []).map(formatActivity)

        // Split into main list, upcoming, and history
        activities.value = allActivities.slice(0, 3) // Show first 3 on main page

        upcomingActivities.value = allActivities.filter(a => a.status !== 'completed' && !dayjs().isAfter(dayjs(a.end_time)))
        historyActivities.value = allActivities.filter(a => a.status === 'completed' || dayjs().isAfter(dayjs(a.end_time)))

        startCountdown()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin hoạt động')
    }
}

const getClubLeaderBoard = async () => {
    isLeaderboardLoading.value = true
    try {
        const response = await ClubService.getClubLeaderBoard(clubId, {
            month: leaderboardFilters.value.month,
            year: leaderboardFilters.value.year,
            per_page: leaderboardFilters.value.per_page,
            page: leaderboardFilters.value.page,
        })

        const allData = response.data.leaderboard

        // Only update topThree if we are on the first page
        if (leaderboardFilters.value.page === 1) {
            topThree.value = allData.slice(0, 3)
            leaderboard.value = allData.slice(3)
        } else {
            leaderboard.value = allData
        }

        leaderboardMeta.value = response.meta
    } catch (error) {
        console.log(error)
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin xếp hạng')
    } finally {
        isLeaderboardLoading.value = false
    }
}

const getClubJoiningRequests = async () => {
    if(!isAdminView.value) return
    try {
        const response = await ClubService.joiningRequests(clubId)
        joiningRequests.value = response.data
    } catch (error) {
        console.log(error)
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin yêu cầu tham gia')
    }
}

const approveJoinRequest = async (requestId) => {
    try {
        await ClubService.approveJoinRequest(clubId, requestId)
        await getClubJoiningRequests()
        toast.success('Đã duyệt yêu cầu tham gia')
    } catch (error) {
        console.log(error)
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi duyệt yêu cầu tham gia')
    }
}

const rejectJoinRequest = async (requestId) => {
    try {
        await ClubService.rejectJoinRequest(clubId, requestId)
        await getClubJoiningRequests()
        toast.success('Đã từ chối yêu cầu tham gia')
    } catch (error) {
        console.log(error)
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi từ chối yêu cầu tham gia')
    }
}

const handleLeaderboardFilter = (filters) => {
    leaderboardFilters.value = { ...leaderboardFilters.value, ...filters, page: 1 }
    getClubLeaderBoard()
}

const handleLeaderboardPageChange = (page) => {
    leaderboardFilters.value.page = page
    getClubLeaderBoard()
}

const getFund = async () => {
    try {
        const response = await ClubService.getFund(clubId)
        fund.value = response.data
    } catch (error) {
        console.log(error)
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin quỹ')
    }
}

onMounted(async () => {
    if (!clubId) {
        isInitialLoading.value = false;
        return;
    }
    try {
        await getClubDetail()
        await Promise.all([
            getClubNotification(),
            getClubActivities(),
            getClubLeaderBoard(),
            getClubJoiningRequests(),
            getFund()
        ])
    } finally {
        isInitialLoading.value = false
    }
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