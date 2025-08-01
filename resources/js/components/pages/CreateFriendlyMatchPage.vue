<template>
    <div class="p-4 max-w-5xl mx-auto">
        <h2 class="text-xl font-bold text-center mb-4">Tạo trận đấu</h2>

        <!-- Tabs -->
        <div class="flex justify-center mb-4">
            <button class="px-4 py-2 border rounded-l-lg"
                :class="matchType === 'single' ? 'bg-primary text-white' : 'bg-white'" @click="setMatchType('single')">
                Đấu đơn
            </button>
            <button class="px-4 py-2 border-t border-b border-r rounded-r-lg"
                :class="matchType === 'double' ? 'bg-primary text-white' : 'bg-white'" @click="setMatchType('double')">
                Đấu đôi
            </button>
        </div>

        <TeamBlock title="Team A" :team="teamA" keyPrefix="teamA"
            :onPlayerSelect="(index) => openPlayerPopup('A', index)" />

        <!-- Swap / Random -->
        <div class="flex justify-center gap-4 my-3">
            <button class="text-sm border px-3 py-2 rounded flex items-center gap-1" @click="swapTeams">
                <ArrowsUpDownIcon class="w-4 h-4"/> Đổi vị trí
            </button>
            <button class="text-sm border px-3 py-2 rounded flex items-center gap-1" @click="randomizeTeams">
                <ArrowPathIcon class="w-4 h-4"/> Tìm ngẫu nhiên
            </button>
        </div>

        <!-- Team B -->
        <TeamBlock title="Team B" :team="teamB" keyPrefix="teamB"
            :onPlayerSelect="(index) => openPlayerPopup('B', index)" />

        <p class="text-xs italic text-center text-gray-500 mb-4">
            Bỏ trống vị trí nếu bạn muốn tạo trận đấu kiểu <span class="font-semibold">Xé vé</span>
        </p>

        <!-- Settings -->
        <div class="bg-white border rounded-xl p-4 space-y-4 shadow-sm text-sm">
            <div class="flex justify-between items-center">
                <label for="sets" class="text-gray-600 font-medium">Số SET đấu</label>
                <select id="sets" v-model="settings.sets" class="px-3 py-1 focus:outline-none text-right">
                    <option value="1">1 SET</option>
                    <option value="3">3 SET</option>
                    <option value="5">5 SET</option>
                </select>
            </div>

            <div class="flex justify-between items-center">
                <label for="point" class="text-gray-600 font-medium">Điểm kết thúc</label>
                <select id="point" v-model="settings.point" class="px-3 py-1 focus:outline-none text-right">
                    <option value="11">11 Điểm</option>
                    <option value="15">15 Điểm</option>
                    <option value="21">21 Điểm</option>
                </select>
            </div>

            <div class="flex justify-between items-center">
                <label for="win_rule" class="text-gray-600 font-medium">Quy tắc thắng</label>
                <select id="win_rule" v-model="settings.win_rule" class="px-3 py-1 focus:outline-none text-right">
                    <option value="2">Cách biệt 2 điểm</option>
                    <option value="1">Thắng trước</option>
                </select>
            </div>

            <div class="flex justify-between items-center">
                <label for="vndupr" class="text-gray-600 font-medium">Có tính VNDUPR</label>
                <select id="vndupr" v-model="settings.vndupr" class="px-3 py-1 focus:outline-none text-right">
                    <option value="yes">Có</option>
                    <option value="no">Không</option>
                </select>
            </div>

            <div class="flex justify-between items-center">
                <label for="visibility" class="text-gray-600 font-medium">Cho phép tìm kiếm</label>
                <select id="visibility" v-model="settings.visibility" class="px-3 py-1 focus:outline-none text-right">
                    <option value="private">Trận riêng tư</option>
                    <option value="public">Ai cũng tìm được</option>
                </select>
            </div>
        </div>

        <div class="text-center mt-6">
            <button class="bg-primary text-white px-6 py-2 rounded">Tạo trận đấu</button>
        </div>

        <!-- Popup chọn người chơi -->
        <PlayerSelectPopup
      :show="showPopup"
      :players="mockPlayers"
      :selectedPlayers="[...teamA, ...teamB].map(p => p.name)"
      :onClose="() => (showPopup = false)"
      :onSelect="selectPlayer"
    />
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { ArrowPathIcon, ArrowsUpDownIcon } from '@heroicons/vue/24/outline'
import TeamBlock from '@/components/molecules/TeamBlock.vue'
import PlayerSelectPopup from '@/components/molecules/PlayerSelectPopup.vue'

const matchType = ref('double')
const teamA = ref([])
const teamB = ref([])

const mockPlayers = [
    { name: 'Nguyễn Văn A', vndupr: 1.5, avatar: 'https://i.pravatar.cc/150?u=A' },
    { name: 'Trần Thị B', vndupr: 3.5, avatar: 'https://i.pravatar.cc/150?u=B' },
    { name: 'Lê Văn C', vndupr: 4, avatar: 'https://i.pravatar.cc/150?u=C' },
    { name: 'Phạm Thị D', vndupr: 2, avatar: 'https://i.pravatar.cc/150?u=D' },
    { name: 'Đỗ Văn E', vndupr: 2.7, avatar: 'https://i.pravatar.cc/150?u=E' },
    { name: 'Ngô Thị F', vndupr: 0.4, avatar: 'https://i.pravatar.cc/150?u=F' },
]

const search = ref('')
const showPopup = ref(false)
const currentTeam = ref(null)
const currentIndex = ref(null)

const settings = reactive({
    sets: '1',
    point: '11',
    win_rule: '2',
    vndupr: 'yes',
    visibility: 'private',
})

const filteredPlayers = computed(() => {
    const selectedNames = [...teamA.value, ...teamB.value].map(p => p.name)
    let currentSelected = null
    if (currentTeam.value && currentIndex.value !== null) {
        currentSelected =
            currentTeam.value === 'A' ? teamA.value[currentIndex.value] : teamB.value[currentIndex.value]
    }
    return mockPlayers.filter(p =>
        (!selectedNames.includes(p.name) || (currentSelected && currentSelected.name === p.name)) &&
        p.name.toLowerCase().includes(search.value.toLowerCase())
    )
})

const openPlayerPopup = (team, index) => {
    currentTeam.value = team
    currentIndex.value = index
    showPopup.value = true
    search.value = ''
}

const selectPlayer = (player) => {
    if (currentTeam.value === 'A') teamA.value[currentIndex.value] = player
    else if (currentTeam.value === 'B') teamB.value[currentIndex.value] = player
    showPopup.value = false
}

const setMatchType = (type) => {
    matchType.value = type
    const size = type === 'single' ? 1 : 2
    teamA.value = Array(size).fill({})
    teamB.value = Array(size).fill({})
}

const swapTeams = () => {
    const temp = JSON.parse(JSON.stringify(teamA.value))
    teamA.value = JSON.parse(JSON.stringify(teamB.value))
    teamB.value = temp
}

const randomizeTeams = () => {
    const total = teamA.value.length + teamB.value.length
    const shuffled = [...mockPlayers].sort(() => Math.random() - 0.5).slice(0, total)
    teamA.value = shuffled.slice(0, teamA.value.length)
    teamB.value = shuffled.slice(teamA.value.length)
}

setMatchType(matchType.value)
</script>
