<template>
    <div class="flex flex-col items-center justify-center min-h-screen px-4">
      <LogoSplash class="w-[60%]" />
      <div class="text-center mb-8 mt-8">
        <h1 class="text-white text-2xl mb-2">Quên mật khẩu</h1>
        <p class="text-sm text-white font-light">
          Tận hưởng toàn bộ tính năng của Pickleball, bao gồm cập nhật giải đấu, 
          bảng xếp hạng và thông báo trận đấu độc quyền!
        </p>
      </div>
  
      <div class="bg-white rounded-xl shadow-lg p-8 max-w-md w-full text-center space-y-6">
        <EmailSent class="w-20 h-20 mx-auto" />
        <h2 class="text-xl font-semibold text-gray-800">Nhập mã xác minh</h2>
        <p class="text-gray-600">
          Chúng tôi đã gửi mã OTP đến:
          <span class="font-medium text-blue-600">{{ email }}</span>
        </p>
  
        <!-- Ô nhập OTP -->
        <div class="flex justify-center space-x-2 mt-4">
          <input
            v-for="(digit, index) in otpDigits"
            :key="index"
            type="text"
            maxlength="1"
            class="w-10 h-12 text-center text-xl border rounded focus:outline-none focus:ring-2 focus:ring-primary"
            v-model="otpDigits[index]"
            @input="handleInput(index)"
            @keydown.backspace="handleBackspace(index, $event)"
            ref="otpRefs[index]"
          />
        </div>
  
        <!-- Loading xác minh -->
        <div v-if="submitting" class="flex justify-center space-x-2 mt-3">
          <div class="w-2 h-2 !bg-primary rounded-full animate-bounce"></div>
          <div class="w-2 h-2 !bg-primary rounded-full animate-bounce [animation-delay:-.2s]"></div>
          <div class="w-2 h-2 !bg-primary rounded-full animate-bounce [animation-delay:-.4s]"></div>
        </div>
  
        <!-- Nút xác minh -->
        <div class="mt-4">
          <button
            class="w-full py-2 px-4 !bg-primary hover:!bg-secondary text-white font-semibold rounded disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="submitting || otpDigits.join('').length < 6"
            @click="submitOtp"
          >
            <span v-if="!submitting">Gửi</span>
            <span v-else>Đang xác minh...</span>
          </button>
        </div>
  
        <!-- Gửi lại mã OTP -->
        <div class="mt-4 text-gray-600 text-sm">
          <span>Bạn chưa nhận được mã? </span>
          <template v-if="countdown > 0">
            <span class="text-gray-400">Gửi lại ({{ countdown }}s)</span>
          </template>
          <template v-else>
            <button
              class="!text-primary font-semibold hover:underline"
              :disabled="loading"
              @click="resendOtp"
            >
              <span v-if="!loading">Gửi lại</span>
              <span v-else>Đang gửi...</span>
            </button>
          </template>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, reactive, onMounted, nextTick } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { toast } from 'vue3-toastify'
  import { useUserStore } from '@/store/auth'
  import LogoSplash from '@/assets/images/logo-splash.svg'
  import EmailSent from '@/assets/images/email-sent.svg'
  
  const route = useRoute()
  const router = useRouter()
  const userStore = useUserStore()
  
  const email = ref(route.query.email || '')
  const countdown = ref(60)
  const loading = ref(false)
  const submitting = ref(false)
  const otpDigits = reactive(['', '', '', '', '', ''])
  const otpRefs = ref([])
  
  let timer
  
  const startCountdown = () => {
    clearInterval(timer)
    countdown.value = 60
    timer = setInterval(() => {
      if (countdown.value > 0) countdown.value--
      else clearInterval(timer)
    }, 1000)
  }
  
  const handleInput = (index) => {
    const val = otpDigits[index]
    if (val && index < 5) {
      otpRefs.value[index + 1]?.focus()
    }
  }
  
  const handleBackspace = (index, event) => {
    if (!otpDigits[index] && index > 0) {
      otpRefs.value[index - 1]?.focus()
    }
  }
  
  const resendOtp = async () => {
    loading.value = true
    try {
      await userStore.resendOtpPassword({ email: email.value })
      toast.success('Mã OTP mới đã được gửi.')
      startCountdown()
    } catch (error) {
      toast.error(error.response?.data?.message || 'Gửi lại mã OTP thất bại!')
    } finally {
      loading.value = false
    }
  }
  
  const submitOtp = async () => {
    submitting.value = true
    try {
      const otp = otpDigits.join('')
      if (otp.length < 6) {
        toast.error('Vui lòng nhập đủ 6 chữ số OTP!')
        return
      }
  
      const res = await userStore.verifyOtpPassword({ email: email.value, otp })
      toast.success('Xác minh thành công')
      setTimeout(() => {
        router.push({ path: '/reset-password', query: { email: email.value } })
      }, 1500)
    } catch (error) {
      toast.error(error.response?.data?.message || 'Mã OTP không hợp lệ!')
      otpDigits.fill('')
      await nextTick(() => otpRefs.value[0]?.focus())
    } finally {
      submitting.value = false
    }
  }
  
  onMounted(() => {
    startCountdown()
    nextTick(() => otpRefs.value[0]?.focus())
  })
  </script>
  
  <style scoped>
  @keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
  }
  .animate-bounce {
    animation: bounce 1.4s infinite ease-in-out both;
  }
  </style>
  