<template>
  <footer class="bg-gradient-red text-gray-700 text-sm border-t">
    <div class="max-w-7xl mx-auto px-4 py-8 grid gap-6 md:grid-cols-3 text-left">
      <!-- Logo + Description -->
      <div>
        <h2 class="text-xl font-bold text-primary mb-2">
          <img src="@/assets/images/logo.png" class="w-32" alt="">
        </h2>
        <p class="text-white">
          Nền tảng quản lý và tổ chức giải đấu Pickleball hàng đầu tại Việt Nam. Kết nối cộng đồng và phát triển phong trào thể thao.
        </p>
      </div>

      <!-- Quick Links -->
      <div>
        <h3 class="font-semibold mb-2 text-white">Liên kết</h3>
        <ul class="space-y-1">
          <li><a href="/about" class="text-white hover:underline">Về chúng tôi</a></li>
          <li><a href="/terms" class="text-white hover:underline">Điều khoản sử dụng</a></li>
          <li><a href="/contact" class="text-white hover:underline">Liên hệ</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div>
        <h3 class="font-semibold text-white mb-2">Liên hệ</h3>
        <p class="text-white">Email: 
          <a href="mailto:support@vpick.vn" class="text-white hover:underline">support@vpick.vn</a>
        </p>
        <p class="text-white">Hotline: <span class="font-medium text-white">1900 9999</span></p>
        <p class="mt-2 text-white">© 2025 VPick. All rights reserved.</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
// Nothing needed here
</script>
