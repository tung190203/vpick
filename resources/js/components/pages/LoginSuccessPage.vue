<template>
  <div class="flex flex-col justify-center items-center h-screen space-y-4 text-gray-600">
    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
    </svg>
    <p>Đang tiến hành đăng nhập...</p>
  </div>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { LOCAL_STORAGE_KEY, LOCAL_STORAGE_USER } from '@/constants/index.js'
import { useVerifyStore } from '@/store/verify.js'

const router = useRouter()
const route = useRoute()
const verifyStore = useVerifyStore()

const token = route.query.access_token
const type = route.query.token_type || 'Bearer'
const refreshToken = route.query.refresh_token
if (refreshToken) {
  localStorage.setItem(LOCAL_STORAGE_KEY.REFRESH_TOKEN, refreshToken)
}

async function fetchUser(token, type) {
  try {
    const res = await axios.get(import.meta.env.VITE_BASE_URL + '/me', {
      headers: {
        Authorization: `${type} ${token}`,
      }
    })
    localStorage.setItem(LOCAL_STORAGE_USER.USER, JSON.stringify(res.data))
    router.replace({ name: 'dashboard' })
  } catch (err) {
    console.error('Lấy thông tin user thất bại:', err)
    router.replace({ name: 'login' })
  }
}

if (token) {
  setTimeout(async () => {
    localStorage.setItem(LOCAL_STORAGE_KEY.LOGIN_TOKEN, token)
    await fetchUser(token, type)
    await verifyStore.showVerification()
  }, 1500)
} else {
  router.replace({ name: 'login' })
}
</script>