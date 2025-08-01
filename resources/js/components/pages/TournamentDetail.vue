<template>
  <div class="p-6 max-w-5xl mx-auto space-y-6 mt-8 bg-white rounded-lg shadow">
    <!-- Header -->
    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-gray-800 flex items-center justify-between">
        {{ tournament.name }}
        <span class="text-sm px-2 py-1 rounded bg-green-100 text-green-800">Đang diễn ra</span>
      </h1>
      <p class="text-gray-600">{{ tournament.location }} | {{ tournament.dateStart }} - {{ tournament.dateEnd }}</p>
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
        <button v-for="group in matchGroups" :key="group" @click="selectedGroup = group"
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
              <th class="p-3">Thể thức</th>
              <th class="p-3">Bảng</th>
              <th class="p-3">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="match in filteredMatches" :key="match.id" class="border-t text-sm">
              <td class="p-3">{{ match.player1 }}</td>
              <td class="p-3">{{ match.player2 }}</td>
              <td class="p-3">{{ match.time }}</td>
              <td class="p-3">{{ match.type }}</td>
              <td class="p-3">{{ match.group }}</td>
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
      <button @click="joinTournament" class="bg-primary text-white px-5 py-2 rounded hover:bg-secondary transition-colors">
        Tham gia giải
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'

const route = useRoute()
const tournamentId = route.params.id

const tournament = ref({})
const matches = ref([])
const matchTypes = ref(['Đơn', 'Đôi', 'Hỗn hợp'])
const matchGroups = ref(['A', 'B', 'C', 'D'])
const selectedType = ref('Đơn')
const selectedGroup = ref('A')

const filteredMatches = computed(() =>
  matches.value.filter(
    (match) => match.type === selectedType.value && match.group === selectedGroup.value
  )
)

onMounted(() => {
  tournament.value = {
    id: tournamentId,
    name: 'Giải Hè Pickleball 2025',
    location: 'Nhà thi đấu Quận 1',
    dateStart: '2025-08-15',
    dateEnd: '2025-08-18',
    description:
      'Giải đấu quy tụ 32 vận động viên xuất sắc nhất toàn quốc. Đây là cơ hội để các vận động viên thể hiện tài năng và cống hiến những trận đấu hấp dẫn.',
  }

  matches.value = [
    { id: 1, player1: 'Nam', player2: 'Minh', time: '10:00 - 15/08', type: 'Đơn', group: 'A', status: 'Đã diễn ra' },
    { id: 2, player1: 'Tú', player2: 'Phát', time: '11:30 - 15/08', type: 'Đơn', group: 'A', status: 'Sắp diễn ra' },
    { id: 3, player1: 'Tùng/Thắng', player2: 'Long/Phong', time: '13:00 - 15/08', type: 'Đôi', group: 'B', status: 'Sắp diễn ra' },
    { id: 4, player1: 'An/Phúc', player2: 'Hà/Hương', time: '14:30 - 15/08', type: 'Hỗn hợp', group: 'C', status: 'Chưa rõ' },
    { id: 5, player1: 'Bình', player2: 'Dương', time: '15:30 - 15/08', type: 'Đơn', group: 'D', status: 'Sắp diễn ra' },
  ]
})

function joinTournament() {
  toast.success('Bạn đã tham gia giải đấu thành công!')
}
</script>