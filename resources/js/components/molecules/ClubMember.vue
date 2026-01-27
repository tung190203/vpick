<template>
  <div class="bg-white">
    <div class="relative mb-6">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
      </div>
      <input type="text" placeholder="Tìm tên, trình độ"
        class="block w-full pl-10 pr-3 py-2.5 border border-[#EDEEF2] rounded-md bg-[#EDEEF2] text-sm placeholder-[#9EA2B3] focus:outline-none focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all">
    </div>
    <div>
      <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-tight mb-4 flex items-center gap-1.5">
        BAN QUẢN TRỊ <span class="text-gray-400 text-lg">•</span> {{ adminAndMods.length }}
      </h3>
      <div v-for="user in adminAndMods" :key="user.id"
        class="flex items-center justify-between py-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
          <div class="relative p-0.5 rounded-full border-2"
            :class="user.role === 'Admin' ? 'border-blue-400' : 'border-orange-400'">
            <img :src="user.avatar" :alt="user.name" class="w-14 h-14 rounded-full object-cover">
            <div v-if="user.role === 'Admin'"
              class="absolute -bottom-1 -right-1 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center border-2 border-white">
              <img :src="ShieldCheckIcon" alt="" class="w-3 h-3">
            </div>
            <div v-else-if="user.status === 'Thủ quỹ'"
              class="absolute -bottom-1 -right-1 w-6 h-6 bg-orange-400 rounded-full flex items-center justify-center border-2 border-white">
              <img :src="MoneyIcon" alt="" class="w-3 h-3">
            </div>
          </div>

          <div>
            <div class="flex items-center gap-2">
              <p class="font-semibold text-[#374151]">{{ user.name }}</p>
              <span :class="[
                'px-2 py-0.5 text-[10px] font-bold rounded text-white uppercase',
                user.role === 'Admin' ? 'bg-blue-500' : 'bg-orange-400'
              ]">
                {{ user.role }}
              </span>
            </div>
            <p class="text-xs text-gray-400 font-medium">
              {{ user.pickl }} PICKI • {{ user.status }}
            </p>
          </div>
        </div>

        <div class="relative">
          <button
            @click="toggleMenu(user.id)"
            class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 hover:bg-gray-200 transition-colors">
            <EllipsisHorizontalIcon class="w-4 h-4" />
          </button>

          <!-- Dropdown Menu -->
          <div v-if="openMenuId === user.id" 
            class="absolute right-0 top-10 w-44 bg-white rounded-xl shadow-xl py-2 z-[10000] border border-gray-100 animate-in fade-in zoom-in duration-200">
            <button 
              @click="viewInfo(user)"
              class="w-full text-left px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
              <InformationCircleIcon class="w-4 h-4 text-gray-400" />
              Xem thông tin
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Members Section -->
    <div class="mt-8">
      <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-tight mb-4 flex items-center gap-1.5">
        THÀNH VIÊN <span class="text-gray-400 text-lg">•</span> {{ members.length }}
      </h3>

      <div class="divide-y divide-gray-100">
        <div v-for="member in members" :key="member.id" class="flex items-center justify-between py-4">
          <div class="flex items-center gap-3">
            <div class="relative">
              <img :src="member.avatar" :alt="member.name" class="w-14 h-14 rounded-full object-cover bg-orange-50">
              <!-- Member Level Badge -->
              <div
                class="absolute -bottom-1 -left-1 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center border-2 border-white text-white text-[9px] font-bold">
                {{ member.level }}
              </div>
              <!-- Online Status Indicator -->
              <div v-if="member.online"
                class="absolute bottom-[-1px] right-[-1px] w-4 h-4 bg-emerald-500 rounded-full border-2 border-white">
              </div>
            </div>

            <div>
              <p class="font-semibold text-[#374151]">{{ member.name }}</p>
              <p class="text-xs text-gray-400">{{ member.lastSeen }}</p>
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
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Member Info Modal -->
    <MemberInfoModal 
      v-model="showModal" 
      :member="selectedMember" 
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ShieldCheckIcon from "@/assets/images/shield_check.svg";
import MoneyIcon from "@/assets/images/money.svg";
import { EllipsisHorizontalIcon, MagnifyingGlassIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import MemberInfoModal from '@/components/molecules/MemberInfoModal.vue';

const adminAndMods = ref([
  {
    id: 1,
    name: 'Nguyễn Tuấn Anh',
    avatar: 'https://images.pexels.com/photos/30643252/pexels-photo-30643252.jpeg',
    role: 'Admin',
    pickl: '3.5',
    status: 'Chủ câu lạc bộ',
    verified: true
  },
  {
    id: 2,
    name: 'Trần Minh',
    avatar: 'https://images.pexels.com/photos/30643252/pexels-photo-30643252.jpeg',
    role: 'Mod',
    pickl: '3.0',
    status: 'Thủ quỹ',
    verified: false
  }
])

const members = ref([
  {
    id: 3,
    name: 'Lê Văn Cường',
    avatar: 'https://images.pexels.com/photos/30643252/pexels-photo-30643252.jpeg',
    level: '4.5',
    lastSeen: 'Tham gia 3 ngày trước',
    online: true
  }
])

const openMenuId = ref(null)
const showModal = ref(false)
const selectedMember = ref(null)

const toggleMenu = (id) => {
  if (openMenuId.value === id) {
    openMenuId.value = null
  } else {
    openMenuId.value = id
  }
}

const closeMenu = () => {
  openMenuId.value = null
}

const viewInfo = (member) => {
  selectedMember.value = member
  showModal.value = true
  closeMenu()
}
</script>

<style scoped>
/* Typography and colors based on image */
.tracking-tight {
  letter-spacing: -0.015em;
}
</style>