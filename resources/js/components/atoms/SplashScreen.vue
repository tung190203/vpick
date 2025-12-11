<template>
  <transition name="fade">
    <div v-if="visible" class="splash-bg fixed inset-0 flex items-center justify-center z-50">
      <div class="flex items-center p-6 rounded-2xl">
        <div ref="lottieContainer" class="lottie-box"></div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import lottie from 'lottie-web';
import animationData from '@/assets/lottie/splash-logo-animation.json';

const visible = ref(true)
const lottieContainer = ref(null);

onMounted(() => {
  if (lottieContainer.value) {
    lottie.loadAnimation({
      container: lottieContainer.value,
      renderer: 'svg',
      loop: true,
      autoplay: true,
      animationData: animationData
    });
  }
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

.lottie-box {
  transform: scale(1);
  transform-origin: center;
}

@media (min-width: 640px) {
  .lottie-box {
    transform: scale(1.0);
  }
}

@media (min-width: 1024px) {
  .lottie-box {
    transform: scale(0.5);
  }
}
</style>