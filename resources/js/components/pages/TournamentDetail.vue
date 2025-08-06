<template>
  <div class="p-6 max-w-5xl mx-auto space-y-6 mt-8 bg-white rounded-lg shadow">
    <!-- Header -->
    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-gray-800 flex items-center justify-between">
        {{ tournament.name }}
        <span class="text-sm px-2 py-1 rounded" :class="statusClass(tournament.status)">{{
          statusLabel(tournament.status) }}</span>
      </h1>
      <p class="text-gray-600">{{ tournament.location }} | {{ tournament.start_date }} - {{ tournament.end_date }}</p>
    </div>

    <!-- Mô tả -->
    <div class="text-gray-700 leading-relaxed">
      {{ tournament.description }}
    </div>

    <!-- Tabs thể thức thi đấu -->
    <div>
      <div class="flex gap-4 border-b mb-2">
        <button v-for="(type, index) in matchTypes" :key="index" @click="selectedType = type"
          class="py-2 px-4 text-sm font-medium" :class="{
            'border-b-2 border-primary text-primary': selectedType === type,
            'text-gray-600': selectedType !== type
          }">
          {{ type }}
        </button>
      </div>
    </div>

    <!-- Tabs bảng đấu -->
    <div>
      <div class="flex gap-4 border-b mb-4">
        <button v-for="group in matchGroupsByType[selectedType] || []" :key="group" @click="selectedGroup = group"
          class="py-1 px-3 text-sm font-medium" :class="{
            'border-b-2 border-primary text-primary': selectedGroup === group,
            'text-gray-600': selectedGroup !== group
          }">
          Bảng {{ group }}
        </button>
      </div>
    </div>

    <!-- Danh sách trận -->
    <div>
      <h2 class="text-xl font-semibold mb-2">Danh sách trận - {{ selectedType }} - Bảng {{ selectedGroup }}</h2>
      <div v-if="filteredMatches.length > 0" class="overflow-x-auto">
        <table class="w-full text-left border rounded">
          <thead class="bg-gray-100 text-sm text-gray-700">
            <tr>
              <th class="p-3">Người chơi 1</th>
              <th class="p-3">Người chơi 2</th>
              <th class="p-3">Thời gian</th>
              <th class="p-3">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="match in filteredMatches" :key="match.id" class="border-t text-sm">
              <td class="p-3">{{ match.player1 }}</td>
              <td class="p-3">{{ match.player2 }}</td>
              <td class="p-3">{{ match.time }}</td>
              <td class="p-3">
                <span class="text-xs px-2 py-1 rounded" :class="{
                  'bg-green-100 text-green-800': match.status === 'Đã diễn ra',
                  'bg-yellow-100 text-yellow-800': match.status === 'Sắp diễn ra',
                  'bg-gray-100 text-gray-600': match.status === 'Chưa rõ'
                }">
                  {{ match.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-500 mt-4">Không có trận nào thuộc thể thức và bảng này.</div>
    </div>

    <!-- Nút tham gia -->
    <div class="text-right">
      <button v-if="tournament.joined" class="bg-gray-300 text-gray-700 px-5 py-2 rounded cursor-not-allowed" disabled>
        Đã tham gia giải
      </button>

      <button v-else @click="joinTournament"
        class="bg-primary text-white px-5 py-2 rounded hover:bg-secondary transition-colors">
        Tham gia giải
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import * as TournamentService from '@/service/tournament'
import { TOURNAMENT_STATUS, TOURNAMENT_STATUS_LABEL } from '@/constants/index.js'

const route = useRoute()
const id = route.params.id

const tournament = ref({})
const matches = ref([])
const matchTypes = ref([])
const matchGroupsByType = ref([])
const selectedType = ref('Đơn')
const selectedGroup = ref(null)

const filteredMatches = computed(() =>
  matches.value.filter(
    (match) => match.type === selectedType.value && match.group === selectedGroup.value
  )
)

function getTypeLabel(type) {
  switch (type) {
    case 'single':
      return 'Đơn';
    case 'double':
      return 'Đôi';
    case 'mixed':
      return 'Hỗn hợp';
    default:
      return 'Không xác định';
  }
}

const getDetailTournament = async (id) => {
  try {
    const response = await TournamentService.getTournamentById(id)
    tournament.value = response.data
    const types = response.data.types || []
    matchTypes.value = types.map(type => getTypeLabel(type.type))
    const groupsMap = {}
    types.forEach(type => {
      const label = getTypeLabel(type.type)
      groupsMap[label] = (type.groups || []).map(group => group.name)
    })
    matchGroupsByType.value = groupsMap
    selectedType.value = matchTypes.value[0]
    selectedGroup.value = matchGroupsByType.value[selectedType.value]?.[0] || null
  } catch (error) {
    console.error('Error fetching tournament details:', error)
  }
}

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

onMounted(async () => {
  await getDetailTournament(id)
  // matches.value = [
  //   { id: 1, player1: 'Nam', player2: 'Minh', time: '10:00 - 15/08', type: 'Đơn', group: 'A', status: 'Đã diễn ra' },
  //   { id: 2, player1: 'Tú', player2: 'Phát', time: '11:30 - 15/08', type: 'Đơn', group: 'A', status: 'Sắp diễn ra' },
  //   { id: 3, player1: 'Tùng/Thắng', player2: 'Long/Phong', time: '13:00 - 15/08', type: 'Đôi', group: 'B', status: 'Sắp diễn ra' },
  //   { id: 4, player1: 'An/Phúc', player2: 'Hà/Hương', time: '14:30 - 15/08', type: 'Hỗn hợp', group: 'C', status: 'Chưa rõ' },
  //   { id: 5, player1: 'Bình', player2: 'Dương', time: '15:30 - 15/08', type: 'Đơn', group: 'D', status: 'Sắp diễn ra' },
  // ]
})

function joinTournament() {
  toast.success('Bạn đã tham gia giải đấu thành công!')
}
</script>