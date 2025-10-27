<template>
  <div class="min-h-screen bg-gray-100 p-4 lg:p-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
      <!-- Left column -->
      <div class="lg:col-span-2 space-y-6">
        <!-- VN/DUPR Card -->
        <div class="bg-red-custom text-white rounded-[8px] shadow-lg p-6 relative overflow-hidden"
          :style="{ backgroundImage: `url(${Background})` }">
          <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-gradient-to-br from-red-500 to-red-700"></div>

          <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start">
              <!-- Left side - Ratings -->
              <div class="mb-6 md:mb-0">
                <div class="text-sm opacity-90 mb-1 text-[32px]">VNDUPR</div>
                <div class="text-6xl font-bold leading-none mb-4 text-[100px]">{{ homeData.user_info?.vndupr_scor ?? 0
                  }}
                </div>
                <div class="text-sm opacity-90 mb-1 text-[32px]">DUPR</div>
                <div class="text-5xl font-bold leading-none text-[100px]">3.65</div>
              </div>

              <!-- Right side - Stats & QR -->
              <div class="flex flex-col items-end justify-between h-[264px]">
                <!-- QR Icon ở trên cùng -->
                <QrCodeIcon class="w-12 h-12 mb-6 text-white" />

                <!-- Hai vòng tròn nằm dưới cùng -->
                <div class="flex items-center space-x-8">
                  <!-- Vòng cung 1 - Chiến thắng -->
                  <div class="flex flex-col items-center">
                    <div class="relative w-32 h-32">
                      <svg class="w-32 h-32 transform rotate-[225deg]" viewBox="0 0 140 140">
                        <!-- Vòng nền mờ (270 độ - 3/4 vòng tròn) -->
                        <path d="M 70 10 A 60 60 0 1 1 10 70" stroke="white" stroke-width="16" fill="none"
                          opacity="0.25" />
                        <!-- Vòng cung progress -->
                        <path d="M 70 10 A 60 60 0 1 1 10 70" stroke="white" stroke-width="16" fill="none"
                          :stroke-dasharray="`${282.74 * (homeData.user_info?.win_rate || 0) / 100} 282.74`"
                          class="transition-all duration-1000 ease-out" />
                      </svg>
                      <div class="absolute inset-0 flex items-center justify-center text-3xl font-semibold">
                        {{ homeData.user_info?.win_rate }}%
                      </div>
                    </div>
                    <p class="text-[24px] font-medium">Chiến thắng</p>
                  </div>

                  <!-- Vòng cung 2 - Phong độ -->
                  <div class="flex flex-col items-center">
                    <div class="relative w-32 h-32">
                      <svg class="w-32 h-32 transform rotate-[225deg]" viewBox="0 0 140 140">
                        <!-- Vòng nền mờ (270 độ - 3/4 vòng tròn) -->
                        <path d="M 70 10 A 60 60 0 1 1 10 70" stroke="white" stroke-width="16" fill="none"
                          opacity="0.25" />
                        <!-- Vòng cung progress -->
                        <path d="M 70 10 A 60 60 0 1 1 10 70" stroke="white" stroke-width="16" fill="none"
                          :stroke-dasharray="`${282.74 * (homeData.user_info?.performance || 0) / 100} 282.74`"
                          class="transition-all duration-1000 ease-out" />
                      </svg>
                      <div class="absolute inset-0 flex items-center justify-center text-center">
                        <span class="text-lg font-bold leading-tight" v-html="getPerformanceLevel"></span>
                      </div>
                    </div>
                    <p class="text-[24px] font-medium">Phong độ</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Kèo đấu sắp tới -->
        <section>
          <div class="flex items-center justify-start mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Kèo đấu sắp tới</h2>
            <div
              class="flex items-center text-sm text-gray-600 ml-4 cursor-pointer hover:text-gray-800 bg-[#FFFFFF] p-1.5 rounded-full shadow-md">
              <ArrowUpRightIcon class="w-4 h-4 text-gray-[#838799]" />
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="(mini, i) in homeData?.upcoming_mini_tournament" :key="i"
              class="bg-white rounded-[8px] shadow hover:shadow-lg p-4 transition-all relative">
              <!-- Bell notification icon -->
              <div class="absolute top-4 right-4 text-red-500 cursor-pointer hover:text-red-600">
                <BellAlertIcon class="w-5 h-5" />
              </div>

              <div class="text-base text-gray-700 font-semibold">{{ formatTime(mini.starts_at) }}</div>
              <div class="text-sm text-gray-500 mt-0.5">{{ formatDate(mini.starts_at) }}</div>
              <div class="text-base text-gray-900 font-bold mt-2 line-clamp-1 cursor-pointer">{{ mini.name }}</div>

              <div class="pt-4 border-gray-100">
                <div class="flex justify-start space-x-4">
                  <!-- Cột 1: Người tạo -->
                  <div class="flex flex-col items-start pr-4 border-r">
                    <span class="text-xs text-gray-500 font-medium mb-2">Người tạo</span>
                    <div class="flex -space-x-2">
                      <img v-for="(organizer, idx) in mini.staff?.organizer" :key="'creator-' + idx"
                        :src="organizer.user.avatar_url" :alt="organizer.user.full_name"
                        class="w-8 h-8 rounded-full border-2 border-white object-cover" />
                    </div>
                  </div>

                  <!-- Cột 2: Người tham gia -->
                  <div class="flex flex-col items-start">
                    <span class="text-xs text-gray-500 font-medium mb-2">Người tham gia</span>
                    <div class="flex items-center -space-x-2">
                      <img v-for="(user, idx) in mini.all_users.slice(0, 3)" :key="'participant-' + idx"
                        :src="user.avatar_url" :alt="user.full_name"
                        class="w-8 h-8 rounded-full border-2 border-white object-cover" />
                      <div v-if="mini.all_users.length > 3"
                        class="w-8 h-8 rounded-full border-2 border-white bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                        +{{ mini.all_users.length - 3 }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Giải đấu sắp tới -->
        <section>
          <div class="flex items-center justify-start mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Giải đấu sắp tới</h2>
            <div
              class="flex items-center text-sm text-gray-600 ml-4 cursor-pointer hover:text-gray-800 bg-[#FFFFFF] p-1.5 rounded-full shadow-md">
              <ArrowUpRightIcon class="w-4 h-4 text-gray-[#838799]" />
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="(t, i) in homeData.upcoming_tournaments" :key="i"
              class="bg-white rounded-[8px] shadow hover:shadow-lg overflow-hidden transition-all p-[16px]">
              <div class="relative h-40">
                <img :src="t.poster" alt="" class="w-full h-full object-cover rounded-[4px] cursor-pointer" />
              </div>
              <div class="py-4">
                <div class="text-sm font-bold text-gray-900 mb-2 cursor-pointer">{{ t.name }}</div>
                <div class="text-xs text-[#004D99] flex items-center">
                  <MapPinIcon class="w-4 h-4 mr-1 flex-shrink-0 mt-0.5 text-[#4392E0]" />
                  <span class="line-clamp-1">{{ t.location }}</span>
                </div>
                <div class="text-xs text-[#004D99] flex items-center my-2">
                  <CalendarDaysIcon class="w-4 h-4 mr-1 flex-shrink-0 mt-0.5 text-[#4392E0]" />
                  <span class="line-clamp-1">{{ formatDate(t.start_date) }}</span>
                </div>
                <div>
                  <p class="text-sm line-clamp-2">
                    {{ t.description }}
                  </p>
                </div>
              </div>
              <div class="flex justify-end w-full">
                <button
                  class="bg-[#D72D36] text-white text-sm font-semibold px-2 py-1 rounded-[4px] hover:bg-[#D72D37] transition-colors flex items-center justify-center gap-2">
                  <ArrowUpRightIcon class="w-4 h-4" />
                  Đăng ký ngay
                </button>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Right column -->
      <div class="space-y-6">
        <!-- Favorite Features -->
        <div class="bg-white rounded-[8px] shadow p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 text-base">Tính năng ưu thích</h3>
            <button class="text-gray-600 hover:text-gray-700 rounded-full p-2 bg-[#EDEEF2] shadow-md">
              <PencilIcon class="w-3 h-3" />
            </button>
          </div>
          <div class="grid grid-cols-4 gap-4">
            <div v-for="(f, i) in features" :key="i" class="flex flex-col items-center text-center cursor-pointer">
              <div class="w-12 h-12 bg-red-100 text-red-600 flex items-center justify-center rounded-full mb-2">
                <component :is="f.icon" class="w-6 h-6" />
              </div>
              <p class="text-xs text-gray-700 font-medium">{{ f.label }}</p>
            </div>
          </div>
        </div>

        <!-- Green banner -->
        <div
          class="bg-green-400 rounded-[8px] h-[133px] flex items-center justify-center text-white font-semibold text-lg shadow">
        </div>
        <!-- Friends -->
        <div class="bg-white rounded-[8px] shadow p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 text-base">Bạn bè</h3>
            <div
              class="flex items-center text-sm text-gray-600 ml-4 cursor-pointer hover:text-gray-800 bg-[#FFFFFF] p-1.5 rounded-full shadow-md">
              <ArrowUpRightIcon class="w-4 h-4 text-gray-[#838799]" />
            </div>
          </div>
          <ul class="space-y-3">
            <li v-for="(f, i) in friendList" :key="i"
              class="flex items-center justify-between py-1 cursor-pointer hover:bg-gray-100 rounded-md px-2">
              <div class="flex items-center space-x-3">
                <div class="relative">
                  <img :src="f.avatar_url" class="w-10 h-10 rounded-full object-cover" />
                  <span v-if="f.visibility === 'open'"
                    class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></span>
                </div>
                <span class="text-sm font-medium text-gray-800">{{ f.full_name }}</span>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  UserIcon,
  UsersIcon,
  ChartBarIcon,
  QrCodeIcon,
  BellAlertIcon,
  ArrowUpRightIcon,
  PencilIcon,
} from "@heroicons/vue/24/outline";
import { MapPinIcon, CalendarDaysIcon } from "@heroicons/vue/24/solid";
import Background from "@/assets/images/dashboard-bg.svg";
import { onMounted, ref, computed } from "vue";
import * as HomeService from "@/service/home";
import * as FollowService from "@/service/follow";

