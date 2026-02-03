<template>
  <div class="p-4 md:p-6 min-h-screen bg-[#F8F9FA]">
    <!-- Header -->
    <div class="flex items-center mb-6">
      <button @click="goBack" class="p-2 hover:bg-gray-100 rounded-full transition-colors mr-2">
        <ArrowLeftIcon class="w-6 h-6 text-[#3E414C]" />
      </button>
      <h1 class="text-xl font-semibold text-[#3E414C]">Chi tiết sự kiện</h1>
    </div>

    <!-- Page Title & Actions -->
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-8">
      <div class="flex-1">
        <div class="flex items-center gap-3 mb-2">
          <h2 class="text-2xl font-semibold text-[#3E414C]">{{ activity.title || 'Đang tải...' }}</h2>
          <span class="px-3 py-1 bg-[#F2F7FC] text-sm font-semibold rounded-[4px]" :class="activity.is_private ? 'text-[#4392E0]' : 'text-[#00B377]'">
            {{ activity.is_private ? 'Private' : 'Public' }}
          </span>
        </div>
        <p class="text-[#838799]">{{ activity.summary }}</p>
      </div>
      <div class="flex items-center gap-3">
        <Button 
          size="lg" 
          color="danger" 
          class="px-4 py-3 rounded-[4px] font-semibold shadow-lg shadow-red-100"
          @click="registerEvent"
        >
          <div class="flex items-center gap-2">
            <RegistrationIcon class="w-5 h-5" />
            Đăng ký ngay
          </div>
        </Button>
        <Button 
          size="lg" 
          color="white" 
          class="px-4 py-3 rounded-[4px] font-semibold border border-[#DCDEE6] bg-[#EDEEF2] text-[#3E414C] shadow-sm transition-all hover:bg-gray-50"
          @click="shareEvent"
        >
          <div class="flex items-center gap-2">
            <ShareIcon class="w-5 h-5" />
            Chia sẻ
          </div>
        </Button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      <!-- Left Column (Main Info) -->
      <div class="lg:col-span-8 space-y-6">
        <!-- Event Quick Info Cards -->
        <div class="bg-white rounded-[16px] p-6 shadow-sm border border-gray-50">
          <div class="flex flex-wrap gap-4 bg-[#FBEAEB] rounded-lg p-4">
            <!-- Time Card -->
            <div class="flex-1 min-w-[200px] flex items-center gap-4">
              <div class="w-12 h-12 bg-white rounded-lg shadow-sm flex items-center justify-center text-[#D72D36]">
                <ClockIcon class="w-6 h-6" />
              </div>
              <div>
                <div class="font-semibold text-[#3E414C]">{{ formatTimeRange(activity.start_time, activity.end_time) }}</div>
                <div class="text-sm text-[#838799] font-medium">{{ formatDate(activity.start_time) }}</div>
              </div>
            </div>

            <!-- Location Card -->
            <div class="flex-1 min-w-[200px] flex items-center gap-4">
              <div class="w-12 h-12 bg-white rounded-lg shadow-sm flex items-center justify-center text-[#D72D36]">
                <MapPinIcon class="w-6 h-6" />
              </div>
              <div>
                <div class="font-semibold text-[#3E414C] truncate max-w-[150px]">{{ activity.location || 'Sân Pickleball' }}</div>
                <div class="text-sm text-[#838799] font-medium truncate max-w-[150px]">{{ activity.address || 'Quận 7, TP.HCM' }}</div>
              </div>
            </div>

            <!-- Frequency Card -->
            <div class="flex-1 min-w-[200px] flex items-center gap-4">
              <div class="w-12 h-12 bg-white rounded-lg shadow-sm flex items-center justify-center text-[#D72D36]">
                <ArrowPathRoundedSquareIcon class="w-6 h-6" />
              </div>
              <div class="font-semibold text-[#3E414C]">Lặp lại hàng tuần</div>
            </div>
          </div>

          <!-- Secondary Detailed Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <!-- Cost Card -->
            <div class="border border-gray-100 rounded-2xl p-5 hover:border-[#D72D36]/20 transition-colors">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 bg-[#D72D36] rounded-full p-2 flex items-center justify-center text-white">
                  <PriceCheckIcon class="w-5 h-5" />
                </div>
                <h4 class="font-semibold text-[#3E414C] text-xl">Chia tiền</h4>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between items-center text-sm">
                  <span class="text-[#3E414C]">Cơ chế chia tiền</span>
                  <span class="font-semibold text-[#D72D36]">Chia đều</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="text-[#3E414C]">Thu khách vãng lai</span>
                  <span class="font-semibold text-[#D72D36]">{{ formatCurrency(activity.guest_fee || 20000) }}/người</span>
                </div>
              </div>
            </div>

            <!-- Regulation Card -->
            <div class="border border-gray-100 rounded-2xl p-5 hover:border-[#D72D36]/20 transition-colors">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 bg-[#D72D36] rounded-full p-2 flex items-center justify-center text-white">
                  <RuleIcon class="w-5 h-5" />
                </div>
                <h4 class="font-semibold text-[#3E414C] text-xl">Quy định</h4>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between items-center text-sm">
                  <span class="text-[#3E414C]">Hạn chót hủy kèo</span>
                  <span class="font-semibold text-[#D72D36]">Trước 4 Tiếng</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="text-[#3E414C]">Phạt hủy muộn</span>
                  <span class="font-semibold text-[#D72D36]">{{ formatCurrency(20000) }}/người</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Description Section -->
          <div class="mt-8">
            <h4 class="font-semibold text-[#838799] uppercase tracking-wider mb-4">Mô tả chi tiết</h4>
            <div class="text-[#3E414C] leading-relaxed whitespace-pre-line">
              {{ activity.description || 'Sự kiện sinh hoạt cố định hàng tuần cho thành viên CLB Pickleball Sài Gòn Phố. Chào mừng tất cả mọi người tham gia giao lưu, không phân biệt trình độ. Vui lòng mang theo vợt cá nhân và giày thể thao phù hợp.' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column (Sidebar) -->
      <div class="lg:col-span-4 space-y-6">
        <!-- Participants Card -->
        <div class="bg-white rounded-[24px] shadow-sm border border-gray-50 overflow-hidden">
          <div class="p-6 pb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-[#D72D36] rounded-full p-2 flex items-center justify-center text-white">
                <UsersIcon class="w-5 h-5" />
              </div>
              <h3 class="font-semibold text-[#3E414C]">Thành viên tham gia</h3>
              <span class="w-1 h-1 rounded-full bg-[#3E414C]"></span>
              <span class="px-2 py-0.5 bg-gray-100 text-[#838799] text-xs font-bold rounded-full">
                {{ participants.length }}/{{ activity.max_participants || 12 }}
              </span>
            </div>
            <button @click="isParticipantsExpanded = !isParticipantsExpanded" class="p-1 hover:bg-gray-100 rounded-full transition-transform duration-200" :class="{ 'rotate-180': !isParticipantsExpanded }">
              <ChevronDownIcon class="w-5 h-5 text-gray-400" />
            </button>
          </div>
          
          <div v-show="isParticipantsExpanded" class="px-6 pb-6">
            <h4 class="font-bold text-[#8E95A2] text-[13px] uppercase tracking-[0.2em] mb-6">NGƯỜI TẠO</h4>
            <!-- Creator -->
            <div class="flex items-center gap-5 mb-10">
               <div class="relative">
                  <img :src="creator.avatar || defaultAvatar" class="w-14 h-14 rounded-full border-[2px] border-[#4392E0] shadow-sm object-cover" />
                  <div class="absolute -bottom-0.5 -right-0.5 p-1 bg-[#4392E0] rounded-full ring-2 ring-white">
                    <ShieldCheckIcon class="w-3 h-3 text-white" />
                  </div>
               </div>
               <div class="flex-1">
                 <div class="flex items-center gap-2 mb-1">
                    <span class="font-bold text-[#3E414C] text-[17px]">{{ creator.name }}</span>
                    <span class="px-2 py-0.5 bg-[#4392E0] text-white text-[11px] font-bold rounded-md uppercase">Admin</span>
                 </div>
                 <div class="text-[14px] text-[#8E95A2] font-medium leading-tight">
                    {{ creator.level }} PICKI <span class="mx-1">•</span> Chủ sân
                 </div>
               </div>
            </div>

            <h4 class="font-bold text-[#8E95A2] text-[13px] uppercase tracking-[0.2em] mb-4">THÀNH VIÊN</h4>
            <div class="space-y-0">
              <div v-for="(user, index) in participants" :key="user.id" 
                class="flex items-center gap-5 py-5 group cursor-pointer" @click="goToProfile(user.id)"
                :class="{ 'border-t border-[#F0F2F5]': index !== 0 }">
                <div class="relative">
                  <img :src="user.avatar || defaultAvatar" class="w-14 h-14 rounded-full bg-[#F2F4F7] object-cover" />
                  <div class="absolute bottom-0 right-0 w-4.5 h-4.5 p-1.5 bg-[#00B377] ring-4 ring-white rounded-full"></div>
                  <div class="absolute -left-1 -bottom-0.5 w-5 h-5 bg-[#4392E0] text-white text-[8px] font-semibold flex items-center justify-center rounded-full ring-2 ring-white">
                    {{ user.level }}
                  </div>
                </div>
                <div class="flex-1">
                  <div class="font-bold text-[#3E414C] text-[17px] mb-1">{{ user.name }}</div>
                  <div class="text-[14px] text-[#A4B0C1] font-medium">{{ user.joined_at || 'Tham gia 3 ngày trước' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- QR Code Card -->
        <div class="bg-white rounded-[24px] shadow-sm border border-gray-50 overflow-hidden">
           <div class="p-6 pb-2 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-[#D72D36] rounded-full p-2 flex items-center justify-center text-white">
                <QrcodeIcon class="w-5 h-5" />
              </div>
              <h3 class="font-semibold text-[#3E414C]">Mã QR Check-in</h3>
            </div>
            <button @click="isQRExpanded = !isQRExpanded" class="p-1 hover:bg-gray-100 rounded-full transition-transform duration-200" :class="{ 'rotate-180': !isQRExpanded }">
              <ChevronDownIcon class="w-5 h-5 text-gray-400" />
            </button>
          </div>
          <div v-show="isQRExpanded" class="p-8 flex flex-col items-center">
            <div class="p-4 mb-6">
              <qrcode-vue :value="qrValue" :size="200" level="H" class="rounded-xl" />
            </div>
            <Button 
                size="md" 
                color="white" 
                class="w-full bg-[#FFF5F5] text-[#D72D36] border-none font-bold py-3 hover:bg-[#FFEBEB]"
                @click="downloadQR"
            >
              <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
              Tải xuống mã QR
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { 
    ArrowLeftIcon, 
    ShareIcon, 
    ClockIcon, 
    MapPinIcon, 
    UsersIcon,
    ChevronDownIcon,
    ArrowDownTrayIcon,
    ArrowPathRoundedSquareIcon
} from '@heroicons/vue/24/outline'
import RegistrationIcon from '@/assets/images/registration.svg'
import PriceCheckIcon from '@/assets/images/price_check.svg'
import RuleIcon from '@/assets/images/rule.svg'
import ShieldCheckIcon from "@/assets/images/shield_check.svg";
import QrcodeIcon from '@/assets/images/qr_code.svg'
import Button from '@/components/atoms/Button.vue'
import QrcodeVue from 'qrcode.vue'
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import * as ClubService from '@/service/club.js'
import { toast } from 'vue3-toastify'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/vi'
dayjs.extend(relativeTime)
dayjs.locale('vi')
const defaultAvatar = "/images/default-avatar.png";

const router = useRouter()
const route = useRoute()
const clubId = route.params.id
const activityId = computed(() => route.query.activityId)

const activity = ref({})
const participants = ref([])
const creator = ref({})

const isParticipantsExpanded = ref(true)
const isQRExpanded = ref(true)

const qrValue = computed(() => {
    return JSON.stringify({
        type: 'activity_checkin',
        activity_id: activity.value.id,
        club_id: clubId
    })
})

const getActivityDetail = async () => {
    try {
        const response = await ClubService.getClubActivityDetail(clubId, activityId.value)
        const data = response.data
        if (data) {
            activity.value = {
                ...data,
                summary: data.description || '',
                is_private: data.type === 'private',
                guest_fee: data.fee_amount || 0
            }
            
            if (data.participants) {
                participants.value = data.participants.map(p => ({
                    id: p.id,
                    name: p.user?.full_name || 'Thành viên',
                    avatar: p.user?.avatar_url || p.user?.thumbnail,
                    level: p.user?.vn_rank || 'N/A',
                    joined_at: p.created_at ? dayjs(p.created_at).fromNow() : 'Vừa tham gia'
                }))
            }

            if (data.creator) {
                creator.value = {
                    name: data.creator.full_name,
                    level: data.creator.vn_rank || 'N/A',
                    avatar: data.creator.avatar_url || data.creator.thumbnail
                }
            }
        }
    } catch (error) {
        console.error(error)
        toast.error('Không thể lấy thông tin chi tiết sự kiện')
    }
}

const goBack = () => {
    router.back()
}

const registerEvent = () => {
    toast.info('Tính năng đăng ký đang được xử lý')
}

const shareEvent = () => {
    toast.info('Đã sao chép liên kết sự kiện')
}

const downloadQR = () => {
  toast.success('Đang chuẩn bị tải xuống mã QR')
}

const formatDate = (date) => {
    if (!date) return ''
    const d = dayjs(date)
    const days = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7']
    return `${days[d.day()]}, ${d.format('DD/MM/YYYY')}`
}

const formatTimeRange = (start, end) => {
    if (!start || !end) return '18:00 - 20:00'
    return `${dayjs(start).format('HH:mm')} - ${dayjs(end).format('HH:mm')}`
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value).replace('₫', '')
}

const goToProfile = (id) => {
    router.push({ name: 'profile', params: { id } });
};

onMounted(async () => {
    if (clubId && activityId.value) {
        await getActivityDetail()
    }
})
</script>
