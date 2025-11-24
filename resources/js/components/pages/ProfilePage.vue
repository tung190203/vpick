<template>
    <div class="p-4 max-w-6xl mx-auto">
        <div class="max-w-7xl mx-auto h-screen">
            <!-- Background Banner -->
            <div class="relative w-full h-[270px] rounded-[8px] shadow-lg text-white cursor-pointer"
                :style="backgroundStyle">
                <!-- Button Update Thumbnail -->
                <button 
                    v-if="isOwner"
                    class="absolute bottom-4 right-4 text-black bg-white rounded-md px-4 py-2 cursor-pointer shadow-md"
                    @click="triggerThumbnailPicker">
                     {{ user.thumbnail == null ? 'Thêm ảnh bìa' : 'Thay ảnh bìa' }}
                </button>
                <!-- INPUT FILE ẨN Thumbnail -->
                <input type="file" accept="image/*" ref="thumbnailInput" class="hidden"
                    @change="handleThumbnailChange" />

                <!-- Avatar + Name -->
                <div class="absolute -bottom-32 left-10 flex flex-col items-start z-20">
                    <div class="relative">
                        <div class="w-[120px] h-[120px] rounded-full overflow-hidden border-2 border-white shadow-md"
                            :class="{ 'cursor-pointer': isOwner }"
                            @click="isOwner && triggerAvatarPicker()">
                            <img :src="user.avatar_url || defaultAvatar" alt="User Avatar"
                                class="w-full h-full object-cover" />
                        </div>

                        <!-- Button Update Avatar -->
                        <button 
                            v-if="isOwner"
                            class="absolute bottom-1 right-1 bg-[#4392E0] rounded-full p-2 cursor-pointer shadow"
                            @click="triggerAvatarPicker">
                            <PencilIcon class="w-4 h-4 text-white" />
                        </button>

                        <!-- INPUT FILE ẨN Avatar -->
                        <input type="file" accept="image/*" ref="avatarInput" class="hidden"
                            @change="handleAvatarChange" />
                    </div>

                    <div class="flex items-center gap-2 mt-3">
                        <p class="text-[#3E414C] font-semibold text-2xl">{{ user.full_name ?? 'Không rõ' }}</p>

                        <div v-if="isOwner" class="relative" v-click-outside="closeVisibilityMenu">
                            <span class="px-2 py-1 rounded text-xs font-medium capitalize cursor-pointer" :class="{
                                'bg-green-100 text-green-700': user.visibility === 'open',
                                'bg-yellow-100 text-yellow-700': user.visibility === 'friend-only',
                                'bg-red-100 text-red-700': user.visibility === 'private'
                            }" @click="toggleVisibilityMenu">
                                {{ user.visibility }}
                            </span>
                            <!-- Dropdown Menu -->
                            <div v-if="showVisibilityMenu"
                                class="absolute top-full mt-2 left-0 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-30 min-w-[150px]">
                                <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm text-green-700 font-medium"
                                    @click="changeVisibility('open')">
                                    Open
                                </div>
                                <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm text-yellow-700 font-medium"
                                    @click="changeVisibility('friend-only')">
                                    Friend-only
                                </div>
                                <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm text-red-700 font-medium"
                                    @click="changeVisibility('private')">
                                    Private
                                </div>
                            </div>
                        </div>
                        <span 
                            v-else
                            class="px-2 py-1 rounded text-xs font-medium capitalize" 
                            :class="{
                                'bg-green-100 text-green-700': user.visibility === 'open',
                                'bg-yellow-100 text-yellow-700': user.visibility === 'friend-only',
                                'bg-red-100 text-red-700': user.visibility === 'private'
                            }">
                            {{ user.visibility }}
                        </span>
                    </div>

                    <div class="text-sm text-[#6B6F80] mt-1 flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <img v-if="user.gender === 1" :src="maleIcon" class="w-5 h-5" alt="" />
                            <img v-else-if="user.gender === 2" :src="femaleIcon" class="w-5 h-5" alt="" />
                            <ExclamationCircleIcon v-else-if="user.gender === 3" class="w-5 h-5" />
                            <QuestionMarkCircleIcon v-else-if="user.gender === 0" class="w-5 h-5" />
                            <p>{{ user.gender_text }}</p>
                        </div>
                        <div v-if="user.age_group">• {{ user.age_group }}</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end items-center mt-4 text-[#4392E0] gap-11 mr-10 mb-28"
                :class="{ invisible: isOwner }">
                <div class="flex flex-col items-center cursor-pointer">
                    <StarIcon v-if="user.is_followed == false" class="w-7 h-7 mb-2" />
                    <StarIconSolid v-else class="w-7 h-7 mb-2" />
                    <span class="text-xs font-semibold">{{ user.is_followed ? 'Đã yêu thích' : 'Yêu thích' }}</span>
                </div>
                <div class="flex flex-col items-center cursor-pointer">
                    <CalendarIcon class="w-7 h-7 mb-2" />
                    <span class="text-xs font-semibold">Mời bạn</span>
                </div>
                <div class="flex flex-col items-center cursor-pointer">
                    <ChatBubbleBottomCenterTextIcon class="w-7 h-7 mb-2" />
                    <span class="text-xs font-semibold">Trò chuyện</span>
                </div>
            </div>

            <!-- Sports -->
            <section>
                <div class="flex items-center justify-start mb-4 mx-10">
                    <h2 class="text-xl font-semibold text-gray-800">Môn thể thao</h2>
                    <div
                        v-if="isOwner"
                        class="flex items-center text-sm text-gray-600 ml-4 cursor-pointer hover:text-gray-800 bg-white p-1.5 rounded-full shadow-md"
                        @click="openSportModal">
                        <PencilIcon class="w-4 h-4" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mx-10 mb-10 items-start">
                    <SportLevelCard v-for="(item, index) in mappedSports" :key="index"
                        :icon="item.sport_icon || '/images/basketball.png'" :title="item.sport_name"
                        :subtitle="item.dupr == 0 ? 'VNDUPR ' + item.vndupr : 'DUPR ' + item.dupr"
                        :selfScore="item.selfScore" :dupr="item.dupr" :vndupr="item.vndupr"
                        :is-open="openIndex === index" @toggle="toggleCard(index)" />
                </div>
            </section>

            <!-- Clubs -->
            <section>
                <div class="flex items-center justify-start mb-4 mx-10">
                    <h2 class="text-xl font-semibold text-gray-800">Cộng đồng</h2>
                    <div
                        v-if="isOwner"
                        class="flex items-center text-sm text-gray-600 ml-4 cursor-pointer hover:text-gray-800 bg-white p-1.5 rounded-full shadow-md">
                        <PencilIcon class="w-4 h-4" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mx-10 mb-10 items-start">
                    <div v-for="club in clubs" :key="club.id"
                        class="flex justify-between hover:bg-gray-100 rounded-md p-4 cursor-pointer">
                        <div class="flex gap-4">
                            <HomeIcon class="w-5 h-5" />
                            <p class="truncate w-40 text-sm text-[#4392E0] font-semibold" v-tooltip="club.name">
                                {{ club.name }}
                            </p>
                        </div>
                        <div class="flex gap-4">
                            <p class="text-[#4392E0] text-xs">Hoạt động</p>
                            <ChevronRightIcon class="w-4 h-4" />
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <SportSelectCard 
            :is-open="showSportModal"
            :my-sports="sports"
            :all-sports="allSports"
            @close="closeSportModal"
            @save="handleSaveSports"
        />
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import Background from "@/assets/images/dashboard-bg.svg";
import maleIcon from '@/assets/images/male.svg';
import femaleIcon from '@/assets/images/female.svg';
import { vClickOutside } from "@/directives/clickOutside";
import * as SportService from '@/service/sport';

import {
    CalendarIcon,
    ChevronRightIcon,
    StarIcon as StarIconSolid
} from "@heroicons/vue/24/solid";

import {
    StarIcon,
    ChatBubbleBottomCenterTextIcon,
    PencilIcon,
    ExclamationCircleIcon,
    QuestionMarkCircleIcon,
    HomeIcon
} from "@heroicons/vue/24/outline";

import SportLevelCard from "@/components/molecules/SportLevelCard.vue";
import SportSelectCard from "@/components/molecules/SportSelectCard.vue";
import { useRoute } from "vue-router";
import { toast } from "vue3-toastify";
import { useUserStore } from "@/store/auth";
import { storeToRefs } from "pinia";

const route = useRoute();
const id = route.params.id;
const user = ref({});
const defaultAvatar = "/images/default-avatar.png";

const userStore = useUserStore();
const { getUser } = storeToRefs(userStore);

const sports = ref([]);
const clubs = ref([]);
const openIndex = ref(null);
const showVisibilityMenu = ref(false);
const showSportModal = ref(false);
const allSports = ref([]);

// Avatar
const avatarInput = ref(null);
const triggerAvatarPicker = () => avatarInput.value?.click();

// Banner Thumbnail
const thumbnailInput = ref(null);
const triggerThumbnailPicker = () => thumbnailInput.value?.click();

const isOwner = computed(() => user.value.id === getUser.value.id);

const mappedSports = computed(() =>
    sports.value.map(s => ({
        ...s,
        selfScore: s.scores.find(x => x.personal_score?.score_value)?.personal_score.score_value ?? 0,
        dupr: s.scores.find(x => x.dupr_score?.score_value)?.dupr_score.score_value ?? 0,
        vndupr: s.scores.find(x => x.vndupr_score?.score_value)?.vndupr_score.score_value ?? 0
    }))
);

const backgroundStyle = computed(() => ({
    backgroundImage: `url('${user.value.thumbnail || Background}')`,
    backgroundSize: 'cover',
    backgroundPosition: 'center'
}));

const toggleCard = (index) => {
    openIndex.value = openIndex.value === index ? null : index;
};

const toggleVisibilityMenu = () => {
    showVisibilityMenu.value = !showVisibilityMenu.value;
};

const closeVisibilityMenu = () => {
    showVisibilityMenu.value = false;
};

const changeVisibility = async (newVisibility) => {
    try {
        const formData = new FormData();
        formData.append("full_name", user.value.full_name);
        formData.append("visibility", newVisibility);

        await updateInfoUser(formData);
        showVisibilityMenu.value = false;
        toast.success("Cập nhật trạng thái hiển thị thành công!");
    } catch (error) {
        toast.error(error.response?.data?.message || "Lỗi khi cập nhật trạng thái");
    }
};

const openSportModal = async () => {
    if (allSports.value.length === 0) {
        try {
            allSports.value = await SportService.getAllSports();
        } catch (error) {
            toast.error('Không thể tải danh sách môn thể thao');
            return;
        }
    }
    showSportModal.value = true;
};

const closeSportModal = () => {
    showSportModal.value = false;
};

const handleSaveSports = async (sportsData) => {
    try {
        const formData = new FormData();
        formData.append("full_name", user.value.full_name);
        sportsData.sport_ids.forEach((id, index) => {
            formData.append(`sport_ids[${index}]`, id);
        });
        sportsData.score_value.forEach((score, index) => {
            formData.append(`score_value[${index}]`, score);
        });
        await updateInfoUser(formData);
        toast.success("Cập nhật môn thể thao thành công!");
    } catch (error) {
        toast.error(error.response?.data?.message || "Lỗi khi cập nhật môn thể thao");
    }
};

// Fetch User
const fetchDetailUser = async (id) => {
    try {
        const res = await userStore.detailUser(id);
        user.value = res || {};

        if (res) {
            sports.value = res.sports || [];
            clubs.value = res.clubs || [];
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi thực hiện thao tác này');
    }
};

// Update User
const updateInfoUser = async (data) => {
    try {
        await userStore.updateUser(data);
        await fetchDetailUser(id);
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra');
    }
};

// Upload Avatar
const handleAvatarChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("full_name", user.value.full_name);
    formData.append("avatar_url", file);

    try {
        await updateInfoUser(formData);
        toast.success("Cập nhật ảnh đại diện thành công!");
    } catch (error) {
        toast.error(error.response?.data?.message || "Lỗi khi cập nhật ảnh đại diện");
    }
};

// Upload Thumbnail (Background)
const handleThumbnailChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("full_name", user.value.full_name);
    formData.append("thumbnail", file);

    try {
        await updateInfoUser(formData);
        toast.success("Cập nhật ảnh bìa thành công!");
    } catch (error) {
        toast.error(error.response?.data?.message || "Lỗi khi cập nhật ảnh bìa");
    }
};

onMounted(async () => {
    if (id) await fetchDetailUser(id);
});
</script>

<style scoped>
.bg-red-custom {
    background-size: cover;
    background-position: center;
}
</style>