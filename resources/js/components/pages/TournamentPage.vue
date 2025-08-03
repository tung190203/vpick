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
                        <option v-for="(status, key) in TOURNAMENT_STATUS" :key="key" :value="status">
                            {{ statusLabel(status) }}
                        </option>
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
            <div v-for="tournament in tournaments" :key="tournament.id"
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
                        {{ formatDatetime(tournament.start_date) }} - {{ formatDatetime(tournament.end_date) }}
                    </p>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between">
                    <span :class="statusClass(tournament.status)" class="text-xs font-semibold px-2 py-1 rounded">
                        {{ statusLabel(tournament.status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="tournaments.length === 0" class="text-center mt-10 text-gray-500 text-sm">
            Không tìm thấy giải đấu phù hợp.
        </div>
        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="mt-10 flex justify-center items-center gap-2">
            <!-- Previous Button -->
            <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                class="w-10 h-10 flex items-center justify-center rounded-md border border-gray-300 text-gray-600 bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                <ChevronLeftIcon class="w-5 h-5" />
            </button>

            <!-- Page Numbers -->
            <template v-for="page in visiblePages" :key="page">
                <button v-if="page !== '...'" @click="changePage(page)" :class="[
                    'w-10 h-10 flex items-center justify-center rounded-md border text-sm font-medium transition',
                    page === pagination.current_page
                        ? 'bg-primary text-white border-primary'
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                ]">
                    {{ page }}
                </button>
                <span v-else class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>
            </template>

            <!-- Next Button -->
            <button @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="w-10 h-10 flex items-center justify-center rounded-md border border-gray-300 text-gray-600 bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                <ChevronRightIcon class="w-5 h-5" />
            </button>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { MapPinIcon, CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/solid'
import * as TournamentService from '@/service/tournament'
import { formatDatetime } from '@/composables/formatDatetime'
import { TOURNAMENT_STATUS, TOURNAMENT_STATUS_LABEL } from '@/constants/index.js'

// const tournaments = ref([
//     {
//         id: 1,
//         name: 'Hanoi Open 2025',
//         location: 'Hà Nội',
//         date: '2025-08-12',
//         status: 'upcoming',
//         teams: 16,
//         joined: true
//     },
// ])
const tournaments = ref([])

const filter = ref({
    status: '',
    keyword: '',
    fromDate: '',
    toDate: ''
})

watch(filter, () => {
  getAllTournaments(1)
}, { deep: true })


const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10
})
const visiblePages = computed(() => {
    const total = pagination.value.last_page
    const current = pagination.value.current_page
    const delta = 2 // số trang hai bên

    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1)
    }

    const pages = []
    const left = Math.max(2, current - delta)
    const right = Math.min(total - 1, current + delta)

    pages.push(1)

    if (left > 2) {
        pages.push('...')
    }

    for (let i = left; i <= right; i++) {
        pages.push(i)
    }

    if (right < total - 1) {
        pages.push('...')
    }

    pages.push(total)

    return pages
})

const statusLabel = status => {
    switch (status) {
        case TOURNAMENT_STATUS.UPCOMING: return TOURNAMENT_STATUS_LABEL.UPCOMING
        case TOURNAMENT_STATUS.ONGOING: return TOURNAMENT_STATUS_LABEL.ONGOING
        case TOURNAMENT_STATUS.FINISHED: return TOURNAMENT_STATUS_LABEL.FINISHED
        default: return ''
    }
}

const statusClass = status => {
    switch (status) {
        case TOURNAMENT_STATUS.UPCOMING: return 'bg-yellow-100 text-yellow-800'
        case TOURNAMENT_STATUS.ONGOING: return 'bg-green-100 text-green-800'
        case TOURNAMENT_STATUS.FINISHED: return 'bg-gray-200 text-gray-700'
        default: return ''
    }
}

// const getAllTournaments = async (page = 1) => {
//     try {
//         const res = await TournamentService.getTournaments({ page, ...filter.value })
//         if (res && res.data) {
//             tournaments.value = res.data
//             pagination.value = res.meta
//         } else {
//             console.error('No tournaments data found')
//         }
//     } catch (error) {
//         console.error('Error fetching tournaments:', error)
//     }
// }

const getAllTournaments = async (page = 1) => {
  try {
    const params = {
      page,
      keyword: filter.value.keyword || '',
      status: filter.value.status || '',
    }

    const res = await TournamentService.getTournaments(params)

    if (res && res.data) {
      tournaments.value = res.data
      pagination.value = res.meta
    } else {
      console.error('No tournaments data found')
    }
  } catch (error) {
    console.error('Error fetching tournaments:', error)
  }
}


const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        pagination.value.current_page = page
        getAllTournaments(page)
    }
}

onMounted(async () => {
    await getAllTournaments()
})
</script>
