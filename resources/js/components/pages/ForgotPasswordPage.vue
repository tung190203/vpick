<script setup>
import { reactive, ref, computed } from 'vue'
import useVuelidate from '@vuelidate/core'
import { required, email, helpers } from '@vuelidate/validators'
import Button from '@/components/atoms/Button.vue'
import { toast } from 'vue3-toastify'
import { useUserStore } from '@/store/auth'

const userStore = useUserStore()

const data = reactive({
  email: ''
})

const rules = computed(() => ({
  email: {
    required: helpers.withMessage('Email không được để trống', required),
    email: helpers.withMessage('Email không đúng định dạng', email)
  }
}))

const v$ = useVuelidate(rules, data)

const countdown = ref(0)
const loading = ref(false)
let timer = null

const startCountdown = () => {
  countdown.value = 60
  timer = setInterval(() => {
    if (countdown.value > 0) countdown.value--
    else clearInterval(timer)
  }, 1000)
}

const submit = async () => {
  v$.value.$touch()
  if (!v$.value.$invalid && countdown.value === 0 && !loading.value) {
    loading.value = true
    try {
      await userStore.forgotPassword(data)
      toast.success('Vui lòng kiểm tra email để đặt lại mật khẩu.')
      startCountdown()
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể gửi yêu cầu.')
    } finally {
      loading.value = false
    }
  }
}
</script>
<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md p-8 bg-white rounded shadow">
      <h2 class="text-2xl font-bold mb-6 text-center ">Quên mật khẩu</h2>

      <p class="text-sm text-gray-600 text-center mb-4">
        Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu.
      </p>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <input
            type="email"
            placeholder="Email"
            v-model="data.email"
            class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-1 focus:ring-primary"
          />
          <span
            v-for="err in v$.email.$errors"
            :key="err.$uid"
            class="text-red-500 text-sm"
          >
            {{ err.$message }}
          </span>
        </div>

        <button
          type="submit"
          :disabled="countdown > 0 || loading"
          class="w-full py-2 px-4 bg-primary hover:bg-secondary text-white font-semibold rounded disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="countdown === 0 && !loading">Gửi yêu cầu</span>
          <span v-else-if="loading">Đang gửi...</span>
          <span v-else>Gửi lại sau {{ countdown }}s</span>
        </button>
      </form>

      <div class="text-sm mt-4 text-center">
        <router-link to="/login" class="text-blue-600 hover:underline">
          Quay lại đăng nhập
        </router-link>
      </div>
    </div>
  </div>
</template>
