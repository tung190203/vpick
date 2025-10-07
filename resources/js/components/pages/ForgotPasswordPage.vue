<script setup>
import { reactive, computed } from 'vue'
import useVuelidate from '@vuelidate/core'
import { required, email, helpers } from '@vuelidate/validators'
import Button from '@/components/atoms/Button.vue'
import { toast } from 'vue3-toastify'
import { useUserStore } from '@/store/auth'
import { useRouter } from 'vue-router'

const userStore = useUserStore()
const router = useRouter()

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

const submit = async () => {
  v$.value.$touch()
  if (!v$.value.$invalid) {
    try {
      await userStore.forgotPassword(data)
      toast.success('Yêu cầu đã được gửi. Vui lòng kiểm tra email của bạn.')
      setTimeout(() => {
        router.push({path: '/verify-change-password', query: { email: data.email }} )
      }, 2000)
    } catch (error) {
      toast.error(error.response?.data?.message || 'Không thể gửi yêu cầu.')
    }
  }
}
</script>
<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4">
    <img src="@/assets/images/logo-splash.svg" class="w-[60%]" alt="">
    <div class="text-center mb-8 mt-8">
      <h1 class="text-white text-2xl mb-2">Quên mật khẩu</h1>
      <p class="text-sm text-white font-light">
        Tận hưởng toàn bộ tính năng của Pickleball, bao gồm cập nhật giải đấu, 
        bảng xếp hạng và thông báo trận đấu độc quyền!
      </p>
    </div>
    <div class="w-full max-w-md p-8 bg-white rounded shadow">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label for="email" class="form-in font-semibold text-[14px]">Email</label>
          <input
            id="email"
            type="email"
            placeholder="Email"
            v-model="data.email"
            class="w-full px-4 py-2 mt-1 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder:text-sm"
          />
          <span
            v-for="err in v$.email.$errors"
            :key="err.$uid"
            class="text-red-500 text-sm"
          >
            {{ err.$message }}
          </span>
        </div>

        <Button 
          type="submit" 
          :class="{
            'w-full bg-primary hover:bg-secondary': data.email,
            'w-full bg-[#edeef2] text-[#333333] hover:bg-[#edeefe]': !data.email
          }"
        >
          Gửi yêu cầu
        </Button>
      </form>

      <div class="text-sm mt-4 text-center">
        <router-link to="/login" class="text-[#4392E0] hover:underline">
          Quay lại đăng nhập
        </router-link>
      </div>
    </div>
  </div>
</template>
