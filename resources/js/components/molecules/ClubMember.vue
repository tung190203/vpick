<template>
  <div class="bg-white">
    <div class="relative mb-6">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
      </div>
      <input 
        v-model="searchQuery"
        type="text" 
        placeholder="Tìm tên, trình độ"
        class="block w-full pl-10 pr-3 py-2.5 border border-[#EDEEF2] rounded-md bg-[#EDEEF2] text-sm placeholder-[#9EA2B3] focus:outline-none focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all">
    </div>

    <!-- Content with Loading Overlay -->
    <div class="relative min-h-[300px]">
      <!-- Loading Overlay -->
      <div v-if="loading" 
        class="absolute inset-0 z-10 flex justify-center items-start pt-12 bg-white/60 backdrop-blur-[1px] transition-all duration-300">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>

      <!-- Main Content -->
      <div :class="{ 'opacity-40 pointer-events-none': loading }" class="transition-opacity duration-300">
      <!-- Management Section (All non-member roles including admin) -->
      <div v-if="managementMembers.length > 0" class="mb-8">
        <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-tight mb-4 flex items-center gap-1.5">
          BAN QUẢN TRỊ <span class="text-gray-400 text-lg">•</span> {{ managementMembers.length }}
        </h3>
        <div v-for="member in managementMembers" :key="member.id"
          class="flex items-center justify-between py-4 border-b border-gray-200">
          <div class="flex items-center gap-3">
            <div class="relative p-0.5 rounded-full border-2"
              :class="getRoleBorderColor(member.role)">
              <img :src="member.user?.avatar_url || 'https://picki.vn/images/default-avatar.png'" 
                :alt="member.user?.full_name" 
                class="w-14 h-14 rounded-full object-cover">
              <div 
                class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center border-2 border-white"
                :class="getRoleBadgeColor(member.role)">
                <ShieldCheckIcon v-if="member.role === 'admin'" class="w-3 h-3" />
                <MoneyIcon v-else-if="member.role === 'treasurer'" class="w-3 h-3 text-white" />
              </div>
            </div>

            <div>
              <div class="flex items-center gap-2">
                <p class="font-semibold text-[#374151]">{{ member.user?.full_name || 'N/A' }}</p>
                <span :class="[
                  'px-2 py-0.5 text-[10px] font-bold rounded text-white uppercase',
                  getRoleTagColor(member.role)
                ]">
                  {{ getRoleLabel(member.role) }}
                </span>
              </div>
              <p class="text-xs text-gray-400 font-medium">
                {{ getVpScore(member.user) }} PICKI • {{ getRolePosition(member.role) }}
              </p>
            </div>
          </div>

          <div class="relative">
            <button
              @click="toggleMenu(member.id)"
              class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 hover:bg-gray-200 transition-colors">
              <EllipsisHorizontalIcon class="w-4 h-4" />
            </button>

            <!-- Dropdown Menu -->
            <div v-if="openMenuId === member.id" 
              class="absolute right-0 top-10 w-44 bg-white rounded-xl shadow-xl py-2 z-[10000] border border-gray-100 animate-in fade-in zoom-in duration-200">
              <button 
                @click="viewInfo(member)"
                class="w-full text-left px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                <InformationCircleIcon class="w-4 h-4 text-gray-400" />
                Xem thông tin
              </button>
              <button v-if="member.role !== 'admin' && member.user?.id !== getUser.id"
                @click="confirmDeleteMember(member)"
                class="w-full text-left px-4 py-3 text-sm font-medium text-red-700 hover:bg-red-50 flex items-center gap-2">
                <TrashIcon class="w-4 h-4 text-red-400" />
                Xoá khỏi CLB
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Members Section -->
      <div class="mt-8" v-if="regularMembers.length > 0">
        <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-tight mb-4 flex items-center gap-1.5">
          THÀNH VIÊN <span class="text-gray-400 text-lg">•</span> {{ totalRegularMembers }}
        </h3>

        <div class="divide-y divide-gray-100">
          <div v-for="member in regularMembers" :key="member.id" class="flex items-center justify-between py-4">
            <div class="flex items-center gap-3">
              <div class="relative">
                <img :src="member.user?.avatar_url || 'https://picki.vn/images/default-avatar.png'" 
                  :alt="member.user?.full_name" 
                  class="w-14 h-14 rounded-full object-cover bg-orange-50">
                <!-- Member Level Badge -->
                <div
                  class="absolute -bottom-1 -left-1 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center border-2 border-white text-white text-[9px] font-bold">
                  {{ getVpScore(member.user) }}
                </div>
                <!-- Online Status Indicator -->
                <div v-if="isOnline(member.user?.last_login)"
                  class="absolute bottom-[-1px] right-[-1px] w-4 h-4 bg-emerald-500 rounded-full border-2 border-white">
                </div>
              </div>

              <div>
                <p class="font-semibold text-[#374151]">{{ member.user?.full_name || 'N/A' }}</p>
                <p class="text-xs text-gray-400">{{ getJoinedDate(member.joined_at || member.created_at) }}</p>
              </div>
            </div>

            <div class="relative">
              <button
                @click="toggleMenu(member.id)"
                class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 hover:bg-gray-200 transition-colors">
                <EllipsisHorizontalIcon class="w-4 h-4" />
              </button>

              <!-- Dropdown Menu -->
              <div v-if="openMenuId === member.id" 
                class="absolute right-0 top-10 w-44 bg-white rounded-xl shadow-xl py-2 z-50 border border-gray-100 animate-in fade-in zoom-in duration-200">
                <button 
                  @click="viewInfo(member)"
                  class="w-full text-left px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                  <InformationCircleIcon class="w-4 h-4 text-gray-400" />
                  Xem thông tin
                </button>
                <button v-if="member.user?.id !== getUser.id && isJoined"
                  @click="confirmDeleteMember(member)"
                  class="w-full text-left px-4 py-3 text-sm font-medium text-red-700 hover:bg-red-50 flex items-center gap-2">
                  <TrashIcon class="w-4 h-4 text-red-400" />
                  Xoá khỏi CLB
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && managementMembers.length === 0 && regularMembers.length === 0" class="text-center py-12">
        <p class="text-gray-400">Không tìm thấy thành viên nào</p>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-8 pt-6 border-t border-gray-200">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
          Trước
        </button>
        
        <div class="flex items-center gap-1">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
              page === currentPage 
                ? 'bg-blue-500 text-white' 
                : 'border border-gray-300 hover:bg-gray-50'
            ]">
            {{ page }}
          </button>
        </div>

        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
          Sau
        </button>
      </div>
      </div>
    </div>

    <!-- Member Info Modal -->
    <MemberInfoModal 
      v-model="showModal" 
      :member="selectedMember" 
      @updated="fetchData"
    />

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmationModal
      v-model="showDeleteModal"
      title="Xoá thành viên"
      :message="deleteMessage"
      confirmButtonText="Xoá"
      confirmButtonClass="bg-red-600 hover:bg-red-700"
      @confirm="handleDeleteMember"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import ShieldCheckIcon from "@/assets/images/shield_check.svg";
