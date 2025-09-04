<template>
  <div class="min-h-screen bg-gray-100 p-4 sm:p-6">
    <!-- Swiper Header -->
    <Swiper :modules="[Autoplay]" :slides-per-view="1" :loop="true"
      :autoplay="{ delay: 2000, disableOnInteraction: false }"
      class="w-full max-w-screen-xl mx-auto mt-4 rounded-xl overflow-hidden">
      <SwiperSlide v-for="(img, index) in banners" :key="index">
        <img :src="img" class="w-full aspect-[21/9] object-cover" alt="" />
      </SwiperSlide>
    </Swiper>
    <!-- Tournament Sections -->
    <TournamentList title="Trận Sắp Tới" :tournaments="tournaments" link="" emptyText="Không có trận nào sắp tới." />
    <TournamentList title="Giải Đấu Gần Đây" :tournaments="tournaments" link="/tournaments"
      emptyText="Không có giải đấu gần đây." />

    <!-- Clubs -->
    <div class="max-w-screen-xl mx-auto mt-8">
      <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-base sm:text-lg md:text-xl font-bold text-gray-800">CLB tiêu biểu</h3>

          <RouterLink to="/giai-dau"
            class="text-sm text-purple-600 hover:text-purple-800 font-medium inline-flex items-center">
            Xem thêm
            <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </RouterLink>
        </div>

        <div v-if="clubs.length" class="space-y-4">
          <div v-for="club in clubs" :key="club.id"
            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-gray-50 rounded-lg hover:shadow transition cursor-pointer">
            <div class="flex items-center w-full sm:w-auto">
              <div class="w-12 h-12 rounded-full bg-gray-300 flex-shrink-0"></div>
              <div class="ml-4">
                <h4 class="text-sm font-semibold text-gray-900 max-w-[160px] truncate">{{ club.name }}</h4>
                <p class="text-xs text-gray-500">Số thành viên: {{ club.members }}</p>
              </div>
            </div>

            <div class="mt-2 sm:mt-0 text-xs text-left md:text-right grid grid-cols-2 gap-x-4">
              <div class="text-gray-400 uppercase">VNDUPR</div>
              <div class="text-gray-400 uppercase">VN RANK</div>
              <div class="text-black font-medium">{{ club.vndupr }}</div>
              <div class="text-black font-medium">{{ club.rank }}</div>
            </div>
          </div>
        </div>

        <div v-else class="text-gray-500 text-center py-6">
          Không có CLB tiêu biểu.
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import TournamentList from '@/components/molecules/TournamentList.vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/autoplay'
import { useUserStore } from '@/store/auth'
import { storeToRefs } from 'pinia'

const banners = [
  new URL('@/assets/images/pickleball-banner.png', import.meta.url).href,
  new URL('@/assets/images/pickleball-banner-1.png', import.meta.url).href,
  new URL('@/assets/images/pickleball-banner-2.png', import.meta.url).href,
]

const userStore = useUserStore()
const { getUser } = storeToRefs(userStore)

const tournaments = [
  { id: 1, name: 'Giải CLB ABC', date: '2025-08-01', location: 'Hà Nội' },
  { id: 2, name: 'Open 2025 - Khu vực miền Bắc', date: '2025-08-05', location: 'Bắc Ninh' },
  { id: 3, name: 'Pickleball Summer Cup', date: '2025-08-12', location: 'TP.HCM' },
  { id: 4, name: 'Friendly Match CLB D', date: '2025-08-15', location: 'Đà Nẵng' },
  { id: 5, name: 'Mini Tournament', date: '2025-08-20', location: 'Huế' },
]

const clubs = [
  {
    id: 1,
    name: "CLB ABCXYZ",
    members: 20,
    vndupr: 4.5,
    rank: "Top 200"
  },
  {
    id: 2,
    name: "CLB DEF123",
    members: 15,
    vndupr: 3.8,
    rank: "Top 300"
  },
  {
    id: 3,
    name: "CLB GHI456",
    members: 25,
    vndupr: 5.0,
    rank: "Top 100"
  }
]
</script>
