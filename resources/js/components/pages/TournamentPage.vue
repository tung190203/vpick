<template>
    <div class="min-h-screen bg-gray-50 px-4 py-10">
        <!-- Title -->
        <div class="max-w-7xl mx-auto mb-8 px-2">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Danh sách Giải đấu</h1>
            <p class="text-gray-500 text-sm">Thông tin về các giải đấu Pickleball hiện tại và sắp tới</p>
        </div>

        <!-- Filter -->
        <div class="max-w-7xl mx-auto mb-6 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 px-2">
            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 whitespace-nowrap">Trạng thái:</label>
                    <select v-model="filter.status" class="px-3 py-2 h-[40px] border rounded text-sm w-full">
                        <option value="">Tất cả</option>
                        <option value="upcoming">Sắp diễn ra</option>
                        <option value="ongoing">Đang diễn ra</option>
                        <option value="finished">Đã kết thúc</option>
                    </select>
                </div>
            </div>

            <div class="w-full sm:w-auto lg:w-1/3">
                <input v-model="filter.keyword" type="text" placeholder="Tìm kiếm theo tên giải..."
                    class="w-full px-3 py-2 h-[40px] border rounded text-sm" />
            </div>
        </div>

        <!-- Tournament Grid -->
        <div class="max-w-7xl mx-auto grid gap-6 sm:grid-cols-2 lg:grid-cols-3 px-2">
            <div v-for="tournament in filteredTournaments" :key="tournament.id"
                class="relative bg-white rounded-xl shadow hover:shadow-md transition p-5 flex flex-col justify-between cursor-pointer"
                @click="() => $router.push(`/tournament/${tournament.id}`)">
                <!-- Badge đẹp hơn -->
                <div class="absolute top-2 right-2 text-[11px] font-medium px-2 py-0.5 rounded-full" :class="tournament.joined
                    ? 'bg-green-100 text-green-700'
                    : 'bg-gray-100 text-gray-500'">
                    {{ tournament.joined ? 'Đã tham gia' : 'Chưa tham gia' }}
                </div>


                <!-- Nội dung giải đấu -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">
                        {{ tournament.name }}
                    </h2>
                    <p class="text-sm text-gray-500 mb-1 flex items-center gap-1">
                        <MapPinIcon class="w-4 h-4 text-red-600" />
                        {{ tournament.location }}
                    </p>
                    <p class="text-sm text-gray-500 flex items-center gap-1">
                        <CalendarIcon class="w-4 h-4 text-blue-600" />
                        {{ tournament.date }}
                    </p>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between">
                    <span :class="statusClass(tournament.status)" class="text-xs font-semibold px-2 py-1 rounded">
                        {{ statusLabel(tournament.status) }}
                    </span>
                    <span class="text-xs text-gray-500">
                        {{ tournament.teams }} đội tham gia
                    </span>
                </div>
            </div>

        </div>

        <!-- Empty state -->
        <div v-if="filteredTournaments.length === 0" class="text-center mt-10 text-gray-500 text-sm">
            Không tìm thấy giải đấu phù hợp.
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { MapPinIcon, CalendarIcon } from '@heroicons/vue/24/solid'

const tournaments = ref([
    {
        id: 1,
        name: 'Hanoi Open 2025',
        location: 'Hà Nội',
        date: '2025-08-12',
        status: 'upcoming',
        teams: 16,
        joined: true
    },
    {
        id: 2,
        name: 'Saigon Championship',
        location: 'TP. HCM',
        date: '2025-07-20',
        status: 'ongoing',
        teams: 24,
        joined: false
    },
    {
        id: 3,
        name: 'Đà Nẵng Friendly Cup',
        location: 'Đà Nẵng',
        date: '2025-06-05',
        status: 'finished',
        teams: 12,
        joined: true
    },
    {
        id: 4,
        name: 'Hue Youth Tournament',
        location: 'Huế',
        date: '2025-09-02',
        status: 'upcoming',
        teams: 10,
        joined: false
    }
])

const filter = ref({
    status: '',
    keyword: '',
    fromDate: '',
    toDate: ''
})

const filteredTournaments = computed(() => {
    return tournaments.value.filter(tour => {
        const keywordMatch = tour.name.toLowerCase().includes(filter.value.keyword.toLowerCase())
        const statusMatch = filter.value.status ? tour.status === filter.value.status : true
        return keywordMatch && statusMatch
    })
})

const statusLabel = status => {
    switch (status) {
        case 'upcoming': return 'Sắp diễn ra'
        case 'ongoing': return 'Đang diễn ra'
        case 'finished': return 'Đã kết thúc'
        default: return ''
    }
}

const statusClass = status => {
    switch (status) {
        case 'upcoming': return 'bg-yellow-100 text-yellow-800'
        case 'ongoing': return 'bg-green-100 text-green-800'
        case 'finished': return 'bg-gray-200 text-gray-700'
        default: return ''
    }
}
</script>
