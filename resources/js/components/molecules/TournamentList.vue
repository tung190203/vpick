<template>
    <div class="max-w-screen-xl mx-auto mt-8">
        <div class="bg-white rounded-xl shadow p-6">
            <!-- Header gồm tiêu đề và nút xem thêm -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-base sm:text-lg md:text-xl font-bold text-gray-800">{{ title }}</h3>
                <RouterLink :to="link"
                    class="text-sm text-purple-600 hover:text-purple-800 font-medium inline-flex items-center">
                    Xem thêm
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </RouterLink>
            </div>

            <!-- Danh sách -->
            <div v-if="tournaments.length" class="space-y-3">
                <div v-for="tournament in tournaments.slice(0, 5)" :key="tournament.id"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg hover:shadow transition cursor-pointer">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900 truncate">{{ tournament.name }}</h4>
                        <p class="text-sm text-gray-500 truncate">Thời gian: {{ formatDate(tournament.date) }}</p>
                    </div>
                    <div class="text-sm text-purple-600 font-medium self-center text-left md:text-right">
                        {{ tournament.location }}
                    </div>
                </div>
            </div>

            <!-- Nếu không có -->
            <div v-else class="text-gray-500 text-center py-6">
                {{ emptyText || 'Không có giải đấu gần đây.' }}
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    tournaments: Array,
    emptyText: String,
    link: {
        type: String,
        default: ''
    }
})

function formatDate(dateStr) {
    const d = new Date(dateStr)
    return d.toLocaleDateString('vi-VN', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}
</script>
