<script setup>
import Button from '@/components/atoms/Button.vue'
import { useRouter } from 'vue-router'
import { LOCAL_STORAGE_KEY } from "@/constants/index.js"

const router = useRouter()

function goRegister() {
  localStorage.setItem(LOCAL_STORAGE_KEY.ONBOARDING, 'true')
  router.push('/register')
}

function goLogin() {
  localStorage.setItem(LOCAL_STORAGE_KEY.ONBOARDING, 'true')
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4">
    <img src="@/assets/images/logo-splash.svg" class="w-[60%]" alt="Logo">
    <div class="text-center mb-8 mt-4">
      <h1 class="text-white text-2xl mb-2">THAM GIA CỘNG ĐỒNG PICKERBALL NGAY TRÊN PICKI</h1>
      <p class="text-sm text-white font-light">
        Tận hưởng toàn bộ tính năng của Pickleball, bao gồm cập nhật giải đấu, 
        bảng xếp hạng và thông báo trận đấu độc quyền!
      </p>
    </div>
    <div class="w-full max-w-md space-y-4">
      <Button class="w-full bg-white !text-[#333333] hover:bg-secondary hover:text-white" @click="goRegister">
        Đăng ký
      </Button>
      <Button class="w-full bg-secondary hover:bg-white hover:text-[#333333]" @click="goLogin">
        Đăng nhập
      </Button>
    </div>
  </div>
</template>
