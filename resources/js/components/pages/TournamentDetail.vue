<template>
  <div class="p-6 max-w-5xl mx-auto space-y-6 mt-8 bg-white rounded-lg shadow">
    <!-- Header -->
    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-gray-800 flex items-center justify-between">
        {{ tournament.name }}
        <span class="text-sm px-2 py-1 rounded" :class="statusClass(tournament.status)">
          {{ statusLabel(tournament.status) }}
        </span>
      </h1>
      <p class="text-gray-600">
        {{ tournament.location }} | {{ tournament.start_date }} - {{ tournament.end_date }}
      </p>
    </div>

    <!-- Mô tả -->
    <div class="text-gray-700 leading-relaxed">
      {{ tournament.description }}
    </div>

    <!-- Tabs thể thức thi đấu -->
    <div>
      <div class="flex gap-4 border-b mb-2">
        <button
          v-for="(type, index) in matchTypes"
          :key="index"
          @click="selectedType = type"
          class="py-2 px-4 text-sm font-medium"
          :class="{
            'border-b-2 border-primary text-primary': selectedType === type,
            'text-gray-600': selectedType !== type
          }"
        >
          {{ type }}
        </button>
      </div>
    </div>

    <!-- Tabs bảng đấu -->
    <div>
      <div class="flex gap-4 border-b mb-4">
        <button
          v-for="group in matchGroupsByType[selectedType] || []"
          :key="group"
          @click="selectedGroup = group"
          class="py-1 px-3 text-sm font-medium"
          :class="{
            'border-b-2 border-primary text-primary': selectedGroup === group,
            'text-gray-600': selectedGroup !== group
          }"
        >
          {{ group }}
        </button>
      </div>
    </div>

    <!-- Danh sách trận -->
    <div>
      <h2 class="text-xl font-semibold mb-2">
        Danh sách trận - {{ selectedType }} - Bảng {{ selectedGroup }}
      </h2>
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
                <span class="text-xs px-2 py-1 rounded" :class="matchStatusClass(match.status)">
                  {{ match.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-500 mt-4">
        Không có trận nào thuộc thể thức và bảng này.
      </div>
    </div>

    <!-- Nút tham gia -->
    <div class="text-right">
      <button
        v-if="tournament.joined"
        class="bg-gray-300 text-gray-700 px-5 py-2 rounded cursor-not-allowed"
        disabled
      >
        Đã tham gia giải
      </button>

      <button
        v-else
        @click="joinTournament()"
        class="bg-primary text-white px-5 py-2 rounded hover:bg-secondary transition-colors"
      >
        Tham gia giải
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import * as TournamentService from '@/service/tournament'
import {
  TOURNAMENT_STATUS,
  TOURNAMENT_STATUS_LABEL,
  MATCH_STATUS,
  MATCH_STATUS_LABEL,
  TYPE_OF_TOURNAMENT,
  TYPE_OF_TOURNAMENT_LABEL
} from '@/constants/index.js'

const route = useRoute()
const id = route.params.id

const tournament = ref({})
const matches = ref([])
const matchTypes = ref([])
const matchGroupsByType = ref({})
const selectedType = ref('')
const selectedGroup = ref(null)

const filteredMatches = computed(() =>
  matches.value.filter(
    (match) => match.type === selectedType.value && match.group === selectedGroup.value
  )
)

const matchStatusClass = (status) => {
  switch (status) {
    case MATCH_STATUS_LABEL.PENDING:
      return 'bg-yellow-100 text-yellow-800'
    case MATCH_STATUS_LABEL.COMPLETED:
      return 'bg-green-100 text-green-800'
    case MATCH_STATUS_LABEL.DISPUTED:
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-600'
  }
}

function getTypeLabel(type) {
  switch (type) {
    case TYPE_OF_TOURNAMENT.SINGLE:
      return TYPE_OF_TOURNAMENT_LABEL.SINGLE
    case TYPE_OF_TOURNAMENT.DOUBLE:
      return TYPE_OF_TOURNAMENT_LABEL.DOUBLE
    case TYPE_OF_TOURNAMENT.MIXED:
      return TYPE_OF_TOURNAMENT_LABEL.MIXED
    default:
      return 'Không xác định'
  }
}

function getMatchStatusLabel(status) {
  switch (status) {
    case MATCH_STATUS.PENDING:
      return MATCH_STATUS_LABEL.PENDING
    case MATCH_STATUS.COMPLETED:
      return MATCH_STATUS_LABEL.COMPLETED
    case MATCH_STATUS.DISPUTED:
      return MATCH_STATUS_LABEL.DISPUTED
    default:
      return 'Chưa rõ'
  }
}

const getDetailTournament = async (id) => {
  try {
    const response = await TournamentService.getTournamentById(id)
    tournament.value = response.data

    const types = response.data.tournament_types || []
    matchTypes.value = types.map((type) => getTypeLabel(type.type))

    const groupsMap = {}
    const allMatches = []

    types.forEach((type) => {
      const label = getTypeLabel(type.type)
      const groups = type.groups || []

      groupsMap[label] = groups.map((group) => group.name)

      groups.forEach((group) => {
        const groupMatches = group.matches || []
        groupMatches.forEach((match) => {
          const formatParticipant = (participant) => {
            if (!participant) return 'N/A'
            if (participant.type === 'user') return participant.user?.name || 'N/A'
            if (participant.type === 'team') return participant.team?.name || 'N/A'
            return 'N/A'
          }

          allMatches.push({
            id: match.id,
            type: label,
            group: group.name,
            player1: formatParticipant(match.participant1),
            player2: formatParticipant(match.participant2),
            time: match.scheduled_at || 'Chưa xác định',
            status: getMatchStatusLabel(match.status)
          })
        })
      })
    })

    matchGroupsByType.value = groupsMap
    matches.value = allMatches
    selectedType.value = matchTypes.value[0] || ''
    selectedGroup.value = matchGroupsByType.value[selectedType.value]?.[0] || null
  } catch (error) {
    console.error('Error fetching tournament details:', error)
  }
}

function statusLabel(status) {
  switch (status) {
    case TOURNAMENT_STATUS.UPCOMING:
      return TOURNAMENT_STATUS_LABEL.UPCOMING
    case TOURNAMENT_STATUS.ONGOING:
      return TOURNAMENT_STATUS_LABEL.ONGOING
    case TOURNAMENT_STATUS.FINISHED:
      return TOURNAMENT_STATUS_LABEL.FINISHED
    default:
      return ''
  }
}

function statusClass(status) {
  switch (status) {
    case TOURNAMENT_STATUS.UPCOMING:
      return 'bg-yellow-100 text-yellow-800'
    case TOURNAMENT_STATUS.ONGOING:
      return 'bg-green-100 text-green-800'
    case TOURNAMENT_STATUS.FINISHED:
      return 'bg-gray-200 text-gray-700'
    default:
      return ''
  }
}

watch(selectedType, (newType) => {
  if (newType && matchGroupsByType.value[newType]) {
    selectedGroup.value = matchGroupsByType.value[newType][0] || null
  } else {
    selectedGroup.value = null
  }
})

onMounted(async () => {
  await getDetailTournament(id)
})
</script>
