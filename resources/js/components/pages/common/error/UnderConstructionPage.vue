<template>
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-gray-50 text-center">
        <!-- Icon / Illustration -->
        <div class="mb-6 w-full max-w-sm mx-auto animate-slideInUp" style="animation-delay: 0.1s;">
            <div ref="lottieContainer" class="w-full h-100"></div>
        </div>

        <h1 class="text-3xl sm:text-4xl font-bold text-gray-800 mb-3">
            Tính năng đang phát triển
        </h1>
        <p class="text-lg text-gray-600 max-w-lg mb-8">
            Chúng tôi đang làm việc chăm chỉ để mang đến tính năng này cho bạn. Hãy quay lại sau nhé!
        </p>

        <div class="flex flex-col sm:flex-row gap-4">
            <router-link to="/"
                class="inline-flex items-center justify-center px-8 py-3 text-base font-semibold rounded-lg shadow-md transition-colors 
                 bg-[#D72D36] text-white hover:bg-[#c0252e] focus:outline-none focus:ring-2 focus:ring-[#D72D36] focus:ring-offset-2">
                <HomeIcon class="w-5 h-5 mr-2" />
                Về Trang Chủ
            </router-link>
        </div>

        <div class="mt-8 text-sm text-gray-500">
             Cần hỗ trợ?
            <a href="#" class="text-[#207AD5] hover:underline font-medium transition-colors">
                Liên hệ chúng tôi
            </a>
        </div>
    </div>
</template>

<script setup>
import { HomeIcon } from '@heroicons/vue/24/solid';
import lottie from 'lottie-web';
import animationData from '@/assets/lottie/under-maintenance-animation.json';
import { ref, onMounted } from 'vue';
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
});
</script>
