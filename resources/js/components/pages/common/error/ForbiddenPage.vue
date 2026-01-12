<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 p-8">
    <h1 class="text-6xl font-bold text-red-500 mb-4">403</h1>
    <h2 class="text-2xl font-semibold mb-2">Không có quyền truy cập</h2>
    <p class="text-gray-600 mb-6">Bạn không được phép truy cập vào trang này.</p>

    <router-link
      :to="homeRoute"
      class="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition"
    >
      Quay về trang chủ
    </router-link>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useUserStore } from '@/store/auth'
import { storeToRefs } from 'pinia'
import { ROLE } from '@/constants/index.js'

const userStore = useUserStore()
const { getRole: userRole } = storeToRefs(userStore);

const homeRoute = computed(() => {
  switch (userRole.value) {
    case ROLE.ADMIN:
      return { name: 'admin.dashboard' }
    case ROLE.REFEREE:
      return { name: 'referee.dashboard' }
    case ROLE.PLAYER:
      return { name: 'dashboard' }
    default:
    return { name: 'dashboard' }
  }
})
</script>
