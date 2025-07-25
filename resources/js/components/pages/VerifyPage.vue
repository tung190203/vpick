<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-50 px-4">
      <div class="bg-white rounded-xl shadow-lg p-8 max-w-md w-full text-center space-y-6">
        <img src="@/assets/images/email-sent.svg" alt="Email Sent" class="w-20 h-20 mx-auto" />
        <h2 class="text-xl font-semibold text-gray-800">Vui lòng xác minh email</h2>
        <p class="text-gray-600">
          Chúng tôi đã gửi một email xác minh đến:
          <span class="font-medium text-blue-600">{{ email }}</span><br />
          Hãy kiểm tra hộp thư đến (hoặc cả mục spam).
        </p>
  
        <div>
          <button
            class="w-full py-2 px-4 bg-blue-500 text-white font-semibold rounded disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="countdown > 0 || loading"
            @click="resendEmail"
          >
            <span v-if="countdown === 0 && !loading">Gửi lại email xác minh</span>
            <span v-else-if="loading">Đang gửi lại...</span>
            <span v-else>Gửi lại sau {{ countdown }}s</span>
          </button>
        </div>
  
        <router-link
          to="/login"
          class="block text-sm text-gray-500 hover:text-blue-600 mt-2 underline"
        >
          Quay lại trang đăng nhập
        </router-link>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import { useRoute } from 'vue-router'
  import axios from 'axios'
  import { toast } from 'vue3-toastify'
  
  const email = ref('')
  const countdown = ref(60)
  const loading = ref(false)
  const route = useRoute()
  
  email.value = route.query.email || 'email của bạn'
  
  let timer
  
  const startCountdown = () => {
    timer = setInterval(() => {
      if (countdown.value > 0) countdown.value--
      else clearInterval(timer)
    }, 1000)
  }
  
  const resendEmail = async () => {
    loading.value = true
    try {
      await axios.post(`${import.meta.env.VITE_BASE_URL}/resend-email`, {
        email: email.value
      })
      toast.success('Email xác minh đã được gửi lại.')
      countdown.value = 60
      startCountdown()
    } catch (error) {
      toast.error(error.response?.data?.message || 'Gửi lại email xác minh thất bại!')
    } finally {
      loading.value = false
    }
  }
  
  onMounted(() => {
    startCountdown()
  })
  </script>
  