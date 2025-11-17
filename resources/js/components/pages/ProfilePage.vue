<template>
    <div class="p-4 max-w-6xl mx-auto">
        <div class="max-w-7xl mx-auto">
            <!-- Background Banner -->
            <div class="relative w-full h-[270px] rounded-[8px] shadow-lg text-white"
                :style="{ backgroundImage: `url(${Background})`, backgroundSize: 'cover', backgroundPosition: 'center' }">

                <!-- Avatar + Name -->
                <div class="absolute -bottom-32 left-10 flex flex-col items-start z-20">
                    <div class="relative">
                        <div class="w-[120px] h-[120px] rounded-full overflow-hidden border-2 border-white shadow-md">
                            <img src="https://placehold.co/600x400" alt="User Avatar"
                                class="w-full h-full object-cover" />
                        </div>

                        <button class="absolute bottom-1 right-1 bg-[#4392E0] rounded-full p-2 cursor-pointer shadow">
                            <PencilIcon class="w-4 h-4 text-white" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2 mt-3">
                        <p class="text-[#3E414C] font-semibold text-2xl">Nguyễn Văn A</p>
                        <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">
                            Friend-Only
                        </span>
                    </div>

                    <div class="text-sm text-[#6B6F80] mt-1 flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <img :src="maleIcon" alt="" class="w-5 h-5">
                            <p>Nam</p>
                        </div>
                        <div>• Người lớn</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end items-center mt-4 text-[#4392E0] gap-11 mr-10 mb-28">
                <div class="flex flex-col items-center cursor-pointer">
                    <StarIcon class="w-7 h-7 mb-2" />
                    <span class="text-xs font-semibold">Yêu thích</span>
                </div>
                <div class="flex flex-col items-center cursor-pointer">
                    <CalendarIcon class="w-7 h-7 mb-2" />
                    <span class="text-xs font-semibold">Mời bạn</span>
                </div>
                <div class="flex flex-col items-center cursor-pointer">
                    <ChatBubbleBottomCenterIcon class="w-7 h-7 mb-2" />
                    <span class="text-xs font-semibold">Trò chuyện</span>
                </div>
            </div>

            <!-- Sports -->
            <section>
                <div class="flex items-center justify-start mb-4 mx-10">
                    <h2 class="text-xl font-semibold text-gray-800">Môn thể thao</h2>
                    <div
                        class="flex items-center text-sm text-gray-600 ml-4 cursor-pointer hover:text-gray-800 bg-white p-1.5 rounded-full shadow-md">
                        <PencilIcon class="w-4 h-4" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mx-10 mb-10 items-start">
                    <SportLevelCard v-for="(item, index) in sports" :key="index" :icon="item.icon" :title="item.title"
                        :subtitle="item.subtitle" :dupr="item.dupr" :vndupr="item.vndupr" :selfScore="item.selfScore"
                        :is-open="openIndex === index" @toggle="toggleCard(index)" />
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Background from "@/assets/images/dashboard-bg.svg";
import maleIcon from '@/assets/images/male.svg';
import femaleIcon from '@/assets/images/female.svg';
import { CalendarIcon } from "@heroicons/vue/24/solid";
import { StarIcon, ChatBubbleBottomCenterIcon, PencilIcon } from "@heroicons/vue/24/outline";

import SportLevelCard from "@/components/molecules/SportLevelCard.vue";

const sports = [
    {
        icon: maleIcon,
        title: 'Pickleball',
        subtitle: 'DUPR 2.543',
        dupr: 2.543,
        vndupr: 2.543,
        selfScore: 2.543
    },
    {
        icon: femaleIcon,
        title: 'Cầu lông',
        subtitle: 'Trung bình',
        dupr: 0,
        vndupr: 0,
        selfScore: 0
    }
];

const openIndex = ref(null);

const toggleCard = (index) => {
    openIndex.value = openIndex.value === index ? null : index;
};
</script>

<style scoped>
.bg-red-custom {
    background-size: cover;
    background-position: center;
}
</style>