import MoneyIcon from "@/assets/images/money.svg";
import { EllipsisHorizontalIcon, MagnifyingGlassIcon, InformationCircleIcon, TrashIcon } from '@heroicons/vue/24/outline';
import MemberInfoModal from '@/components/molecules/MemberInfoModal.vue';
import DeleteConfirmationModal from '@/components/molecules/DeleteConfirmationModal.vue';
import * as ClubService from '@/service/club.js'
import { useUserStore } from '@/store/auth'
import { storeToRefs } from 'pinia'
import { toast } from "vue3-toastify";
import { ROLE_COLORS } from '@/data/club'

const userStore = useUserStore()
const { getUser } = storeToRefs(userStore)
const props = defineProps({
  clubId: {
    type: [String, Number],
    required: true
  },
  isJoined: {
    type: Boolean,
    default: false
  }
})

// State
const searchQuery = ref('')
const openMenuId = ref(null)
const showModal = ref(false)
const showDeleteModal = ref(false)
const memberToDelete = ref(null)
const selectedMember = ref(null)
const loading = ref(false)
const currentPage = ref(1)
const perPage = ref(15)
const totalPages = ref(1)
const totalMembers = ref(0)
const totalRegularMembers = ref(0)
const members = ref([])
const statistics = ref({})
const allManagementMembers = ref([]) // Store all management members separately

// Debounce timer
let searchTimeout = null

// Helper function to get role priority (lower number = higher priority)
const getRolePriority = (role) => {
  const priorities = {
    'admin': 1,
    'manager': 2,
    'treasurer': 3,
    'secretary': 4,
    'member': 5
  }
  return priorities[role] || 999
}

const managementMembers = computed(() => {
  let filtered = allManagementMembers.value
    .filter(member => member.user)
  
  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(member => {
      const fullName = member.user?.full_name?.toLowerCase() || ''
      const role = getRoleLabel(member.role).toLowerCase()
      const score = getVpScore(member.user).toString()
      
      return fullName.includes(query) || 
             role.includes(query) || 
             score.includes(query)
    })
  }
  
  // Sort by role priority
  return filtered.sort((a, b) => getRolePriority(a.role) - getRolePriority(b.role))
})

const regularMembers = computed(() => {
  return members.value.filter(member => 
    member.role === 'member' && member.user
  )
})

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

const deleteMessage = computed(() => {
  if (!memberToDelete.value) return ''
  return `Bạn có chắc chắn muốn xoá thành viên ${memberToDelete.value.user?.full_name} khỏi câu lạc bộ không?`
})

