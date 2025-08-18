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

    <!-- User Info + Actions -->
    <div class="max-w-screen-xl mx-auto mt-8 bg-white rounded-xl shadow p-4 sm:p-6 space-y-4">
      <div
        class="text-lg sm:text-xl font-semibold flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-1 sm:space-y-0">
        <div class="flex items-center max-w-full truncate">
          <img :src="getUser.avatar_url" class="w-8 h-8 rounded-full mr-2" alt="" />
          <span class="truncate">{{ getUser.full_name }}</span>
        </div>
        <div class="text-sm text-gray-600 truncate">🏅 VNDUPR: 4.50 – <span class="font-medium">S3</span></div>
      </div>

      <!-- Action Buttons -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <button class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-center"
          @click="() => $router.push('/tournament')">
          Tham gia giải đấu
        </button>
        <button class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg text-center" @click="() => $router.push('friendly-match/create')">
          Nhập điểm / Tạo giao hữu
        </button>
        <button class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg text-center"
          @click="() => $router.push('/leaderboard')">
          Xem bảng xếp hạng
        </button>
        <button class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg text-center">
          Quét mã QR
        </button>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="max-w-screen-xl mx-auto mt-8 grid grid-cols-1 md:grid-cols-5 gap-4">
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-3xl font-bold text-green-600">80</h2>
        <p class="text-gray-700">Số trận thắng</p>
      </div>
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-3xl font-bold text-red-600">20</h2>
        <p class="text-gray-700">Số trận thua</p>
      </div>
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-3xl font-bold text-blue-600">100</h2>
        <p class="text-gray-700">Tổng số trận</p>
      </div>
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-3xl font-bold">3</h2>
        <p class="text-gray-700">Giải đấu đã tạo</p>
      </div>
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-3xl font-bold">3</h2>
        <p class="text-gray-700">Người chơi</p>
      </div>
    </div>

    <!-- Tournament Sections -->
    <TournamentList title="Trận Sắp Tới" :tournaments="tournaments" link="" emptyText="Không có trận nào sắp tới." />
    <TournamentList title="Giải Đấu Gần Đây" :tournaments="tournaments" link="/tournament"
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
  <BeginnerPopup :show="showSkillPopup" :onClose="() => (showSkillPopup = false)" :onConfirm="handleSkillConfirm" />
</template>


<script setup>
import TournamentList from '@/components/molecules/TournamentList.vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/autoplay'
import { onMounted, ref } from 'vue'
import BeginnerPopup from '@/components/molecules/BeginnerPopup.vue'
import { useUserStore } from '@/store/auth'
import { useVerifyStore } from '@/store/verify'
import { storeToRefs } from 'pinia'
import { toast } from 'vue3-toastify'
import { LOCAL_STORAGE_KEY } from "@/constants/index.js";

const banners = [
  new URL('@/assets/images/pickleball-banner.png', import.meta.url).href,
  new URL('@/assets/images/pickleball-banner-1.png', import.meta.url).href,
  new URL('@/assets/images/pickleball-banner-2.png', import.meta.url).href,
]


const showSkillPopup = ref(true)
const userStore = useUserStore()
const verifyStore = useVerifyStore()
const { getUser } = storeToRefs(userStore)

function checkBeginner() {
  const verified = localStorage.getItem(LOCAL_STORAGE_KEY.VERIFY);
  if (verified) {
    showSkillPopup.value = false;
  } else {
    showSkillPopup.value = true;
  }
}

onMounted(async() => {
  await checkBeginner()
})

const handleSkillConfirm = async (data) => {
  try {
    const { vndupr_score } = data.level
    const certified_file = data.certified_file || null
    const verifier_id = data.verifier_id || null

    const formData = new FormData()
    formData.append('user_id', getUser.value.id)
    formData.append('vndupr_score', vndupr_score)

    if (verifier_id) {
      formData.append('verifier_id', verifier_id)
    }

    if (certified_file) {
      formData.append('certified_file', certified_file)
    }

    await verifyStore.createVerification(formData)
    showSkillPopup.value = false
    toast.success('Đã xác nhận trình độ thành công!')
  } catch (error) {
    console.error('Error updating skill level:', error)
  }
}

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
