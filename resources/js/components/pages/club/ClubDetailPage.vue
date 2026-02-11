<template>
    <div class="p-4 mx-auto w-full max-w-8xl rounded-md flex flex-col overflow-y-auto">
        <!-- Loading Skeleton -->
        <ClubDetailSkeleton v-if="isInitialLoading" />

        <template v-else>
            <!-- Admin/Staff View -->
            <div v-if="hasAnyRole(['admin', 'manager', 'secretary', 'treasurer'])"
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
                            <p class="text-gray-400 text-sm font-medium">{{ getRoleName(currentUserRole) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="p-2 cursor-pointer hover:bg-white/10 rounded-full transition-colors" @click="toggleChangeClub">
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
                            <button v-if="hasAnyRole(['admin'])"
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
                            <button v-if="currentUserRole === 'admin' && adminCount === 1"
                                class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors"
                                @click="openNotification">
                                <CompareArrowsIcon class="w-5 h-5 text-gray-500" />
                                <span class="font-medium">Nhượng CLB</span>
                            </button>
                            <button v-if="(currentUserRole === 'admin' && adminCount > 1) || hasAnyRole(['manager', 'secretary', 'treasurer'])"
                                class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-[#FBEAEA] transition-colors"
                                @click="leaveClub">
                                <ArrowRightOnRectangleIcon class="w-5 h-5 text-[#D72D36]" />
                                <span class="font-medium text-[#D72D36]">Rời CLB</span>
                            </button>
                            <button v-if="hasAnyRole(['admin'])"
                                class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-[#FBEAEA] transition-colors"
                                @click="deleteClub">
                                <TrashIcon class="w-5 h-5 text-[#D72D36]" />
                                <span class="font-medium text-[#D72D36]">Xoá CLB</span>
                            </button>
                        </div>
                        <div v-if="isChangeClubOpen" class="absolute right-0 top-14 w-56 bg-white rounded-xl shadow-2xl z-50 text-gray-800 border border-gray-100 animate-in fade-in zoom-in duration-200">
                            <div class="py-2 max-h-48 overflow-y-auto custom-scrollbar">
                                <template v-for="item in myClubs" :key="item.id">
                                   <div class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-100 transition-colors cursor-pointer" @click="changeClub(item)">
                                        <img v-if="item.logo_url" :src="item.logo_url" alt="" class="w-8 h-8 rounded-full object-cover">
                                        <div v-else class="w-8 h-8 rounded-full bg-red-100 text-[#D72D36] flex items-center justify-center font-bold text-xs">
                                            {{ item.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="font-medium truncate" v-tooltip="item.name">{{ item.name }}</span>
                                        <span v-if="item.id == clubId" class="ml-auto text-[10px] bg-green-50 text-green-600 px-1.5 py-0.5 rounded-full font-bold uppercase whitespace-nowrap">
                                            hiện tại
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-8 relative z-10">
                    <div v-for="(stat, index) in statsAdmin" :key="index"
                        class="bg-[#3E414C]/80 backdrop-blur-md rounded-2xl p-4 sm:p-6 border border-white/5 shadow-inner">
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
                <div v-if="isMenuOpen || isChangeClubOpen" class="fixed inset-0 z-[9999] bg-black/50 backdrop-blur-sm" @click="closeMenu">
                </div>
                <div class="flex items-center">
                    <div>
                        <div v-if="club.profile?.address"
                            class="flex items-center space-x-2 relative rounded-full overflow-hidden bg-white w-fit text-black py-1 px-2">
                            <MapPinIcon class="w-5 h-5" />
                            <div class="text-sm font-semibold">{{ club.profile.address }}</div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="text-3xl sm:text-4xl lg:text-[44px] font-bold">{{ club.name }}</div>
                            <div v-if="club.is_verified" class="bg-[#4392E0] rounded-full p-1">
                                <VerifyIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div class="flex items-center space-x-2" v-if="!is_joined">
                            <template v-if="!club.has_pending_request">
                                <Button size="md" color="danger"
                                    class="px-6 sm:px-12 md:px-[75px] bg-[#D72D36] border border-[#D72D36] text-white hover:bg-white hover:text-[#D72D36] flex gap-2"
                                    @click.stop="joinClubRequest">
                                    <PlusIcon class="w-5 h-5" />
                                    Tham gia CLB
                                </Button>
                            </template>
                            <template v-else>
                                <Button size="md" color="danger"
                                    class="px-6 sm:px-12 md:px-[75px] bg-[#D72D36] border border-[#D72D36] text-white hover:bg-white hover:text-[#D72D36] flex gap-2"
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
                <div class="col-span-12 lg:col-span-8 order-2 lg:order-1">
                    <div v-if="is_joined">
                        <div class="flex items-baseline justify-between">
                            <h2 class="text-2xl text-[#838799] font-semibold uppercase mb-4">Thông báo</h2>
                            <p class="text-[#D72D36] font-semibold cursor-pointer" @click="openNotification">Xem tất cả</p>
                        </div>
                        <template v-if="notifications.length > 0 && pinnedNotifications.length > 0">
                            <NotificationCard v-for="(notification, index) in pinnedNotifications" :key="index"
                                :data="notification" @unpin="handleUnpinNotification" />
                        </template>
                        <div v-else class="p-4 text-center">
                            <p class="text-[#838799]">Hiện chưa có thông báo ghim nào</p>
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
                                @click-card="goToActivityDetail(activity)" @edit="handleEditActivity(activity)"
                                @register="handleRegisterActivity(activity)" @cancel-join="handleCancelJoinActivity(activity)" @check-in="handleCheckInActivity(activity)" />
                        </template>
                        <div v-else class="p-4 text-center">
                            <p class="text-[#838799]">Hiện chưa có lịch thi đấu</p>
                        </div>
                    </div>
                    <ClubInfoTabs :club="club" :isJoined="is_joined" :currentUserRole="currentUserRole" :top-three="topThree" :leaderboard="leaderboard"
                        :leaderboard-meta="leaderboardMeta" :leaderboard-filters="leaderboardFilters"
                        :leaderboard-loading="isLeaderboardLoading" @leaderboard-filter="handleLeaderboardFilter"
                        @leaderboard-page-change="handleLeaderboardPageChange" @tab-change="handleTabChange"
                        @refresh-club="getClubDetail" />
                </div>
                <div class="col-span-12 lg:col-span-4 space-y-4 order-1 lg:order-2">
                    <div class="max-w-3xl mx-auto" v-if="!hasAnyRole(['admin', 'manager', 'secretary', 'treasurer'])">
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
                    <div class="max-w-3xl mx-auto" v-if="hasAnyRole(['admin', 'manager', 'secretary', 'treasurer', 'member'])">
                        <div class="bg-white rounded-2xl shadow-md px-2 py-5">
                            <div class="grid grid-cols-4 text-center">
                                <div v-for="(module, index) in clubModules" :key="index"
                                    class="flex flex-col items-center gap-2">
                                    <div class="text-[#D72D36] rounded-md bg-[#FBEAEB] p-4 cursor-pointer" @click="handleModuleClick(module)">
                                        <component :is="module.icon" class="w-6 h-6" />
                                    </div>
                                    <div class="text-sm text-[#3E414C]">{{ module.label }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="max-w-3xl mx-auto" v-if="hasAnyRole(['admin','secretary'])">
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
            <ClubNotificationModal
                v-model="isNotificationModalOpen"
                :notifications="notifications"
                :meta="notificationMeta"
                :is-loading-more="isLoadingMoreNotifications"
                :is-admin-or-staff="hasAnyRole(['admin', 'manager', 'secretary'])"
                @close="closeNotification"
                @load-more="loadMoreNotifications"
                @mark-as-read="markAsRead"
                @mark-all-as-read="markAllAsRead"
                @unpin="handleUnpinNotification"
                @create="handleCreateNotification"
            />

            <!-- Activity Schedule Modal -->
            <ClubActivityModal :is-open="isActivityModalOpen" :thumbnail="Thumbnail"
                :upcoming-activities="upcomingActivities" :history-activities="historyActivities"
                :next-match="nextMatch" :countdown="countdownText" @close="closeActivityModal" @edit="handleEditActivity" 
                @click-card="goToActivityDetail" @register="handleRegisterActivity" @cancel-join="handleCancelJoinActivity" @check-in="handleCheckInActivity" />

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
                :is-loading="isUpdatingZalo"
                @save="handleUpdateZalo" 
            />

            <!-- Create Notification Modal -->
             <ClubCreateNotificationModal
                v-model="isCreateNotificationModalOpen"
                :club="club"
                :is-loading="isCreatingNotification"
                @create="handleCreateNotificationSubmit"
                :notification-type="notificationType"
            />

            <DeleteConfirmationModal
                v-model="isDeleteModalOpen"
                title="Xoá câu lạc bộ"
                message="Bạn có chắc chắn muốn xoá câu lạc bộ này? Thao tác này không thể hoàn tác."
                confirmButtonText="Xoá ngay"
                @confirm="confirmDeleteClub"
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
    TrashIcon,
    ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'
import { useRouter, useRoute } from 'vue-router'
import { computed, onMounted, ref, watch } from 'vue'
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
import ClubNotificationModal from '@/components/organisms/ClubNotificationModal.vue';
import { CLUB_STATS, CLUB_MODULES } from '@/data/club/index.js';
import * as ClubService from '@/service/club.js'
import { toast } from 'vue3-toastify';
import { useUserStore } from '@/store/auth'
import { storeToRefs } from 'pinia'
import ClubActivityModal from '@/components/organisms/ClubActivityModal.vue';
import ClubCreateNotificationModal from '@/components/organisms/ClubCreateNotificationModal.vue';
import DeleteConfirmationModal from '@/components/molecules/DeleteConfirmationModal.vue';
import dayjs from 'dayjs'
import 'dayjs/locale/vi'
import ClubDetailSkeleton from '@/components/molecules/ClubDetailSkeleton.vue';

dayjs.locale('vi')

const router = useRouter()
const route = useRoute()
const clubStats = CLUB_STATS;
const clubModules = CLUB_MODULES;
const isMenuOpen = ref(false)
const isChangeClubOpen = ref(false)
const isNotificationModalOpen = ref(false)
const isActivityModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isZaloModalOpen = ref(false)
const isCreateNotificationModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isUpdatingClub = ref(false)
const isUpdatingZalo = ref(false)
const isCreatingNotification = ref(false)
const club = ref([]);
const clubId = ref(route.params.id);
const userStore = useUserStore()
const { getUser } = storeToRefs(userStore)
const notifications = ref([])
const notificationMeta = ref({})
const currentNotificationPage = ref(1)
const notificationPerPage = ref(15)
const isLoadingMoreNotifications = ref(false)
const myClubs = ref([])
const activities = ref([])
const upcomingActivities = ref([])
const historyActivities = ref([])
const currentActivityPage = ref(1)
const activityPerPage = ref(20)
const fund = ref([])
const notificationType = ref([])
const hasFetchedLeaderboard = ref(false)

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

const joiningRequests = ref([])

const countdownText = ref('')
let countdownInterval = null

const currentUserMember = computed(() => {
    return club.value?.members?.find(member => member.user_id === getUser.value.id) || null
})

const hasAnyRole = (roles = []) => {
    return roles.includes(currentUserMember.value?.role)
}

const currentUserRole = computed(() => {
    return currentUserMember.value?.role || null
})

const adminCount = computed(() => {
    return club.value?.members?.filter(member => member.role === 'admin').length || 0
})

const getRoleName = (role) => {
    switch (role) {
        case 'admin':
            return 'Quản trị viên'
        case 'manager':
            return 'Quản lý'
        case 'secretary':
            return 'Thư ký'
        case 'treasurer':
            return 'Thủ quỹ'
        default:
            return 'Thành viên'
    }
}

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

  const upcoming = activities.value
    .filter(a => dayjs(a.start_time).isAfter(now))
    .sort((a, b) =>
      dayjs(a.start_time).diff(dayjs(b.start_time))
    )

  return upcoming[0] || null
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
    if (isMenuOpen.value) {
        isChangeClubOpen.value = false
    }
}

const toggleChangeClub = () => {
    isChangeClubOpen.value = !isChangeClubOpen.value
    if (isChangeClubOpen.value) {
        isMenuOpen.value = false
    }
}

const closeMenu = () => {
    isMenuOpen.value = false
    isChangeClubOpen.value = false
}

const closeChangeClub = () => {
    isChangeClubOpen.value = false
}

const changeClub = (item) => {
    isChangeClubOpen.value = false
    router.push({ name: 'club-detail', params: { id: item.id } })
}

const shareClub = () => {
    toast.info('Chức năng đang được phát triển')
}

const openNotification = () => {
    isNotificationModalOpen.value = true
}

const openEditModal = () => {
    isEditModalOpen.value = true
}

const openZaloModal = () => {
    isZaloModalOpen.value = true
}

watch(
    () => [
        isNotificationModalOpen.value,
        isEditModalOpen.value,
        isZaloModalOpen.value,
    ],
    (values) => {
        if (values.some(v => v)) {
            closeMenu()
            closeChangeClub()
        }
    }
)

const closeNotification = () => {
    isNotificationModalOpen.value = false
}

const openActivityModal = () => {
    isActivityModalOpen.value = true
}

const closeActivityModal = () => {
    isActivityModalOpen.value = false
}

const handleModuleClick = (module) => {
    if (module.key === 'schedule') {
        if (!hasAnyRole(['admin', 'manager', 'secretary'])) {
            toast.warning('Bạn không có quyền thực hiện chức năng này')
            return
        }
        router.push({ name: 'club-create-activity', params: { id: clubId.value } })
    } else if (module.key === 'notification') {
        openNotification()
    } else if (module.key === 'chat') {
        toast.info(`Chức năng ${module.label} đang được phát triển`)
    } else if (module.key === 'fund') {
        router.push({ name: 'club-fund', params: { id: clubId.value } })
    }
}

const handleCreateNotification = async() => {
    await getNotificationType()
    isCreateNotificationModalOpen.value = true
    isNotificationModalOpen.value = false
}

const handleCreateNotificationSubmit = async (data) => {
    isCreatingNotification.value = true
    try {
        const formData = new FormData()
        formData.append('title', data.title)
        formData.append('content', data.content)
        formData.append('club_notification_type_id', data.club_notification_type_id)
        formData.append('is_pinned', data.is_pinned)
        formData.append('status', data.status)
        if (Array.isArray(data.user_ids)) {
            data.user_ids.forEach(id => {
                formData.append('user_ids[]', id)
            })
        } else {
             formData.append('user_ids[]', data.user_ids)
        }
        
        if (data.attachment) {
            formData.append('attachment', data.attachment)
        }

        await ClubService.createNotification(clubId.value, formData)
        await getClubNotification()
        isCreateNotificationModalOpen.value = false
        toast.success('Tạo thông báo thành công')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi tạo thông báo')
    } finally {
        isCreatingNotification.value = false
    }
}

const getNotificationType = async () => {
    try {
        const response = await ClubService.getNotificationType(clubId.value)
        notificationType.value = response.data
    } catch (error) {
        console.error('Error fetching notification types:', error)
    }
}

const handleEditActivity = (activity) => {
    router.push({
        name: 'club-activity-edit',
        params: { id: clubId.value, activityId: activity.id }
    })
}

const goToActivityDetail = (activity, query = {}) => {
    router.push({
        name: 'club-detail-activity',
        params: { id: clubId.value },
        query: { activityId: activity.id, ...query }
    })
}

const getVietnameseDay = (date) => {
    const days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
    return days[dayjs(date).day()]
}

const formatActivity = (item) => {
    const userId = getUser.value.id
    const isCompleted = item.status === 'completed' || dayjs().isAfter(dayjs(item.end_time))
    const userParticipant = item.participants?.find(p => p.user_id === userId)
    const isRegistered = !!userParticipant

    let registrationStatus = 'none'
    if (userParticipant) {
        if (userParticipant.status === 'pending') {
            registrationStatus = 'pending'
        } else if (userParticipant.status === 'accepted' || !userParticipant.status) {
            registrationStatus = 'accepted'
        }
    }

    // Determine type (border color)
    let type = 'danger' // Default to red (not registered)
    if (isCompleted) {
        type = 'secondary' // Gray
    } else if (isRegistered) {
        type = 'primary' // Blue (registered/pending)
    }

    // Determine status badge (colors) - ActivityScheduleCard & ActivitySmallCard
    let status = item.is_public ? 'open' : 'private'
    if (isCompleted) status = 'completed'

    // Determine statusText (label) - ActivitySmallCard & updated ActivityScheduleCard
    let statusText = item.is_public ? 'Công khai' : 'Nội bộ'
    if (isCompleted) statusText = 'Hoàn tất'

    // Determine button text
    let buttonText = 'Đăng ký'
    if (isCompleted) {
        buttonText = 'Đã xong'
    } else if (isRegistered) {
        buttonText = registrationStatus === 'pending' ? 'Đang chờ duyệt' : 'Check-in ngay'
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
        isCreator: item.created_by === getUser.value.id,
        location: item.location,
        participants_list: item.participants || [],
        result: isCompleted ? `Địa điểm: ${item.location}` : null,
        registrationStatus
    }
}

const getClubDetail = async () => {
    try {
        const response = await ClubService.clubDetail(clubId.value)
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
        
        await ClubService.updateClub(clubId.value, formData)
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
    isUpdatingZalo.value = true
    try {
        const formData = new FormData()
        formData.append('zalo_link_enabled', data.zalo_link_enabled ? '1' : '0')
        formData.append('zalo_link', data.zalo_link || '')
        formData.append('qr_zalo_enabled', data.qr_zalo_enabled ? '1' : '0')

        if (data.qr_code_image_url instanceof File) {
            formData.append('qr_code_image_url', data.qr_code_image_url)
        }

        await ClubService.updateClub(clubId.value, formData)
        await getClubDetail()
        isZaloModalOpen.value = false
        toast.success('Cập nhật thông tin Zalo thành công')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi cập nhật thông tin')
    } finally {
        isUpdatingZalo.value = false
    }
}

const joinClubRequest = async () => {
    try {
        await ClubService.joinRequest(clubId.value)
        await getClubDetail(clubId.value)
        toast.success('Yêu cầu tham gia đã được gửi thành công')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi gửi yêu cầu tham gia')
    }
}

const cancelJoinRequest = async () => {
    try {
        await ClubService.cancelJoinRequest(clubId.value)
        await getClubDetail(clubId.value)
        toast.success('Đã huỷ yêu cầu tham gia')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi huỷ yêu cầu tham gia')
    }
}

const leaveClub = async () => {
    try {
        await ClubService.leaveClub(clubId.value)
        await getClubDetail(clubId.value)
        isMenuOpen.value = false
        toast.success('Đã rời câu lạc bộ')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi rời câu lạc bộ')
    }
}

const getClubNotification = async (append = false) => {
    if (append) {
        isLoadingMoreNotifications.value = true
    }
    try {
        const response = await ClubService.clubNotification(clubId.value, {
            page: currentNotificationPage.value,
            per_page: notificationPerPage.value,
            status: 'sent'
        })
        if (append) {
            notifications.value = [...notifications.value, ...response.data.notifications]
        } else {
            notifications.value = response.data.notifications
        }
        notificationMeta.value = response.meta
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin thông báo')
    } finally {
        isLoadingMoreNotifications.value = false
    }
}

const loadMoreNotifications = async () => {
    if (currentNotificationPage.value < notificationMeta.value.last_page) {
        currentNotificationPage.value++
        await getClubNotification(true)
    }
}

const markAsRead = async (notificationId) => {
    try {
        await ClubService.markAsRead(clubId.value, notificationId)
        await getClubNotification()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi đánh dấu thông báo đã đọc')
    }
}

const markAllAsRead = async () => {
    try {
        await ClubService.markAllAsRead(clubId.value)
        currentNotificationPage.value = 1
        await getClubNotification()
        toast.success('Đánh dấu tất cả thông báo đã đọc')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi đánh dấu tất cả thông báo đã đọc')
    }
}

const handleUnpinNotification = async (notificationId) => {
    if(!hasAnyRole(['admin', 'manager', 'secretary'])) return
    try {
        await ClubService.togglePin(clubId.value, notificationId)
        await getClubNotification()
        toast.success('Đã gỡ ghim thông báo')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi gỡ ghim thông báo')
    }
}

const pinnedNotifications = computed(() => {
    return notifications.value.filter(notification => notification.is_pinned === true)
})

const is_joined = computed(() => {
    return club.value?.members?.some(member => member.user_id === getUser.value.id && member.status == 'active') ?? false
})

const goBack = () => {
    router.back()
}

const getClubActivities = async () => {
    try {
        const response = await ClubService.getClubActivities(clubId.value, {
            page: currentActivityPage.value,
            per_page: activityPerPage.value,
        })
        const allActivities = (response.data.activities || []).map(formatActivity)

        // Split into main list, upcoming, and history
        upcomingActivities.value = allActivities.filter(a => a.status !== 'completed' && !dayjs().isAfter(dayjs(a.end_time)))
        historyActivities.value = allActivities.filter(a => a.status === 'completed' || dayjs().isAfter(dayjs(a.end_time)))

        activities.value = upcomingActivities.value.slice(0, 5)

        startCountdown()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin hoạt động')
    }
}

const getClubLeaderBoard = async () => {
    isLeaderboardLoading.value = true
    try {
        const response = await ClubService.getClubLeaderBoard(clubId.value, {
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
        hasFetchedLeaderboard.value = true
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin xếp hạng')
    } finally {
        isLeaderboardLoading.value = false
    }
}

const getClubJoiningRequests = async () => {
    if(!hasAnyRole(['admin', 'secretary'])){
        isInitialLoading.value = false
        return
    }
    try {
        const response = await ClubService.joiningRequests(clubId.value)
        joiningRequests.value = response.data
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin yêu cầu tham gia')
    }
}

const approveJoinRequest = async (requestId) => {
    try {
        await ClubService.approveJoinRequest(clubId.value, requestId)
        await getClubJoiningRequests()
        toast.success('Đã duyệt yêu cầu tham gia')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi duyệt yêu cầu tham gia')
    }
}

const rejectJoinRequest = async (requestId) => {
    try {
        await ClubService.rejectJoinRequest(clubId.value, requestId)
        await getClubJoiningRequests()
        toast.success('Đã từ chối yêu cầu tham gia')
    } catch (error) {
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
        const response = await ClubService.getFund(clubId.value)
        fund.value = response.data
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin quỹ')
    }
}

const getMyClubs = async () => {
    if(!hasAnyRole(['admin', 'secretary', 'manager', 'manager'])){
        return
    }
    try {
        const response = await ClubService.myClubs()
        myClubs.value = response
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin câu lạc bộ của tôi')
    }
}

const handleTabChange = (tabId) => {
    if (tabId === 'ranking' && !hasFetchedLeaderboard.value) {
        getClubLeaderBoard()
    }
}

const loadAllData = async () => {
    isInitialLoading.value = true
    try {
        await getClubDetail()
        await Promise.all([
            getClubNotification(),
            getClubActivities(),
            getClubJoiningRequests(),
            getFund(),
            getMyClubs()
        ])
    } finally {
        isInitialLoading.value = false
    }
}

const deleteClub = () => {
    isDeleteModalOpen.value = true
}

const confirmDeleteClub = async () => {
    try {
        await ClubService.deleteClub(clubId.value)
        toast.success('Xoá câu lạc bộ thành công')
        router.push({ name: 'club' })
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi xoá câu lạc bộ')
    }
}

watch(() => route.params.id, (newId) => {
    if (newId && newId !== clubId.value) {
        clubId.value = newId
        hasFetchedLeaderboard.value = false
        loadAllData()
    }
})

onMounted(async () => {
    if (!clubId.value) {
        isInitialLoading.value = false;
        return;
    }
    await loadAllData()
})
const handleRegisterActivity = async (activity) => {
    try {
        await ClubService.joinActivityRequest(clubId.value, activity.id)
        toast.success('Đã gửi yêu cầu tham gia thành công')
        await getClubActivities()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Không thể gửi yêu cầu tham gia')
    }
}

const handleCancelJoinActivity = async (activity) => {
    const userId = getUser.value.id
    const userParticipant = activity.participants_list?.find(p => p.user_id === userId)
    if (!userParticipant) return
    
    try {
        await ClubService.cancelActivityJoinRequest(clubId.value, activity.id, userParticipant.id)
        toast.success('Đã hủy yêu cầu tham gia')
        await getClubActivities()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Không thể hủy yêu cầu tham gia')
    }
}

const handleCheckInActivity = (activity) => {
    goToActivityDetail(activity, { showCheckin: true })
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

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #E5E7EB;
  border-radius: 20px;
}
</style>