const homeData = ref([]);
const friendList = ref([]);

const getHomeData = async () => {
  try {
    const response = await HomeService.getHomeData({
      mini_tournament_per_page: 3,
      tournament_per_page: 3
    });
    homeData.value = response;
  } catch (error) {
    console.error("Error fetching home data:", error);
  }
};

const getFriendLists = async () => {
  try {
    const response = await FollowService.getFriendList();
    friendList.value = response;
  } catch (error) {
    console.error("Error fetching friend list:", error);
  }
};

const getPerformanceLevel = computed(() => {
  const performance = homeData.value?.user_info?.performance || 0;

  if (performance >= 76) return 'Xuất <br/> sắc';
  if (performance >= 51) return 'Tốt';
  if (performance >= 26) return 'Trung <br/> bình';
  return 'Kém';
});

onMounted(async () => {
  await getHomeData({
    mini_tournament_per_page: 3,
    tournament_per_page: 3
  });
  await getFriendLists();
});

function formatTime(datetime) {
  const date = new Date(datetime);
  let hours = date.getHours();
  const minutes = date.getMinutes();
  const ampm = hours >= 12 ? 'PM' : 'AM';

  hours = hours % 12;
  hours = hours ? hours : 12; // 0 giờ thành 12

  const minutesStr = minutes < 10 ? '0' + minutes : minutes;

  return `${hours}:${minutesStr} ${ampm}`;
}

function formatDate(datetime) {
  const date = new Date(datetime);
  const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  const dayName = days[date.getDay()];
  const day = date.getDate();
  const month = date.getMonth() + 1;

  return `${dayName}, ${day.toString().padStart(2, '0')}/${month.toString().padStart(2, '0')}`;
}

const features = [
  { label: "CLB", icon: UsersIcon },
  { label: "Tạo trận", icon: QrCodeIcon },
  { label: "Tìm sân", icon: UserIcon },
  { label: "Xếp hạng", icon: ChartBarIcon },
];

</script>

<style scoped>
.bg-red-custom {
  background-size: cover;
  background-position: center;
}
</style>
