<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="flex items-center justify-end">
        <button
          @click="markAllAsRead"
          class="flex items-center gap-2 bg-[#D72D36] hover:bg-red-500 text-white px-4 py-2 rounded-lg text-sm"
        >
          <CheckIcon class="w-5 h-5" />
          <span class="hidden sm:inline">Đánh dấu tất cả</span>
        </button>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Tabs -->
      <div class="flex gap-2 bg-white rounded-lg p-2 shadow-md mb-6 overflow-x-auto">
        <button
          v-for="tab in filterTabs"
          :key="tab.value"
          @click="changeFilter(tab.value)"
          :class="[
            'px-4 py-2 rounded-lg font-medium whitespace-nowrap transition',
            activeFilter === tab.value
              ? 'bg-[#D72D36] text-white'
              : 'text-gray-600 hover:bg-gray-100'
          ]"
        >
          {{ tab.label }} ({{ tab.count }})
        </button>
      </div>

      <!-- Empty -->
      <div
        v-if="notifications.length === 0 && !loading"
        class="text-center pt-8 text-gray-500"
      >
        Không có thông báo nào
      </div>

      <!-- List -->
      <div class="space-y-4">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          @click="handleClick(notification)"
          :class="[
            'cursor-pointer rounded-lg shadow-sm p-4 sm:p-6 transition hover:shadow-md',
            getNotificationClass(notification)
          ]"
        >
          <div class="flex gap-4">
            <!-- ICON -->
            <component
              :is="getIcon(notification.type)"
              class="w-6 h-6"
              :class="{
                'text-green-500': notification.type === 'success',
                'text-yellow-500': notification.type === 'warning',
                'text-blue-500': notification.type === 'info',
              }"
            />

            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2 mb-2">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  {{ notification.title }}
                  <span
                    v-if="!notification.read"
                    class="w-2 h-2 bg-blue-600 rounded-full"
                  />
                </h3>
                <span class="text-xs sm:text-sm text-gray-500 whitespace-nowrap">
                  {{ notification.time }}
                </span>
              </div>

              <p class="text-gray-600 mb-4 text-sm sm:text-base">
                {{ notification.message }}
              </p>

              <div class="flex gap-2 flex-wrap">
                <button
                  v-if="!notification.read"
                  @click.stop="markOneAsRead(notification.id)"
                  class="flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-md text-sm"
                >
                  <CheckIcon class="w-4 h-4" />
                  Đánh dấu đã đọc
                </button>

                <button
                  @click.stop="deleteOne(notification.id)"
                  class="flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-md text-sm"
                >
                  <TrashIcon class="w-4 h-4" />
                  Xóa
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-8 text-gray-500">
          Đang tải...
        </div>

        <div
          v-if="!hasMore && notifications.length"
          class="text-center py-8 text-gray-400"
        >
          Đã hiển thị tất cả thông báo
        </div>

        <div ref="scrollTrigger" class="h-4"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  CheckIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/solid'
import { TrashIcon } from '@heroicons/vue/24/outline'

import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import * as NotificationService from '@/service/notifications'
import { toast } from 'vue3-toastify'

/* ================= ROUTER ================= */
const router = useRouter()

/* ================= STATE ================= */
const notifications = ref([])
const unreadCount = ref(0)
const readCount = ref(0)
const totalCount = ref(0)

const activeFilter = ref('all')
const page = ref(1)
const perPage = 15

const loading = ref(false)
const hasMore = ref(true)
const scrollTrigger = ref(null)

/* ================= HELPERS ================= */
const formatTime = (date) =>
  new Date(date).toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })

/* ================= PARSE ================= */
const parseNotification = (n) => {
  const data = n.data || {}

  let title = data.title || 'Thông báo'
  let message = data.message || ''

  switch (n.type) {
    case 'MiniTournamentMessageNotification':
    case 'PrivateMessageNotification':
    case 'TournamentMessageNotification':
      title = data.sender_name
        ? `${data.sender_name} đã gửi tin nhắn`
        : 'Tin nhắn mới'
      message =
        data.type === 'image'
          ? 'Đã gửi một hình ảnh'
          : data.content || ''
      break

    case 'MiniMatchCreatedNotification':
    case 'MiniMatchUpdatedNotification':
    case 'MiniMatchResultConfirmedNotification':
      title = data.title || 'Cập nhật trận đấu'
      message = data.message || 'Có cập nhật mới về trận đấu'
      break

    case 'MiniTournamentInvitationNotification':
    case 'MiniTournamentCreatorInvitationNotification':
    case 'TournamentInvitationNotification':
      title = data.title || 'Lời mời tham gia'
      message = data.message || 'Bạn được mời tham gia'
      break

    case 'MiniTournamentJoinRequestNotification':
    case 'TournamentJoinRequestNotification':
      title = 'Yêu cầu tham gia'
      message = 'Có người gửi yêu cầu tham gia'
      break

    case 'MiniTournamentJoinConfirmedNotification':
    case 'TournamentJoinConfirmedNotification':
      title = 'Tham gia thành công'
      message = 'Yêu cầu tham gia của bạn đã được chấp nhận'
      break

    case 'MiniTournamentRemovedNotification':
    case 'TournamentRemovedNotification':
      title = data.title || 'Giải đấu đã bị hủy'
      message = data.message || 'Giải đấu không còn tồn tại'
      break

    case 'MiniTournamentReminder':
      title = data.title || 'Nhắc nhở giải đấu'
      message = data.message || 'Giải đấu sắp diễn ra'
      break

    case 'FollowNotification':
      title = 'Theo dõi mới'
      message = 'Có người vừa theo dõi bạn'
      break

    case 'VerifyEmailNotification':
      title = 'Xác thực email'
      message = 'Vui lòng xác thực email của bạn'
      break
  }

  return {
    id: n.id,
    read: !!n.read_at,
    time: formatTime(n.created_at),

    type: 'info',
    title,
    message,

    rawType: n.type,
    rawData: data,
  }
}

