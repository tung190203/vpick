<template>
    <transition name="fade">
      <div
        v-if="visible"
        class="splash-bg fixed inset-0 flex items-center justify-center z-50"
      >
        <div class="flex items-center p-6 rounded-2xl">
          <img
            src="@/assets/images/logo-splash.svg"
            alt="VPICK Logo"
            class="animate-pulse"
          />
        </div>
      </div>
    </transition>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  
  const visible = ref(true)
  
  onMounted(() => {
    setTimeout(() => {
      visible.value = false
    }, 1000)
  });
  </script>
  
  <style scoped>
  .splash-bg {
    background-image: url('@/assets/images/splash-bg.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  }
  
  .fade-enter-active,
  .fade-leave-active {
    transition: opacity 0.7s ease;
  }
  .fade-enter-from,
  .fade-leave-to {
    opacity: 0;
  }
  </style>
  