<template>
  <div class="min-h-screen bg-gray-100 p-4 lg:p-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-red-custom text-white rounded-[8px] shadow-lg p-6 relative overflow-hidden"
          :style="{ backgroundImage: `url(${Background})` }">
          <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-gradient-to-br from-red-500 to-red-700"></div>

          <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start">
              <div class="mb-6 md:mb-0">
                <div class="text-sm opacity-90 mb-1 text-[32px]">VNDUPR</div>
                <div class="text-6xl font-bold leading-none mb-4 text-[100px]">{{
                  homeData.user_info?.sports[0]?.scores.vndupr_score
                  }}
                </div>
                <div class="text-sm opacity-90 mb-1 text-[32px]">DUPR</div>
                <div class="text-5xl font-bold leading-none text-[100px]">{{
                  homeData.user_info?.sports[0]?.scores.dupr_score
                  }}</div>
              </div>

              <div class="flex flex-col items-end justify-between h-[264px]">
                <QrCodeIcon class="w-12 h-12 mb-6 text-white" />

                <div class="flex items-center space-x-8">
                  <div class="flex flex-col items-center">
                    <div class="relative w-32 h-32">
                      <svg class="w-32 h-32 transform rotate-[225deg]" viewBox="0 0 140 140">
                        <path d="M 70 10 A 60 60 0 1 1 10 70" stroke="white" stroke-width="16" fill="none"
                          opacity="0.25" />
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

                  <div class="flex flex-col items-center">
                    <div class="relative w-32 h-32">
                      <svg class="w-32 h-32 transform rotate-[225deg]" viewBox="0 0 140 140">
                        <path d="M 70 10 A 60 60 0 1 1 10 70" stroke="white" stroke-width="16" fill="none"
                          opacity="0.25" />
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

        <section>
          <div class="flex items-center justify-start mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Kèo đấu sắp tới</h2>
            <div
              class="flex items-center text-sm text-gray-600 ml-4 cursor-pointer hover:text-gray-800 bg-[#FFFFFF] p-1.5 rounded-full shadow-md">
              <ArrowUpRightIcon class="w-4 h-4 text-gray-[#838799]" />
            </div>
          </div>
          <div class="min-h-[220px] grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template v-if="!homeData?.upcoming_mini_tournament?.length">
              <div class="col-span-3 flex items-center justify-center text-gray-500 text-sm">
                Không có kèo đấu nào sắp tới
              </div>
            </template>
            <template v-else>
              <div v-for="(mini, i) in homeData.upcoming_mini_tournament" :key="i"
                class="bg-white rounded-[8px] shadow hover:shadow-lg p-4 transition-all relative cursor-pointer">
                <div class="absolute top-4 right-4 text-red-500 cursor-pointer hover:text-red-600">
                  <BellAlertIcon class="w-5 h-5" />
                </div>

                <div @click="goToMiniTournamentDetail(mini.id)">
                  <div class="text-base text-gray-700 font-semibold">{{ formatTime(mini.starts_at) }}</div>
                  <div class="text-sm text-gray-500 mt-0.5">{{ formatDate(mini.starts_at) }}</div>
                  <div class="text-base text-gray-900 font-bold mt-2 line-clamp-1 cursor-pointer">{{ mini.name }}</div>
                </div>

                <div class="pt-4 border-gray-100" @click="goToMiniTournamentDetail(mini.id)">
                  <div class="flex justify-start space-x-4">
                    <div class="flex flex-col items-start pr-4 border-r">
                      <span class="text-xs text-gray-500 font-medium mb-2">Người tạo</span>
                      <div class="flex -space-x-2">
                        <img v-for="(organizer, idx) in mini.staff?.organizer" :key="'creator-' + idx"
                          :src="organizer.user.avatar_url" :alt="organizer.user.full_name"
                          class="w-8 h-8 rounded-full border-2 border-white object-cover" />
                      </div>
                    </div>

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
            </template>
          </div>
        </section>

        <section>
          <div class="flex items-center justify-start mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Giải đấu sắp tới</h2>
            <div
              class="flex items-center text-sm text-gray-600 ml-4 cursor-pointer hover:text-gray-800 bg-[#FFFFFF] p-1.5 rounded-full shadow-md">
              <ArrowUpRightIcon class="w-4 h-4 text-gray-[#838799]" />
            </div>
          </div>
          <div class="min-h-[220px] grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template v-if="!homeData?.upcoming_tournaments?.length">
              <div class="col-span-3 flex items-center justify-center text-gray-500 text-sm">
                Không có giải đấu nào sắp tới
              </div>
            </template>

            <template v-else>
              <div v-for="(t, i) in homeData.upcoming_tournaments" :key="i"
                class="bg-white rounded-[8px] shadow hover:shadow-lg overflow-hidden transition-all p-[16px] cursor-pointer">
                <div class="relative h-40 rounded-[4px] cursor-pointer overflow-hidden"
                  @click="goToTournamentDetail(t.id)"
                  :style="!t.poster ? { backgroundColor: getRandomColor(t.id) } : {}">
                  <img v-if="t.poster" :src="t.poster" alt="" class="w-full h-full object-cover rounded-[4px]" />
                </div>
                <div class="py-4" @click="goToTournamentDetail(t.id)">
                  <div class="text-sm font-bold text-gray-900 mb-2 cursor-pointer">{{ t.name }}</div>
                  <div class="text-xs text-[#004D99] flex items-center">
                    <MapPinIcon class="w-4 h-4 mr-1 flex-shrink-0 mt-0.5 text-[#4392E0]" />
                    <span class="line-clamp-1">{{ t.competition_location?.name ?? 'Không rõ' }}</span>
                  </div>
                  <div class="text-xs text-[#004D99] flex items-center my-2">
                    <CalendarDaysIcon class="w-4 h-4 mr-1 flex-shrink-0 mt-0.5 text-[#4392E0]" />
                    <span class="line-clamp-1">{{ formatDate(t.start_date) }}</span>
                  </div>
                  <p class="text-sm line-clamp-2">
                    {{ t.description }}
                  </p>
                </div>
              </div>
            </template>
          </div>
        </section>
      </div>

      <div class="space-y-6">
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

        <div class="rounded-[8px] h-[133px] shadow relative overflow-hidden">
            <Swiper
                v-if="homeData.banners && homeData.banners.length > 0"
                :modules="modules"
                :slides-per-view="1"
                :space-between="0"
                :loop="homeData.banners.length > 1"
                :pagination="{ clickable: true }"
                :autoplay="{
                    delay: 5000,
                    disableOnInteraction: false,
                }"
                class="w-full h-[133px] rounded-[8px]"
            >
                <SwiperSlide 
                    v-for="banner in homeData.banners" 
                    :key="banner.id"
                    class="h-full"
                >
                    <a 
                        :href="banner.link" 
                        :target="banner.link ? '_blank' : '_self'" 
                        :class="{'cursor-pointer': banner.link}"
                        class="block w-full h-full"
                    >
                        <img 
                            :src="getBannerUrl(banner.image_url)" 
                            :alt="banner.title || 'Banner'"
                            class="w-full h-full object-cover" 
                        >
                    </a>
                </SwiperSlide>
            </Swiper>
            <div v-else class="w-full h-full flex items-center justify-center font-semibold text-lg bg-white text-gray-500">
                Không có banner
            </div>
        </div>
        <div class="bg-white rounded-[8px] shadow p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 text-base">Bạn bè</h3>
            <div
              class="flex items-center text-sm text-gray-600 ml-4 cursor-pointer hover:text-gray-800 bg-[#FFFFFF] p-1.5 rounded-full shadow-md">
              <ArrowUpRightIcon class="w-4 h-4 text-gray-[#838799]" />
            </div>
          </div>
          <ul v-if="friendList.length > 0" class="space-y-3">
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
          <div v-else class="text-center text-sm text-gray-500 py-3">
            Chưa có bạn bè nào
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  MapPinIcon as MapPinIconOutline,
  UserGroupIcon,
  ChartBarIcon,
  QrCodeIcon,
  PlusCircleIcon,
  BellAlertIcon,
  ArrowUpRightIcon,
  PencilIcon,
} from "@heroicons/vue/24/outline";
import { MapPinIcon, CalendarDaysIcon } from "@heroicons/vue/24/solid";

