<template>
    <div class="max-w-5xl mx-auto my-8 p-6 bg-white rounded-lg shadow">
        <!-- Hồ sơ cá nhân -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center space-x-4">
                <img :src="getUser.avatar_url" alt="Avatar" class="w-16 h-16 rounded-full border" />
                <div>
                    <div class="text-xl font-semibold">{{ getUser.full_name }}</div>
                    <div class="text-xs font-bold flex jutify-start items-cente select-none text-green-500">
                        <component :is="getUser.email_verified_at ? CheckCircleIcon : XCircleIcon" class="w-4 h-4 mr-1" />
                        {{ getUser.email_verified_at ? 'Đã xác minh' : 'Chưa xác minh' }}
                    </div>
                </div>
            </div>
            <div>
                <div class="mt-4 sm:mt-0 flex items-center gap-4 text-sm text-gray-600">
                    <div><span class="font-medium">VNDUPR:</span> {{ getUser.vndupr_score }}</div> |
                    <div><span class="font-medium">Tier:</span> {{ getUser.tier ?? 'Chưa phân cấp' }}</div>
                </div>
                <div class="text-xs font-bold flex jutify-start items-cente select-none"
                    :class="verifyStatusColor(getVerify.status)">
                    <component :is="verifyStatusIcon(getVerify.status)" class="w-4 h-4 mr-1" />
                    {{ verifyStatusText(getVerify.status) }}
                </div>
            </div>
        </div>

        <hr class="my-4" />

        <!-- Nội dung có tab -->
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Tabs bên trái -->
            <div class="flex md:flex-col gap-2 md:w-1/4 text-sm overflow-x-auto md:overflow-visible whitespace-nowrap">
                <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                    class="px-4 py-2 rounded text-left shrink-0" :class="{
                        'bg-primary font-semibold text-white': activeTab === tab.key,
                        'hover:bg-primary-light hover:text-white': activeTab !== tab.key
                    }">
                    {{ tab.label }}
                </button>
            </div>

            <!-- Nội dung tab bên phải -->
            <div class="md:w-3/4">
                <div v-if="activeTab === 'stats'">
                    <h3 class="text-base font-semibold mb-2">Thống kê tổng quan</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-700 mb-4">
                        <div><span class="font-medium">Tổng trận:</span> 15</div>
                        <div><span class="font-medium">Win Rate:</span> 60%</div>
                        <div class="col-span-2">
                            <span class="font-medium">Huy hiệu:</span> 5 game thắng liên tục
                        </div>
                    </div>
                    <h3 class="text-base font-semibold mb-2">Phong độ</h3>
                    <div class="flex justify-between text-sm font-medium mb-1 px-1">
                        <span>Kém</span>
                        <span>Trung bình</span>
                        <span>Tốt</span>
                        <span>Xuất sắc</span>
                    </div>

                    <input type="range" min="1" max="4" v-model="performanceLevel" step="0.1" disabled
                        class="performance-slider w-full h-3 rounded-full appearance-none cursor-pointer" />
                    <h3 class="text-base font-semibold mt-4 mb-2">Biểu đồ điểm theo thời gian</h3>
                    <PerformanceChart />
                </div>
                <div v-else-if="activeTab === 'history'">
                    <h3 class="text-base font-semibold mb-3">Lịch sử trận đấu</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm text-gray-700">
                            <thead class="bg-gray-100 text-left">
                                <tr>
                                    <th class="px-3 py-2 border-b">Ngày Thi đấu</th>
                                    <th class="px-3 py-2 border-b">Đối thủ</th>
                                    <th class="px-3 py-2 border-b">Kết quả</th>
                                    <th class="px-3 py-2 border-b">Tỉ số</th>
                                    <th class="px-3 py-2 border-b">Giải đấu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="match in matchHistory" :key="match.id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 border-b">{{ match.date }}</td>
                                    <td class="px-3 py-2 border-b">{{ match.opponent }}</td>
                                    <td class="px-3 py-2 border-b">
                                        <span :class="match.result === 'Thắng' ? 'text-green-600' : 'text-red-600'">
                                            {{ match.result }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 border-b">{{ match.score }}</td>
                                    <td class="px-3 py-2 border-b">{{ match.tournament }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else-if="activeTab === 'clubs'" class="bg-white rounded-xl shadow p-6 space-y-6">
                    <!-- Header CLB -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <img src="@/assets/images/default-avatar.png" alt="Club Logo"
                                class="w-16 h-16 rounded-full object-cover border" />
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">CLB Pickleball Thủ Đức</h3>
                                <p class="text-sm text-gray-500">Thành viên từ 04/2024</p>
                            </div>
                        </div>
                        <button
                            class="text-sm px-4 py-2 rounded-lg bg-primary text-white hover:bg-secondary transition">Trang
                            CLB</button>
                    </div>

                    <!-- Mô tả CLB -->
                    <div>
                        <h4 class="text-md font-semibold text-gray-800 mb-1">Giới thiệu</h4>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            CLB Pickleball Thủ Đức được thành lập với mục tiêu kết nối cộng đồng yêu thích bộ môn
                            Pickleball tại khu vực TP. Thủ Đức.
                            Với hơn 150 thành viên ở nhiều độ tuổi, CLB tổ chức các buổi giao lưu, huấn luyện kỹ thuật
                            và thi đấu nội bộ hằng tuần.
                        </p>
                    </div>

                    <!-- Thông tin thêm -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                        <div>
                            <span class="font-medium text-gray-900">Địa điểm hoạt động:</span>
                            <p>Công viên Văn hóa Thủ Đức</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900">Liên hệ:</span>
                            <p>Email: clbthuduc@pickleball.vn</p>
                            <p>Hotline: 0909.123.456</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900">Thành tích nổi bật:</span>
                            <p>🏆 Vô địch TP.HCM Cup 2024 (Đôi nam nữ)</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900">Lịch sinh hoạt:</span>
                            <p>Thứ 3 - Thứ 6: 18h00 - 20h00</p>
                            <p>Chủ nhật: 7h30 - 10h00</p>
                        </div>
                    </div>

                    <!-- Badge -->
                    <div class="pt-2">
                        <span class="inline-block bg-yellow-500 text-white text-xs px-3 py-1 rounded-full mr-2">Top 5
                            CLB miền Nam</span>
                        <span class="inline-block bg-green-500 text-white text-xs px-3 py-1 rounded-full">Đang tuyển
                            thành viên</span>
                    </div>
                </div>
                <div v-else-if="activeTab === 'information'">
                    <h3 class="text-base font-semibold mb-2">Chỉnh sửa thông tin cá nhân</h3>
                    <div class="bg-white p-6 rounded-2xl max-w-4xl mx-auto">
                        <form @submit.prevent="updateProfile" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Họ và
                                        tên</label>
                                    <input v-model="player.full_name" type="text" id="full_name"
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-1 focus:ring-primary" />
                                </div>

                                <div>
                                    <label for="email"
                                        class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input v-model="player.email" type="email" disabled id="email"
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-1 focus:ring-primary cursor-not-allowed" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Avatar</label>

                                    <!-- Preview -->
                                    <div v-if="avatarPreview" class="my-4 flex justify-center">
                                        <img :src="avatarPreview" alt="Avatar Preview"
                                            class="h-24 w-24 md:h-32 md:w-32 rounded-full object-cover border border-gray-300 shadow" />
                                    </div>

                                    <!-- Drop Zone -->
                                    <div class="relative flex flex-col items-center justify-center w-full h-32 rounded-lg border-2 border-dashed border-gray-300 cursor-pointer hover:border-primary transition group bg-gray-50"
                                        @dragover.prevent="onDragOver" @drop.prevent="onDrop">
                                        <input ref="fileInput" type="file" id="avatar_file" accept="image/*"
                                            class="absolute inset-0 opacity-0 cursor-pointer"
                                            @change="handleAvatarUpload" />
                                        <div class="text-center">
                                            <svg class="mx-auto w-8 h-8 text-gray-400 group-hover:text-primary transition"
                                                fill="none" stroke="currentColor" stroke-width="1.5"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 16.5V19a2 2 0 002 2h14a2 2 0 002-2v-2.5M16 9l-4-4m0 0L8 9m4-4v12" />
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600">
                                                Kéo thả ảnh vào đây hoặc <span
                                                    class="text-primary font-medium underline">chọn ảnh</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật
                                        khẩu</label>
                                    <div class="relative">
                                        <input v-model="password" :type="showPassword ? 'text' : 'password'"
                                            id="password"
                                            class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:ring-1 focus:ring-primary" />
                                        <button type="button" @click="showPassword = !showPassword"
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                            <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                                            <EyeSlashIcon v-else class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <div class="text-right">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-2 bg-primary text-white text-sm font-medium rounded-lg shadow hover:bg-secondary transition">
                                    Lưu thông tin
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { LOCAL_STORAGE_USER } from '@/constants/index.js'
import PerformanceChart from '../molecules/PerformanceChart.vue'
import { EyeIcon, EyeSlashIcon, ClockIcon } from '@heroicons/vue/24/outline'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/solid'
import { useUserStore } from '@/store/auth'
import { useVerifyStore } from '@/store/verify'
import { storeToRefs } from 'pinia'
import { toast } from 'vue3-toastify'

const userData = localStorage.getItem(LOCAL_STORAGE_USER.USER)
const userStore = useUserStore()
const verifyStore = useVerifyStore()
const { getUser } = storeToRefs(userStore)
const { getVerify } = storeToRefs(verifyStore)
const player = userData ? JSON.parse(userData) : {};

const tabs = [
    { key: 'information', label: 'Thông tin cá nhân' },
    { key: 'stats', label: 'Thống kê' },
    { key: 'clubs', label: 'CLB' },
    { key: 'history', label: 'Lịch sử thi đấu' },
]

const password = ref('')

const avatarPreview = ref(null)
const fileInput = ref(null)
const showPassword = ref(false)

const verifyStatusColor = (status) => {
    switch (status) {
        case "pending":
            return "text-yellow-500";
        case "approved":
            return "text-green-500";
        case "rejected":
            return "text-red-500";
        default:
            return "text-gray-500";
    }
}

const verifyStatusText = (status) => {
    switch (status) {
        case "pending":
            return "Chờ xác minh điểm vndupr";
        case "approved":
            return "Đã xác minh điểm vndupr";
        case "rejected":
            return "Đã từ chối xác minh";
        default:
            return "Không xác định";
    }
}

const verifyStatusIcon = (status) => {
    switch (status) {
        case "pending":
            return ClockIcon;
        case "approved":
            return CheckCircleIcon;
        case "rejected":
            return XCircleIcon;
        default:
            return null;
    }
}

const handleAvatarUpload = (e) => {
    const file = e.target.files[0]
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader()
        reader.onload = () => {
            avatarPreview.value = reader.result
        }
        reader.readAsDataURL(file)
    }
}

const onDrop = (e) => {
    const file = e.dataTransfer.files[0]
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader()
        reader.onload = () => {
            avatarPreview.value = reader.result
        }
        reader.readAsDataURL(file)
    }
}

