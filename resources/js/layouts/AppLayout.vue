<template>
    <div>
      <Header />
      <main class="p-4">
        <router-view />
      </main>
    </div>
  </template>
  
  <script setup>
  import Header from '@/components/organisms/Header.vue'
  </script>
  