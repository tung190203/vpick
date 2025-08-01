<template>
    <div>
      <Header />
      <main class="custom-height">
        <router-view />
      </main>
      <Footer />
    </div>
  </template>
  
  <script setup>
  import Header from '@/components/organisms/Header.vue'
  import Footer from '../components/organisms/Footer.vue';
  </script>

  <style scoped>
  .custom-height {
    min-height: calc(100vh - 141px);
  }
  </style>
  