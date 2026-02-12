<template>
  <div class="min-h-screen bg-gradient-to-br flex items-center justify-center p-4"
    :class="isLoading ? 'from-gray-50 to-gray-100' : (errorMessage ? 'from-red-50 to-orange-50' : 'from-green-50 to-blue-50')">
    <div class="max-w-md w-full">
      
      <!-- Loading State -->
      <div v-if="isLoading" class="bg-white rounded-2xl shadow-2xl overflow-hidden p-12">
        <div class="text-center">
          <div class="mx-auto w-20 h-20 mb-6">
            <svg class="animate-spin h-20 w-20 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-gray-700">Đang xử lý check-in...</h2>
          <p class="text-sm text-gray-500 mt-2">Vui lòng đợi trong giây lát</p>
        </div>
      </div>

      <!-- Success/Error Card -->
      <div v-else class="bg-white rounded-2xl shadow-2xl overflow-hidden animate-slideIn">
        <!-- Header with Icon -->
        <div class="p-8 text-center"
          :class="errorMessage ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-green-500 to-green-600'">
          <div class="mx-auto w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4"
            :class="errorMessage ? '' : 'animate-bounce'">
            <!-- Success Icon -->
            <svg v-if="!errorMessage" class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
            <!-- Error Icon -->
            <svg v-else class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </div>
          <h1 class="text-3xl font-bold text-white mb-2">
            {{ errorMessage ? 'Check-in thất bại!' : 'Check-in thành công!' }}
          </h1>
          <p :class="errorMessage ? 'text-red-100' : 'text-green-100'">
            {{ errorMessage || successMessage }}
          </p>
        </div>

        <!-- Activity Details (Success only) -->
        <div v-if="!errorMessage && activityData" class="p-6 space-y-4">
          <div class="border-l-4 border-green-500 pl-4">
            <h2 class="text-xl font-bold text-gray-800 mb-2">{{ activityData.title }}</h2>
            <div class="space-y-2 text-sm text-gray-600">
              <div v-if="activityData.start_time" class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ formatDateTime(activityData.start_time) }}</span>
              </div>
              <div v-if="activityData.address" class="flex items-start">
                <svg class="w-5 h-5 mr-2 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="flex-1">{{ activityData.address }}</span>
              </div>
            </div>
          </div>

          <!-- Check-in Info -->
          <div class="bg-green-50 rounded-lg p-4">
            <p class="text-sm text-gray-700">
              <span class="font-semibold">Thời gian check-in:</span> {{ formatDateTime(new Date()) }}
            </p>
          </div>
        </div>

        <!-- Error Details -->
        <div v-else-if="errorMessage" class="p-6">
          <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4">
            <div class="flex items-start">
              <svg class="w-5 h-5 text-red-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
              <div>
                <h3 class="font-semibold text-red-800 mb-1">Có lỗi xảy ra</h3>
                <p class="text-sm text-red-700">{{ errorMessage }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="p-6 bg-gray-50 space-y-3">
          <button
            v-if="!errorMessage && clubId && activityId"
            @click="goToActivityDetail"
            class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg"
          >
            Xem chi tiết hoạt động
          </button>
          <button
            @click="goToDashboard"
            :class="errorMessage ? 'bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-600 hover:to-red-700' : 'bg-white text-gray-700 hover:bg-gray-50'"
            class="w-full font-semibold py-3 px-6 rounded-lg transition-all shadow-md hover:shadow-lg"
          >
            {{ errorMessage ? 'Quay lại trang chủ' : 'Về trang chủ' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();

const isLoading = ref(true);
const clubId = ref(null);
const activityId = ref(null);
const activityData = ref(null);
const successMessage = ref('Bạn đã check-in thành công!');
const errorMessage = ref('');

onMounted(() => {
  // Simulate loading for smooth transition
  setTimeout(() => {
    // Get data from route query params
    clubId.value = route.query.clubId;
    activityId.value = route.query.activityId;
    successMessage.value = route.query.message || 'Bạn đã check-in thành công!';
    
    // Parse activity data if available
    if (route.query.activityData) {
      try {
        activityData.value = JSON.parse(route.query.activityData);
      } catch (e) {
        console.error('Failed to parse activity data:', e);
      }
    }

    // Check if there's an error
    if (route.query.error) {
      errorMessage.value = route.query.error;
    }

    // Hide loading
    isLoading.value = false;
  }, 800); // 800ms loading animation
});

const formatDateTime = (datetime) => {
  if (!datetime) return '';
  const date = new Date(datetime);
  const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  const dayName = days[date.getDay()];
  const day = date.getDate().toString().padStart(2, '0');
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const year = date.getFullYear();
  const hours = date.getHours().toString().padStart(2, '0');
  const minutes = date.getMinutes().toString().padStart(2, '0');
  
  return `${dayName}, ${day}/${month}/${year} - ${hours}:${minutes}`;
};

const goToActivityDetail = () => {
  if (clubId.value && activityId.value) {
    router.push({
      name: 'club-detail-activity',
      params: { id: clubId.value },
      query: { activityId: activityId.value }
    });
  }
};

const goToDashboard = () => {
  router.push({ name: 'dashboard' });
};
</script>

<style scoped>
@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-bounce {
  animation: bounce 1s ease-in-out infinite;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-slideIn {
  animation: slideIn 0.4s ease-out;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
