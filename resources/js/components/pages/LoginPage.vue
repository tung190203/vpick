<script setup>
import { reactive, computed } from 'vue'
import useVuelidate from '@vuelidate/core'
import { required, minLength, helpers } from '@vuelidate/validators'
import Button from '@/components/atoms/Button.vue'
import { useUserStore } from '@/store/auth'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import { storeToRefs } from 'pinia'

const router = useRouter()
const userStore = useUserStore()
const { getRole: userRole } = storeToRefs(userStore);

const data = reactive({
  login: '',
  password: ''
})

const isEmailOrPhone = helpers.withMessage(
  'Vui lòng nhập email hoặc số điện thoại hợp lệ',
  value => {
    if (!value) return true
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    const phoneRegex = /^0\d{9,10}$/
    return emailRegex.test(value) || phoneRegex.test(value)
  }
)

const rules = computed(() => ({
  login: {
    required: helpers.withMessage('Không được để trống', required),
    isEmailOrPhone
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
      const res = await userStore.loginUser(data)
      toast.success('Đăng nhập thành công!')
      if(res && res?.user?.is_profile_completed == 0) {
        setTimeout(() => {
          router.push({ path: '/complete-profile' })
        }, 1000)
        return
      }
      const roleRouteMap = {
        user: 'dashboard',
        admin: 'admin.dashboard',
        referee: 'referee.dashboard'
      }
      const defaultRouteName = roleRouteMap[userRole.value] || 'dashboard'

      const redirectPath =
        router.currentRoute.value.query.redirect
          ? router.currentRoute.value.query.redirect
          : { name: defaultRouteName }
      setTimeout(() => {
        router.push(redirectPath)
      }, 1000)
    } catch (error) {
      toast.error(error.response?.data?.message || 'Đăng nhập thất bại!')
      if(error.response?.data?.errors?.status_code === "PASSWORD_PENDING") {
        setTimeout(() => {
          router.push({ path: '/complete-registration', query: { login: data.login } })
        }, 1000)
      }
      if(error.response?.data?.errors?.status_code === "OTP_PENDING") {
        setTimeout(() => {
          router.push({ path: '/verify', query: { login: data.login } })
        }, 1000)
      }
    }
  }
}

const loginWithGoogle = () => {
  window.location.href = import.meta.env.VITE_BASE_URL + '/auth/google/redirect'
}

const loginWithFacebook = () => {
  window.location.href = import.meta.env.VITE_BASE_URL + '/auth/facebook/redirect'
}
const loginWithApple = () => {
  window.location.href = import.meta.env.VITE_BASE_URL + '/auth/apple/redirect'
}
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4">
    <img src="@/assets/images/logo-splash.svg" class="w-[60%]" alt="">
    <div class="text-center mb-8 mt-4">
      <h1 class="text-white text-2xl mb-2">Đăng nhập/Đăng ký</h1>
      <p class="text-sm text-white font-light">Tận hưởng toàn bộ tính năng của Pickleball, bao gồm cập nhật giải đấu, 
        bảng xếp hạng và thông báo trận đấu độc quyền!</p>
    </div>
    <div class="w-full max-w-md p-8 bg-white rounded-[12px] shadow">
      <form @submit.prevent="login" class="space-y-4">
        <div>
          <label for="login" class="form-in font-semibold text-[14px]">Email</label>
          <input
            id="login"
            type="text"
            placeholder="Nhập email của bạn"
            v-model="data.login"
            class="w-full px-4 py-2 my-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder:text-sm"
          />
          <span
            v-for="err in v$.login.$errors"
            :key="err.$uid"
            class="text-red-500 text-sm"
          >
            {{ err.$message }}
          </span>
        </div>

        <div>
          <label for="password" class="form-in font-semibold text-[14px]">Mật khẩu</label>
          <input
            id="password"
            type="password"
            placeholder="Nhập mật khẩu của bạn"
            v-model="data.password"
            class="w-full px-4 py-2 mt-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder:text-sm"
          />
          <span
            v-for="err in v$.password.$errors"
            :key="err.$uid"
            class="text-red-500 text-sm"
          >
            {{ err.$message }}
          </span>
        </div>
        <div class="text-right text-sm text-[#4392E0] hover:underline">
          <router-link to="/forgot-password">Quên mật khẩu?</router-link>
        </div>
        <Button 
          type="submit" 
          :class="{
            'w-full !bg-primary hover:!bg-secondary': data.login && data.password,
            'w-full !bg-[#edeef2] !text-[#333333] hover:!bg-[#edeefe]': !(data.login && data.password)
          }"
        >
          Tiếp tục
        </Button>
      </form>

      <div class="flex items-center my-4 text-sm text-gray-500">
        <div class="flex-grow border-t border-gray-300"></div>
        <span class="px-4">Hoặc đăng nhập với</span>
        <div class="flex-grow border-t border-gray-300"></div>
      </div>

      <div class="flex gap-2">
        <Button
        class="w-full flex items-center justify-center gap-2 bg-white !text-gray-800 border border-gray-300 hover:bg-gray-50"
        @click="loginWithGoogle"
      >
        <img src="@/assets/images/google-icon.svg" class="w-5 h-5" alt="Google" />
        <p class="text-sm">Google</p>
      </Button>
      <Button
        class="w-full flex items-center justify-center gap-2 bg-white !text-gray-800 border border-gray-300 hover:bg-gray-50"
        @click="loginWithFacebook"
      >
        <img src="@/assets/images/facebook-icon.svg" class="w-5 h-5" alt="Google" />
        <p class="text-sm">Facebook</p>
      </Button>
      <Button
        class="w-full flex items-center justify-center gap-2 bg-white !text-gray-800 border border-gray-300 hover:bg-gray-50"
        @click="loginWithApple"
      >
        <img src="@/assets/images/apple-icon.svg" class="w-5 h-5" alt="Google" />
        <p class="text-sm">Apple</p>
      </Button>
      </div>

      <div class="text-sm mt-[40px] text-center">
        Chưa có tài khoản?
        <router-link to="/register" class="text-[#4392E0] hover:underline">
          Đăng ký ngay
        </router-link>
      </div>
    </div>
  </div>
</template>
