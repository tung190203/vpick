<script setup>
import { reactive, computed } from 'vue'
import useVuelidate from '@vuelidate/core'
import { required, email, minLength, sameAs } from '@vuelidate/validators'

import { useRouter } from 'vue-router'
import Button from '@/components/atoms/Button.vue'
import { useUserStore } from '@/store/auth'
import { toast } from 'vue3-toastify'

const router = useRouter()
const userStore = useUserStore()

const data = reactive({
  full_name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const rules = computed(() => ({
  full_name: { required },
  email: { required, email },
  password: { required, minLength: minLength(6) },
  password_confirmation: { required, sameAsPassword: sameAs(data.password) }
}))

const v$ = useVuelidate(rules, data)

const register = async () => {
  v$.value.$touch()
  if (!v$.value.$invalid) {
    try {
      await userStore.registerUser(data)
      toast.success('Đăng ký thành công!')
      setTimeout(() => {
        router.push({ path: '/verify', query: { email: data.email } })
      }, 1000)
    } catch (error) {
      const message = error.response?.data?.message || 'Đăng ký thất bại, vui lòng thử lại!'
      toast.error(`${message}`)
      console.error('Đăng ký thất bại:', error)
    }
  }
}

const registerWithGoogle = () => {
  window.location.href = import.meta.env.VITE_BASE_URL + '/auth/google/redirect'
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md p-8 bg-white rounded shadow">
      <h2 class="text-2xl font-bold mb-6 text-center">Đăng ký</h2>

      <form @submit.prevent="register" class="space-y-4">
        <div>
          <input
            type="text"
            placeholder="Họ và tên"
            v-model="data.full_name"
            class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="v$.full_name.$error" class="text-sm text-red-500 mt-1">
            Họ và tên không được để trống
          </p>
        </div>

        <div>
          <input
            type="email"
            placeholder="Email"
            v-model="data.email"
            class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="v$.email.$error" class="text-sm text-red-500 mt-1">
            {{ v$.email.email.$invalid ? 'Email không hợp lệ' : 'Email không được để trống' }}
          </p>
        </div>

        <div>
          <input
            type="password"
            placeholder="Mật khẩu"
            v-model="data.password"
            class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="v$.password.$error" class="text-sm text-red-500 mt-1">
            {{ v$.password.minLength.$invalid ? 'Mật khẩu tối thiểu 6 ký tự' : 'Mật khẩu không được để trống' }}
          </p>
        </div>

        <div>
          <input
            type="password"
            placeholder="Nhập lại mật khẩu"
            v-model="data.password_confirmation"
            class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="v$.password_confirmation.$error" class="text-sm text-red-500 mt-1">
            {{ v$.password_confirmation.sameAsPassword.$invalid ? 'Mật khẩu nhập lại không khớp' : 'Vui lòng xác nhận mật khẩu' }}
          </p>
        </div>

        <Button type="submit" class="w-full">Đăng ký</Button>
      </form>

      <div class="text-center my-4 text-sm text-gray-500">Hoặc</div>

      <Button
        class="w-full flex items-center justify-center gap-2 bg-white !text-gray-800 border border-gray-300 hover:bg-gray-50"
        @click="registerWithGoogle"
      >
        <img src="@/assets/images/google-icon.svg" class="w-5 h-5" alt="Google" />
        Đăng ký bằng Google
      </Button>

      <div class="text-sm mt-4 text-center">
        Đã có tài khoản?
        <router-link to="/login" class="text-blue-600 hover:underline">
          Đăng nhập ngay
        </router-link>
      </div>
    </div>
  </div>
</template>
