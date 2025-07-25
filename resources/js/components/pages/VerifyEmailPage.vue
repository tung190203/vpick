<template>
  <div class="flex justify-center items-center min-h-screen bg-gray-50">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md text-center space-y-6">
      <div v-if="success === true" class="space-y-3">
        <h2 class="text-xl font-semibold text-green-600">Xác minh thành công!</h2>
        <p class="text-sm text-gray-600">
          Bạn sẽ được chuyển về trang đăng nhập sau 
          <span class="font-semibold text-blue-600">{{ countdown }}</span> giây.
        </p>
      </div>

      <div v-else-if="success === false" class="space-y-3">
        <h2 class="text-xl font-semibold text-red-600">Xác minh thất bại!</h2>
        <p class="text-sm text-gray-600">Liên kết xác minh không hợp lệ hoặc đã hết hạn.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { ref, onMounted } from 'vue'
import { toast } from 'vue3-toastify'

const route = useRoute()
const router = useRouter()

const success = ref(null)
const countdown = ref(3)

onMounted(async () => {
  try {
    const token = route.query.token
    await axios.get(`${import.meta.env.VITE_BASE_URL}/verify-email`, {
      params: { token }
    })
    success.value = true
    toast.success('Xác minh email thành công!')

    const interval = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(interval)
        router.push('/login')
      }
    }, 1000)
  } catch (e) {
    toast.error(e.response?.data?.message || 'Xác minh email thất bại!')
    success.value = false
  }
})
</script>
