<script setup>
import { reactive, computed } from 'vue'
import useVuelidate from '@vuelidate/core'
import { required, email, minLength, helpers } from '@vuelidate/validators'

import Button from '@/components/atoms/Button.vue'
import { useUserStore } from '@/store/auth'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'

const router = useRouter()
const userStore = useUserStore()

const data = reactive({
  email: '',
  password: ''
})

const rules = computed(() => ({
  email: {
    required: helpers.withMessage('Email không được để trống', required),
    email: helpers.withMessage('Email không đúng định dạng', email)
  },
  password: {
    required: helpers.withMessage('Mật khẩu không được để trống', required),
    minLength: helpers.withMessage('Mật khẩu tối thiểu 6 ký tự', minLength(6))
  }
}))

const v$ = useVuelidate(rules, data)

const login = async () => {
  v$.value.$touch()
  if (!v$.value.$invalid) {
    try {
      await userStore.loginUser(data)
      toast.success('Đăng nhập thành công!')
      setTimeout(() => {
        router.push({ name: 'dashboard' })
      }, 1000)
    } catch (error) {
      toast.error(error.response?.data?.message || 'Đăng nhập thất bại!')
    }
  }
}

const loginWithGoogle = () => {
  window.location.href = import.meta.env.VITE_BASE_URL + '/auth/google/redirect'
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md p-8 bg-white rounded shadow">
      <h2 class="text-2xl font-bold mb-6 text-center">Đăng nhập</h2>

      <form @submit.prevent="login" class="space-y-4">
        <div>
          <input
            type="email"
            placeholder="Email"
            v-model="data.email"
            class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <span
            v-for="err in v$.email.$errors"
            :key="err.$uid"
            class="text-red-500 text-sm"
          >
            {{ err.$message }}
          </span>
        </div>

        <div>
          <input
            type="password"
            placeholder="Mật khẩu"
            v-model="data.password"
            class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <span
            v-for="err in v$.password.$errors"
            :key="err.$uid"
            class="text-red-500 text-sm"
          >
            {{ err.$message }}
          </span>
        </div>

        <Button type="submit" class="w-full">Đăng nhập</Button>
      </form>

      <div class="text-center my-4 text-sm text-gray-500">Hoặc</div>

      <Button
        class="w-full flex items-center justify-center gap-2 bg-white !text-gray-800 border border-gray-300 hover:bg-gray-50"
        @click="loginWithGoogle"
      >
        <img src="@/assets/images/google-icon.svg" class="w-5 h-5" alt="Google" />
        Đăng nhập bằng Google
      </Button>

      <div class="text-sm mt-4 text-center">
        Chưa có tài khoản?
        <router-link to="/register" class="text-blue-600 hover:underline">
          Đăng ký ngay
        </router-link>
      </div>
    </div>
  </div>
</template>
