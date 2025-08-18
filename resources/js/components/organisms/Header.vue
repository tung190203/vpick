<template>
    <header class="bg-primary/90 backdrop-blur-md shadow px-4 py-3 sticky top-0 z-50 transition duration-300 border-b">
    <div class="flex justify-between items-center">
      <!-- Logo -->
      <img
        src="@/assets/images/logo.png"
        alt="Logo"
        class="cursor-pointer"
        style="height: 3.5rem;"
        @click="goToDashboard"
      />
      <!-- Desktop Menu -->
      <nav class="hidden md:flex space-x-6 text-gray-700 font-medium">
        <template v-if="getRole === ROLE.ADMIN">
          <RouterLink to="/admin/dashboard" class="text-white">Trang chủ</RouterLink>
        </template>
        <template v-if="getRole === ROLE.REFEREE">
          <RouterLink to="/referee/dashboard" class="text-white">Trang chủ</RouterLink>
          <RouterLink to="" class="text-white">Giải đấu được phân công</RouterLink>
          <RouterLink to="" class="text-white">Báo cáo / Khiếu nại</RouterLink>
        </template>
        <template v-if="getRole === ROLE.PLAYER">
          <RouterLink to="/" class="text-white">Trang chủ</RouterLink>
          <RouterLink to="/tournament" class="text-white">Giải đấu</RouterLink>
          <RouterLink to="/friendly-match/create" class="text-white">Giao hữu</RouterLink>
          <RouterLink to="/leaderboard" class="text-white">Bảng xếp hạng</RouterLink>
          <RouterLink to="/club" class="text-white">CLB</RouterLink>
        </template>
      </nav>

      <!-- Right user info -->
      <div ref="dropdownRef" class="flex items-center space-x-3 relative cursor-pointer">
        <span class="text-sm hidden sm:inline cursor-pointer text-white select-none" @click="toggleDropdown">{{ getUser.full_name }}</span>
        <img
          :src="getUser.avatar_url"
          alt="avatar"
          class="w-8 h-8 rounded-full border cursor-pointer"
          @click="toggleDropdown"
        />
        <!-- Mobile menu toggle -->
        <button class="md:hidden" @click="mobileMenuOpen = !mobileMenuOpen">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <!-- Dropdown -->
        <div v-if="dropdownOpen" class="absolute right-0 top-12 bg-white border rounded shadow z-50 w-48">
          <ul class="divide-y divide-gray-200">
            <li>
              <router-link to="/profile" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded" @click="dropdownOpen = false">
                Hồ sơ cá nhân
              </router-link>
            </li>
            <li v-if="getRole === ROLE.PLAYER">
              <router-link to="/my-tournament"
                class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded" @click="dropdownOpen = false">
                Giải đấu của tôi
              </router-link>
            </li>
            <li v-if="getRole === ROLE.PLAYER">
              <router-link to="/settings" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded" @click="dropdownOpen = false">
                Cài đặt
              </router-link>
            </li>
            <li>
              <button
                class="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-100 rounded"
                @click="logout"
              >
                Đăng xuất
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div v-if="mobileMenuOpen" class="md:hidden mt-3 space-y-2">
      <RouterLink to="/" class="block text-gray-700 text-white">Trang chủ</RouterLink>
      <RouterLink to="/tournament" class="block text-gray-700 text-white">Giải đấu</RouterLink>
      <RouterLink to="/friendly/create" class="block text-gray-700 text-white">Giao hữu</RouterLink>
      <RouterLink to="/leaderboard" class="block text-gray-700 text-white">Bảng xếp hạng</RouterLink>
      <RouterLink to="/club" class="block text-gray-700 text-white">CLB</RouterLink>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/store/auth'
import { toast } from 'vue3-toastify'
import { storeToRefs } from 'pinia'
import { ROLE } from '@/constants/index'

const userStore = useUserStore()
const router = useRouter()
const { getUser } = storeToRefs(userStore)
const { getRole } = storeToRefs(userStore)

const goToDashboard = () => {
  switch (getRole.value) {
    case ROLE.ADMIN:
      router.push({ name: 'admin.dashboard' })
      break
    case ROLE.REFEREE:
      router.push({ name: 'referee.dashboard' })
      break
    case ROLE.PLAYER:
    default:
      router.push({ name: 'dashboard' })
      break
  }
}


const mobileMenuOpen = ref(false)
const dropdownOpen = ref(false)
const dropdownRef = ref(null)

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    dropdownOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

const logout = async () => {
  try {
    await userStore.logoutUser()
    toast.success('Đăng xuất thành công!')
    dropdownOpen.value = false
    mobileMenuOpen.value = false
    setTimeout(() => {
      router.push({ name: 'login' })
    }, 500)
  } catch (error) {
    toast.error(error.response?.data?.message || 'Đăng xuất thất bại!')
  }
}
</script>