// --------------------------- SWIPER IMPORTS START ---------------------------
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Autoplay, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';

const modules = [Autoplay, Pagination];

// --------------------------- SWIPER IMPORTS END -----------------------------

import Background from "@/assets/images/dashboard-bg.svg";
import { onMounted, ref, computed } from "vue";
import * as HomeService from "@/service/home";
import * as FollowService from "@/service/follow";
import { useRouter } from 'vue-router'

const router = useRouter()

const homeData = ref({}); // Khởi tạo là object rỗng
const friendList = ref([]);

// Thay thế bằng URL cơ sở thực tế của bạn
const BASE_STORAGE_URL = 'http://localhost:8000/storage/';

const getBannerUrl = (url) => {
    // Kiểm tra nếu url là đường dẫn tuyệt đối
    if (url && (url.startsWith('http') || url.startsWith('https'))) {
        return url;
    }
    // Xử lý đường dẫn tương đối
    return url ? BASE_STORAGE_URL + url : '';
};

const getRandomColor = (seed) => {
  const colors = ['#E57373', '#64B5F6', '#81C784', '#FFD54F', '#BA68C8', '#4DD0E1'];
  return colors[seed % colors.length];
};

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
    friendList.value = response.friends;
  } catch (error) {
    console.error("Error fetching friend list:", error);
  }
};

const getPerformanceLevel = computed(() => {
  // Đảm bảo homeData.value và user_info tồn tại
  const performance = homeData.value?.user_info?.performance || 0;

  if (performance >= 76) return 'Xuất <br/> sắc';
  if (performance >= 51) return 'Tốt';
  if (performance >= 26) return 'Trung <br/> bình';
  return 'Kém';
});

onMounted(async () => {
  await getHomeData(); // Gọi hàm không cần truyền params nữa
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
  { label: "CLB", icon: UserGroupIcon },
  { label: "Tạo trận", icon: PlusCircleIcon },
  { label: "Tìm sân", icon: MapPinIconOutline },
  { label: "Xếp hạng", icon: ChartBarIcon },
];

function goToMiniTournamentDetail(id) {
  router.push({ name: 'mini-tournament-detail', params: { id } })
}

function goToTournamentDetail(id) {
  router.push({ name: 'tournament-detail', params: { id } })
}

</script>

<style scoped>
.bg-red-custom {
  background-size: cover;
  background-position: center;
}

/* Tùy chỉnh cho pagination dots của Swiper */
/* Đảm bảo style này áp dụng đúng nếu Swiper inject các dots */
.swiper-pagination-bullet-active {
    background-color: white !important; /* Ví dụ: đổi màu dots khi active */
}
</style>