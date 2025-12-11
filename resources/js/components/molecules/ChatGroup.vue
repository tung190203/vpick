<template>
    <div class="fixed bottom-6 right-6 flex flex-col items-center space-y-3 position-absolute z-[9999]">
        <div class="relative cursor-pointer">
            <img src="https://i.pravatar.cc/100?img=1" alt="avatar1"
                class="w-12 h-12 rounded-full border-white shadow-md" />
            <button
                class="absolute -top-1.5 -right-1.5 bg-gray-300 hover:bg-gray-400 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs shadow">
                <XMarkIcon class="w-3 h-3" />
            </button>
            <!-- Badge online -->
            <span class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border border-white rounded-full"></span>
        </div>

        <!-- Avatar 2 -->
        <div class="relative cursor-pointer">
            <img src="https://i.pravatar.cc/100?img=2" alt="avatar2"
                class="w-12 h-12 rounded-full border-white shadow-md" />
            <span class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border border-white rounded-full"></span>
        </div>

        <!-- Nút bút chì -->
        <button class="bg-gray-100 hover:bg-gray-200 w-12 h-12 rounded-full shadow-lg flex items-center justify-center">
            <PencilIcon class="w-5 h-5 text-gray-500" />
        </button>
    </div>
</template>
<script setup>
import { PencilIcon, XMarkIcon } from "@heroicons/vue/24/outline";
</script>