const onDragOver = (e) => {
    e.dataTransfer.dropEffect = 'copy'
}

const updateProfile = async () => {
    const formData = new FormData()
    formData.append('full_name', player.full_name)
    if (password.value && password.value.trim() !== '') {
        formData.append('password', password.value)
    }
    const file = fileInput.value?.files[0]
    if (file) {
        formData.append('avatar_url', file)
    }
    try {
        await userStore.updateUser(formData)
        const data = localStorage.getItem(LOCAL_STORAGE_USER.USER)
        Object.assign(player, JSON.parse(data))
        toast.success('Cập nhật thông tin thành công!')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Cập nhật thông tin thất bại!')
    }
}

if (userData && player.avatar_url) {
    avatarPreview.value = player.avatar_url
} else {
    avatarPreview.value = new URL('@/assets/images/default-avatar.png', import.meta.url).href
}

const matchHistory = [
    {
        id: 1,
        date: '2025-07-20',
        opponent: 'Nguyễn B',
        result: 'Thắng',
        score: '11:7, 11:9',
        tournament: 'Giải Giao hữu Tháng 7'
    },
    {
        id: 2,
        date: '2025-07-15',
        opponent: 'Trần C',
        result: 'Thua',
        score: '8:11, 9:11',
        tournament: 'Giải Mở rộng TP.HCM'
    },
    {
        id: 3,
        date: '2025-07-10',
        opponent: 'Lê D',
        result: 'Thắng',
        score: '11:6, 11:5',
        tournament: 'Giải CLB nội bộ'
    }
]

const performanceLevel = ref(3.5)

const activeTab = ref('information')
</script>

<style scoped>
.performance-slider {
    background: linear-gradient(to right, #ef4444, #facc15, #22c55e, #10b981);
    outline: none;
}

.performance-slider::-webkit-slider-thumb {
    appearance: none;
    width: 20px;
    height: 20px;
    background-color: #b91c1c;
    border-radius: 9999px;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #b91c1c;
    transition: all 0.2s ease-in-out;
}

.performance-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    background-color: #b91c1c;
    border-radius: 9999px;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #b91c1c;
    transition: all 0.2s ease-in-out;
}
</style>