// Methods
const fetchManagementMembers = async () => {
  try {
    const response = await ClubService.getMembers(props.clubId, {
      status: 'active',
      per_page: 100
    })
    allManagementMembers.value = (response.data.members || [])
      .filter(member => member.user && member.role !== 'member')
  } catch (error) {
    console.error('Error fetching management members:', error)
    allManagementMembers.value = []
  }
}

const fetchMembers = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      status: 'active',
    }

    if (!searchQuery.value) {
      params.role = 'member' // Only fetch regular members for pagination when not searching
    }
    
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    
    const response = await ClubService.getMembers(props.clubId, params)
    
    members.value = response.data.members || []
    statistics.value = response.data.statistics || {}
    currentPage.value = response.meta.current_page || 1
    totalPages.value = response.meta.last_page || 1
    totalMembers.value = response.meta.total || 0
    totalRegularMembers.value = response.data.statistics?.by_role?.member || 0
    perPage.value = response.meta.per_page || 15
  } catch (error) {
    console.error('Error fetching members:', error)
    members.value = []
  } finally {
    loading.value = false
  }
}

const fetchData = async () => {
  await fetchManagementMembers()
  await fetchMembers()
  
  if (selectedMember.value) {
    const updated = allManagementMembers.value.find(m => m.id === selectedMember.value.id) ||
                    members.value.find(m => m.id === selectedMember.value.id)
    if (updated) {
      selectedMember.value = updated
    }
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    fetchMembers()
  }
}

// Helper functions
const getRoleBorderColor = (role) => {
  const colors = {
    'admin': 'border-blue-400',
    'manager': 'border-purple-400',
    'treasurer': 'border-orange-400',
    'secretary': 'border-green-400'
  }
  return colors[role] || 'border-gray-300'
}

const getRoleBadgeColor = (role) => {
  return ROLE_COLORS[role] || 'bg-gray-500'
}

const getRoleTagColor = (role) => {
  return ROLE_COLORS[role] || 'bg-gray-500'
}

const getRoleLabel = (role) => {
  const labels = {
    'admin': 'Admin',
    'manager': 'Quản lý',
    'treasurer': 'Thủ quỹ',
    'secretary': 'Thư ký',
    'member': 'Thành viên'
  }
  return labels[role] || role
}

const getRolePosition = (role) => {
  const positions = {
    'admin': 'Chủ câu lạc bộ',
    'manager': 'Quản lý',
    'treasurer': 'Thủ quỹ',
    'secretary': 'Thư ký'
  }
  return positions[role] || 'Thành viên'
}

const getVpScore = (user) => {
  const pickleball = user?.sports?.find(s => s.sport_id === 1 || s.sport_name === 'Pickleball');
  const score = pickleball?.scores?.vndupr_score;
  return score ? Number(score).toFixed(1) : '0';
}

const isOnline = (lastLogin) => {
  if (!lastLogin) return false
  const lastLoginDate = new Date(lastLogin)
  const now = new Date()
  const diffMinutes = (now - lastLoginDate) / (1000 * 60)
  return diffMinutes < 15 // Online if logged in within last 15 minutes
}

const getJoinedDate = (date) => {
  if (!date) return 'N/A'
  const joinedDate = new Date(date)
  const now = new Date()
  const diffDays = Math.floor((now - joinedDate) / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Tham gia hôm nay'
  if (diffDays === 1) return 'Tham gia 1 ngày trước'
  if (diffDays < 30) return `Tham gia ${diffDays} ngày trước`
  
  const diffMonths = Math.floor(diffDays / 30)
  if (diffMonths === 1) return 'Tham gia 1 tháng trước'
  if (diffMonths < 12) return `Tham gia ${diffMonths} tháng trước`
  
  const diffYears = Math.floor(diffMonths / 12)
  return `Tham gia ${diffYears} năm trước`
}

const toggleMenu = (id) => {
  openMenuId.value = openMenuId.value === id ? null : id
}

const closeMenu = () => {
  openMenuId.value = null
}

const viewInfo = (member) => {
  selectedMember.value = member
  showModal.value = true
  closeMenu()
}

const confirmDeleteMember = (member) => {
  memberToDelete.value = member
  showDeleteModal.value = true
  closeMenu()
}

const handleDeleteMember = async () => {
  if (!memberToDelete.value) return

  try {
    await ClubService.removeMember(props.clubId, memberToDelete.value.id)
    toast.success('Xoá thành viên thành công')
    
    // Refresh lists
    fetchMembers()
    fetchManagementMembers()
  } catch (error) {
    console.error('Error removing member:', error)
    toast.error('Có lỗi xảy ra khi xoá thành viên')
  } finally {
    showDeleteModal.value = false
    memberToDelete.value = null
  }
}

// Watchers
watch(searchQuery, () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchMembers()
  }, 300)
})

// Lifecycle
onMounted(() => {
  fetchManagementMembers() // Fetch all management members first (shown on every page)
  fetchMembers() // Then fetch regular members with pagination
})
</script>

<style scoped>
.tracking-tight {
  letter-spacing: -0.015em;
}
</style>