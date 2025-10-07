<template>
    <div
      class="min-h-screen flex items-center justify-center bg-cover bg-center relative"
      :style="{ backgroundImage: `url(${authBg})` }"
    >
        <div class="w-full max-w-md rounded">
      <router-view />
    </div>
    </div>
  </template>
  
  <script setup>
  import authBg from '@/assets/images/splash-bg.png'
  </script>
  
  <style scoped>
  .bg-cover {
    background-size: cover;
    background-position: center;
  }
  </style>
  