/* ================= API ================= */
const loadData = async () => {
  if (loading.value || !hasMore.value) return
  loading.value = true

  const res = await NotificationService.getNotifications({
    type: activeFilter.value,
    page: page.value,
    per_page: perPage,
  })

  const items = res.data.notifications.map(parseNotification)

  if (page.value === 1) notifications.value = []
  notifications.value.push(...items)

  totalCount.value = res.meta.total
  unreadCount.value = res.meta.unread_count
  readCount.value = res.meta.read_count

  hasMore.value = page.value < res.meta.last_page
  loading.value = false
}

const changeFilter = (filter) => {
  activeFilter.value = filter
  page.value = 1
  hasMore.value = true
  loadData()
}

/* ================= ACTIONS ================= */
const markOneAsRead = async (id) => {
  await NotificationService.markAsRead(id)

  const item = notifications.value.find(n => n.id === id)
  if (item && !item.read) {
    item.read = true
    unreadCount.value--
    readCount.value++
  }
}

const markAllAsRead = async () => {
  await NotificationService.markAsRead(null)
  toast.success('Đã đánh dấu tất cả thông báo là đã đọc')

  notifications.value.forEach(n => (n.read = true))
  unreadCount.value = 0
  readCount.value = totalCount.value
}

const deleteOne = async (id) => {
  await NotificationService.deleteNotification(id)
  toast.success('Đã xóa thông báo')

  const item = notifications.value.find(n => n.id === id)
  if (item) {
    item.read ? readCount.value-- : unreadCount.value--
    totalCount.value--
  }

  notifications.value = notifications.value.filter(n => n.id !== id)
}

/* ================= CLICK → ROUTE ================= */
const handleClick = async (notification) => {
  if (!notification.read) {
    await markOneAsRead(notification.id)
  }

  redirectByType(notification)
}

const redirectByType = (n) => {
  const d = n.rawData || {}

  switch (n.rawType) {
    case 'PrivateMessageNotification':
      if (!d.sender_id) return
      router.push({
        name: 'profile',
        params: { id: d.sender_id }
      })
      break

    case 'TournamentMessageNotification':
      if (!d.tournament_id) return
      router.push({
        name: 'tournament-detail',
        params: { id: d.tournament_id }
      })
      break

    case 'MiniTournamentMessageNotification':
      if (!d.mini_tournament_id) return
      router.push({
        name: 'mini-tournament-detail',
        params: { id: d.mini_tournament_id }
      })
      break

    case 'MiniMatchCreatedNotification':
      if (!d.mini_tournament_id) return
      router.push({
        name: 'mini-tournament-detail',
        params: { id: d.mini_tournament_id }
      })
      break

    case 'MiniMatchUpdatedNotification':
      if (!d.mini_tournament_id) return
      router.push({
        name: 'mini-tournament-detail',
        params: { id: d.mini_tournament_id }
      })
      break

    case 'MiniMatchResultConfirmedNotification':
      if (!d.match_id) return
      router.push({
        name: 'mini-match-verify',
        params: { id: d.match_id }
      })
      break

    case 'MiniTournamentInvitationNotification':
      if (!d.tournament_id) return
      router.push({
        name: 'mini-tournament-detail',
        params: { id: d.tournament_id }
      })
      break

    case 'MiniTournamentJoinConfirmedNotification':
      if (!d.tournament_id) return
      router.push({
        name: 'mini-tournament-detail',
        params: { id: d.tournament_id }
      })
      break

    case 'TournamentInvitationNotification':
      if (!d.tournament_id) return
      router.push({
        name: 'tournament-detail',
        params: { id: d.tournament_id }
      })
      break

    case 'TournamentJoinConfirmedNotification':
      if (!d.tournament_id) return
      router.push({
        name: 'tournament-detail',
        params: { id: d.tournament_id }
      })
      break

    default:
      router.push({ name: 'notifications' })
  }
}

/* ================= SCROLL ================= */
let observer = null

onMounted(() => {
  loadData()

  if (scrollTrigger.value) {
    observer = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting && hasMore.value && !loading.value) {
        page.value++
        loadData()
      }
    }, { rootMargin: '100px' })

    observer.observe(scrollTrigger.value)
  }
})

onUnmounted(() => {
  if (observer && scrollTrigger.value) {
    observer.unobserve(scrollTrigger.value)
    observer.disconnect()
  }
})

/* ================= UI ================= */
const getIcon = (type) => {
  return {
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
  }[type] || InformationCircleIcon
}

const getNotificationClass = (n) => {
  if (n.read) return 'bg-gray-50'

  return {
    success: 'bg-green-50 border-l-4 border-green-500',
    warning: 'bg-yellow-50 border-l-4 border-yellow-500',
    info: 'bg-blue-50 border-l-4 border-blue-500',
  }[n.type]
}

const filterTabs = computed(() => [
  { value: 'all', label: 'Tất cả', count: totalCount.value },
  { value: 'unread', label: 'Chưa đọc', count: unreadCount.value },
  { value: 'read', label: 'Đã đọc', count: readCount.value },
])
</script>