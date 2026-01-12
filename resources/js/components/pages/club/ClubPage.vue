<template>
    <div class="min-h-screen bg-gray-50 px-4 py-8">
        <div class="max-w-6xl mx-auto bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-800">Danh sách Câu lạc bộ Pickleball</h1>
                <p class="text-sm text-gray-500">Khám phá các câu lạc bộ trên toàn quốc</p>
            </div>

            <!-- Bộ lọc -->
            <div
                class="flex flex-col md:flex-row justify-between items-center gap-4 px-6 py-4 border-b border-gray-100">
                <input type="text" v-model="search" placeholder="Tìm kiếm theo tên CLB..."
                    class="w-full md:w-1/3 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400" />

                <select v-model="selectedCity"
                    class="w-full md:w-1/4 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                    <option value="">Tất cả tỉnh thành</option>
                    <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
                </select>
            </div>

            <!-- Danh sách CLB -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                <div v-for="club in filteredClubs" :key="club.id"
                    class="relative bg-gray-50 border rounded-xl shadow hover:shadow-md transition duration-300 pb-12 cursor-pointer">
                    <div class="p-4 flex items-center space-x-4">
                        <img :src="club.logo" alt="Logo CLB" class="w-14 h-14 object-cover rounded-full border" />
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 leading-snug">
                                {{ club.name }}
                            </h2>
                            <p class="text-sm text-gray-500">{{ club.city }}</p>
                        </div>
                    </div>

                    <div class="px-4">
                        <p class="text-sm text-gray-700">{{ club.description }}</p>
                    </div>

                    <!-- Tag thành viên cố định góc dưới trái -->
                    <div class="absolute bottom-3 left-3 flex flex-wrap gap-2">
                        <span class="inline-block text-xs px-2 py-1 bg-gray-100 text-gray-800 rounded">
                            {{ club.members }} thành viên
                        </span>
                        <span class="inline-block text-xs px-2 py-1 bg-green-100 text-green-800 rounded">
                            {{ club.vndupr }} PICKI
                        </span>
                        <span class="inline-block text-xs px-2 py-1 bg-indigo-100 text-indigo-800 rounded">
                            {{ club.vnrank }} VNRANK
                        </span>
                    </div>

                </div>

            </div>

            <div class="p-4 text-center text-gray-500 text-sm">
                Tổng số CLB: {{ filteredClubs.length }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const search = ref('')
const selectedCity = ref('')

const cities = ['Hà Nội', 'TP.HCM', 'Đà Nẵng', 'Cần Thơ', 'Hải Phòng']

const clubs = ref([
    {
        id: 1,
        name: 'Pickleball Hà Nội',
        logo: 'https://i.pravatar.cc/80?img=1',
        city: 'Hà Nội',
        members: 48,
        description: 'CLB hàng đầu miền Bắc với hệ thống sân hiện đại.',
        vndupr: 3.5,
        vnrank: 1500
    },
    {
        id: 2,
        name: 'Saigon Pickle Stars',
        logo: 'https://i.pravatar.cc/80?img=2',
        city: 'TP.HCM',
        members: 60,
        description: 'Nơi hội tụ các tay vợt đam mê tại Sài Gòn.',
        vndupr: 3.5,
        vnrank: 1500
    },
    {
        id: 3,
        name: 'Đà Nẵng Smashers',
        logo: 'https://i.pravatar.cc/80?img=3',
        city: 'Đà Nẵng',
        members: 35,
        description: 'CLB năng động bên bờ biển miền Trung.',
        vndupr: 3.5,
        vnrank: 1500
    },
    {
        id: 4,
        name: 'Cần Thơ Warriors',
        logo: 'https://i.pravatar.cc/80?img=4',
        city: 'Cần Thơ',
        members: 25,
        description: 'Tổ chức thường xuyên các giải đấu khu vực.',
        vndupr: 3.5,
        vnrank: 1500
    },
    {
        id: 5,
        name: 'Hải Phòng Storm',
        logo: 'https://i.pravatar.cc/80?img=5',
        city: 'Hải Phòng',
        members: 30,
        description: 'Tạo dựng phong trào thể thao sôi nổi tại đất Cảng.',
        vndupr: 3.5,
        vnrank: 1500
    }
])

const filteredClubs = computed(() => {
    return clubs.value.filter(club => {
        const nameMatch = club.name.toLowerCase().includes(search.value.toLowerCase())
        const cityMatch = !selectedCity.value || club.city === selectedCity.value
        return nameMatch && cityMatch
    })
})
</script>