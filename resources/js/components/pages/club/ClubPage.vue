<template>
    <div class="min-h-screen bg-gray-50 p-4">
        <div class="max-w-8xl mx-auto bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-800">Danh sách Câu lạc bộ Pickleball</h1>
                <p class="text-sm text-gray-500">Khám phá các câu lạc bộ trên toàn quốc</p>
            </div>

            <!-- Bộ lọc -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 px-6 py-4">
                <input type="text" v-model="search" placeholder="Tìm kiếm theo tên, địa chỉ CLB..."
                    class="w-full md:w-1/3 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400" />
            </div>

            <!-- Danh sách CLB -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 p-6 min-h-[450px] content-start">
                <div v-if="loading" class="col-span-full flex justify-center items-center py-20">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-500"></div>
                </div>
                <template v-else>
                    <div v-for="club in clubs" :key="club.id"
                        class="relative bg-gray-50 border rounded-xl shadow hover:shadow-md transition duration-300 pb-12 cursor-pointer h-full flex flex-col" @click="handleClubClick(club)">
                        <div class="p-4 flex items-start space-x-4">
                            <div class="relative shrink-0">
                                <img :src="club.logo_url || 'https://picki.vn/images/default-avatar.png'" alt="Logo CLB"
                                    class="w-14 h-14 object-cover rounded-full border" />
                                <div v-if="club.is_verified" 
                                    class="absolute bottom-0 right-0 flex items-center justify-center bg-white text-[#4391E0] rounded-full border border-white shadow-sm ring-1 ring-gray-100">
                                    <VerifyIcon class="w-4 h-4" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h2 class="text-lg font-semibold text-gray-800 leading-snug truncate" v-tooltip="club.name">
                                    {{ club.name }}
                                </h2>
                                <div class="flex items-start gap-1 mt-1" v-if="club.address">
                                    <MapPinIcon class="w-4 h-4 text-[#D72D36] shrink-0 mt-0.5" />
                                    <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed" v-tooltip="club.address">{{ club.address }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="px-4 mb-4 flex-1">
                            <p class="text-xs text-gray-700 line-clamp-3 leading-relaxed">{{ club.profile?.description || 'Không có mô tả' }}</p>
                        </div>

                        <!-- Tag thành viên cố định góc dưới trái -->
                        <div class="absolute bottom-3 left-3 right-3 flex flex-wrap gap-2">
                            <span class="inline-block text-xs px-2 py-1 bg-gray-100 text-gray-800 rounded">
                                {{ club.quantity_members }} thành viên
                            </span>
                            <span class="inline-block text-xs px-2 py-1 bg-green-100 text-green-800 rounded">
                                {{ club.skill_level?.min || 0 }} - {{ club.skill_level?.max || 0 }} PICKI
                            </span>
                            <span class="inline-block text-xs px-2 py-1 bg-indigo-100 text-indigo-800 rounded" v-if="club.vnrank">
                                {{ club.vnrank }} VNRANK
                            </span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Phân trang -->
            <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-500">
                    Hiển thị {{ clubs.length }} trên tổng số {{ totalClubs }} CLB
                </div>
                <div class="flex items-center gap-2" v-if="lastPage > 1">
                    <button 
                        @click="page--" 
                        :disabled="page <= 1 || loading"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Trước
                    </button>
                    <div class="flex items-center gap-1">
                        <span class="px-3 py-2 text-sm font-medium text-gray-700">
                            Trang {{ page }} / {{ lastPage }}
                        </span>
                    </div>
                    <button 
                        @click="page++" 
                        :disabled="page >= lastPage || loading"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Sau
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import * as ClubService from '@/service/club.js'
import { MapPinIcon } from '@heroicons/vue/24/solid'
import VerifyIcon from "@/assets/images/verify-icon.svg";
import debounce from 'lodash.debounce'
import { useRouter } from 'vue-router'

const router = useRouter()
const search = ref('')
const clubs = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = ref(9)
const totalClubs = ref(0)
const lastPage = ref(1)

const getAllClubs = async () => {
    loading.value = true
    try {
        const response = await ClubService.getAllClubs({
            name: search.value,
            page: page.value,
            perPage: perPage.value
        })
        clubs.value = response.data?.clubs || []
        totalClubs.value = response.meta?.total || 0
        lastPage.value = response.meta?.last_page || 1
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
    }
}

// Debounce tìm kiếm
const debouncedSearch = debounce(() => {
    page.value = 1
    getAllClubs()
}, 500)

watch(search, () => {
    debouncedSearch()
})

watch(page, () => {
    getAllClubs()
    // Scroll lên đầu trang khi chuyển trang
    window.scrollTo({ top: 0, behavior: 'smooth' })
})

const handleClubClick = (club) => {
    router.push({ name: 'club-detail', params: { id: club.id } })
}

onMounted(async () => {
    await getAllClubs()
})
</script>