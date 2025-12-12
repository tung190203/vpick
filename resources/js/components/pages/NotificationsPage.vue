<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="flex items-center justify-end flex-wrap gap-4">
        <div class="flex items-center gap-3">
          <button @click="markAllAsRead"
            class="flex items-center gap-2 bg-[#D72D36] hover:bg-red-500 text-white px-4 py-2 rounded-lg transition text-sm">
            <CheckIcon class="w-5 h-5" />
            <span class="hidden sm:inline">Đánh dấu tất cả</span>
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Filter Tabs -->
      <div class="flex gap-2 sm:gap-4 bg-white rounded-lg p-2 shadow-md mb-6 overflow-x-auto">
        <button v-for="tab in filterTabs" :key="tab.value" @click="changeFilter(tab.value)" :class="[
          'flex-1 sm:flex-none px-4 py-2 rounded-lg font-medium transition whitespace-nowrap',
          activeFilter === tab.value
            ? 'bg-[#D72D36] text-white'
            : 'text-gray-600 hover:bg-gray-100'
        ]">
          {{ tab.label }} ({{ tab.count }})
        </button>
      </div>

      <!-- Notifications List -->
      <div class="space-y-4">
        <div v-if="notifications.length === 0 && !loading" class="text-center pt-8">
          <p class="text-gray-500 text-lg">Không có thông báo nào</p>
        </div>

        <div v-for="notification in notifications" :key="notification.id" :class="[
          'rounded-lg shadow-sm p-4 sm:p-6 transition hover:shadow-md',
          getNotificationClass(notification)
        ]">
          <div class="flex gap-4">
            <div class="flex-shrink-0">
              <component :is="getIcon(notification.type)" class="w-6 h-6" :class="{
                'text-green-500': notification.type === 'success',
                'text-yellow-500': notification.type === 'warning',
                'text-blue-500': notification.type === 'info',
              }" />
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2 mb-2">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  {{ notification.title }}
                  <span v-if="!notification.read" class="w-2 h-2 bg-blue-600 rounded-full"></span>
                </h3>
                <span class="text-xs sm:text-sm text-gray-500 whitespace-nowrap">
                  {{ notification.time }}
                </span>
              </div>

              <p class="text-gray-600 mb-4 text-sm sm:text-base">
                {{ notification.message }}
              </p>

              <div class="flex gap-2 flex-wrap">
                <button v-if="!notification.read" @click="markOneAsRead(notification.id)"
                  class="flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-md transition text-sm">
                  <CheckIcon class="w-4 h-4" />
                  Đánh dấu đã đọc
                </button>

                <button @click="deleteOne(notification.id)"
                  class="flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-md transition text-sm">
                  <TrashIcon class="w-4 h-4" />
                  Xóa
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-8">
          <div class="flex items-center gap-3 text-gray-600">
            <svg class="animate-spin h-6 w-6" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            <span>Đang tải...</span>
          </div>
        </div>

        <div v-if="!hasMore && notifications.length > 0" class="text-center py-8 text-gray-500">
          <p>Đã hiển thị tất cả thông báo</p>
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

import { ref, onMounted, onUnmounted, computed } from "vue";
import * as NotificationService from "@/service/notifications.js";
import { toast } from 'vue3-toastify';

const notifications = ref([]);
const unreadCount = ref(0);
const activeFilter = ref("all");

const page = ref(1);
const perPage = 10;
const loading = ref(false);
const hasMore = ref(true);
const scrollTrigger = ref(null);

// Format time
const formatTime = (dateString) => {
  const d = new Date(dateString);
  return d.toLocaleString("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  });
};

// Load notifications from API
const loadData = async () => {
  if (loading.value || !hasMore.value) return;

  loading.value = true;

  const res = await NotificationService.getNotifications({
    type: activeFilter.value,
    page: page.value,
    per_page: perPage,
  });
  const newItems = res.data.notifications.map((n) => ({
    id: n.id,
    type: "info",
    title: n.data?.title || "Thông báo",
    message: n.data?.message || "",
    read: !!n.read_at,
    time: formatTime(n.created_at),
  }));

  if (newItems.length === 0) {
    if (page.value === 1) notifications.value = [];
    hasMore.value = false;
    loading.value = false;
    return;
  }

  // Reset khi đổi filter
  if (page.value === 1) notifications.value = [];

  notifications.value.push(...newItems);
  unreadCount.value = res.meta.unread_count;

  hasMore.value = page.value < res.meta.last_page;

  loading.value = false;
};

// Change filter
const changeFilter = (filter) => {
  activeFilter.value = filter;
  page.value = 1;
  hasMore.value = true;
  loadData();
};

// Mark one notification as read
const markOneAsRead = async (id) => {
  await NotificationService.markAsRead(id);
  toast.success("Đã đánh dấu thông báo là đã đọc");
  const item = notifications.value.find(n => n.id === id);
  if (item && !item.read) {
    item.read = true;
    unreadCount.value--;
  }
};

// Mark all as read
const markAllAsRead = async () => {
  await NotificationService.markAsRead(null);
  toast.success("Đã đánh dấu tất cả thông báo là đã đọc");
  notifications.value.forEach(n => n.read = true);
  unreadCount.value = 0;
};

// Delete one
const deleteOne = async (id) => {
  await NotificationService.deleteNotification(id);
  toast.success("Đã xóa thông báo");
  notifications.value = notifications.value.filter(n => n.id !== id);
};

// Infinite scroll
let observer = null;
onMounted(() => {
  loadData();

  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && hasMore.value && !loading.value) {
        page.value++;
        loadData();
      }
    },
    { threshold: 0.1, rootMargin: "100px" }
  );

  if (scrollTrigger.value) observer.observe(scrollTrigger.value);
});

onUnmounted(() => {
  if (observer && scrollTrigger.value) observer.unobserve(scrollTrigger.value);
});

// Icon map
const getIcon = (type) => {
  return {
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
  }[type] || InformationCircleIcon;
};

// Class
const getNotificationClass = (n) => {
  if (n.read) return "bg-gray-50";
  return {
    success: "bg-green-50 border-l-4 border-green-500",
    warning: "bg-yellow-50 border-l-4 border-yellow-500",
    info: "bg-blue-50 border-l-4 border-blue-500",
  }[n.type];
};

// Filter tabs
const filterTabs = computed(() => [
  { value: "all", label: "Tất cả", count: notifications.value.length },
  { value: "unread", label: "Chưa đọc", count: unreadCount.value },
  { value: "read", label: "Đã đọc", count: notifications.value.filter(n => n.read).length },
]);
</script>