<template>
  <div>
    <SplashScreen v-if="showSplash" />
    <router-view v-else />
    <ScrollToTop />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ScrollToTop from './components/atoms/ScrollToTop.vue'
import SplashScreen from './components/atoms/SplashScreen.vue'

const showSplash = ref(true)

onMounted(() => {
  setTimeout(() => {
    showSplash.value = false
  }, 1000)
});
</script>
