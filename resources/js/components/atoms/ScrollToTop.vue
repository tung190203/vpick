<template>
    <button
      v-if="showButton"
      @click="scrollToTop"
      class="fixed bottom-6 right-6 z-50 p-3 rounded-full bg-black text-white shadow-lg hover:bg-gray-800 transition duration-300"
      aria-label="Scroll to top"
    >
      <!-- SVG Arrow Up -->
      <ArrowUpIcon class="w-6 h-6" />
    </button>
  </template>
  
  <script setup>
  import { ref, onMounted, onUnmounted } from 'vue'
  import { ArrowUpIcon } from '@heroicons/vue/24/solid'
  
  const showButton = ref(false)
  
  const handleScroll = () => {
    showButton.value = window.scrollY > 300
  }
  
  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
  
  onMounted(() => {
    window.addEventListener('scroll', handleScroll)
  })
  
  onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
  })
  </script>
  