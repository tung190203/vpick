<template>
    <div class="bg-gray-50">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div
                    class="lg:col-span-4 h-[86vh] bg-white shadow-lg rounded-md overflow-hidden flex flex-col border border-gray-100">
                    <div class="p-4">
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <MagnifyingGlassIcon
                                    class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2" />
                                <input v-if="activeTab === 'courts'" v-model="searchCourt" placeholder="Tìm sân"
                                    class="w-full pl-9 pr-4 py-1.5 h-9 text-sm border border-gray-300 bg-gray-100 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-gray-400" />

                                <input v-if="activeTab === 'match'" v-model="searchMatch" placeholder="Tìm trận"
                                    class="w-full pl-9 pr-4 py-1.5 h-9 text-sm border border-gray-300 bg-gray-100 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-gray-400" />

                                <input v-if="activeTab === 'players'" v-model="searchUser" placeholder="Tìm người chơi"
                                    class="w-full pl-9 pr-4 py-1.5 h-9 text-sm border border-gray-300 bg-gray-100 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-gray-400" />
                            </div>
                            <button @click="isFilterModalOpen = true"
                                class="p-2 h-9 border border-gray-300 rounded hover:bg-gray-50 flex items-center justify-center flex-shrink-0">
                                <FunnelIcon class="w-5 h-5 text-gray-600" />
                            </button>
                        </div>
                    </div>
                    <div class="px-4 py-3 border-gray-100">
                        <div class="grid grid-cols-3 gap-2">
                            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                                'flex items-center justify-center gap-2 w-full py-2 rounded border text-sm font-medium transition-all',
                                activeTab === tab.id
                                    ? 'border-[#D72D36] text-gray-800 bg-white'
                                    : 'border-gray-300 text-gray-600 bg-white hover:bg-gray-50'
                            ]">
                                <span :class="[
                                    'w-4 h-4 rounded-full border flex items-center justify-center',
                                    activeTab === tab.id
                                        ? 'border-[#D72D36] border-2'
                                        : 'border-gray-400'
                                ]">
                                    <span v-if="activeTab === tab.id" class="w-2 h-2 bg-[#D72D36] rounded-full"></span>
                                </span>
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <div class="px-4 pt-3 pb-2">
                        <template v-if="activeTab === 'courts'">
                            <p class="text-[#D72D36] font-semibold text-sm">{{ courts.length }} Sân bóng được tìm thấy
                            </p>
                        </template>
                        <template v-else-if="activeTab === 'match'">
                            <p class="text-[#D72D36] font-semibold text-sm">{{ matches.length }} Trận đấu được tìm thấy
                            </p>
                        </template>
                        <template v-else-if="activeTab === 'players'">
                            <p class="text-[#D72D36] font-semibold text-sm">{{ users.length }} Người dùng được tìm thấy
                            </p>
                        </template>
                    </div>

                    <div class="flex-1 overflow-y-auto px-4 py-1">
                        <div class="space-y-3">
                            <template v-if="activeTab === 'courts'">
                                <div v-for="court in listData" :key="court.id" @click="focusCourt(court)" :class="[
                                    'border rounded-lg cursor-pointer transition-all overflow-hidden flex h-fit px-2 items-center',
                                    court.id === selectedCourt
                                        ? 'border-blue-500 shadow-md'
                                        : 'border-gray-200 hover:border-gray-300 shadow-md'
                                ]">
                                    <div
                                        class="w-28 h-28 flex-shrink-0 relative overflow-hidden bg-gray-100 rounded-md">
                                        <img :src="court.image || defaultImage"
                                            @error="e => e.target.src = defaultImage"
                                            class="absolute inset-0 w-full h-full object-cover" />
                                    </div>

                                    <div class="flex-1 min-w-0 p-3 flex flex-col justify-start">
                                        <h3 class="font-semibold text-gray-900 text-base leading-tight line-clamp-2"
                                            v-tooltip="court.name">
                                            {{ court.name }}
                                        </h3>
                                        <div class="space-y-2 mt-1 text-sm text-gray-600">
                                            <div class="flex items-center gap-1.5">
                                                <ClockIcon class="w-5 h-5 text-[#4392E0]" />
                                                <span>Giờ mở cửa: {{ toHourMinute(court.opening_time) }}-{{
                                                    toHourMinute(court.closing_time) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <PhoneIcon class="w-5 h-5 text-[#4392E0]" />
                                                <span>{{ court.phone }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <MapPinIcon class="w-5 h-5 text-[#4392E0]" />
                                                <span class="w-[90%] leading-tight line-clamp-1"
                                                    v-tooltip="court.address">{{ court.address }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template v-else-if="activeTab === 'match'">
                                <div v-for="match in listData" :key="match.id"
                                    class="border rounded-lg p-3 cursor-pointer hover:border-gray-300 shadow-md">
                                    <h3 class="font-semibold text-gray-900 text-base leading-tight line-clamp-2">
                                        {{ match }}
                                    </h3>
                                    <!-- Thêm thông tin trận đấu ở đây -->
                                </div>
                            </template>
                            <template v-else-if="activeTab === 'players'">
                                <div v-for="user in listData" :key="user.id" @click="focusUser(user)" :class="[
                                    'border rounded-lg cursor-pointer transition-all overflow-hidden flex h-fit p-2 items-center gap-3',
                                    user.id === selectedUser
                                        ? 'border-blue-500 shadow-md'
                                        : 'border-gray-200 hover:border-gray-300 shadow-md'
                                ]">
                                    <div
                                        class="w-16 h-16 flex-shrink-0 relative overflow-hidden bg-gray-100 rounded-full">
                                        <img :src="user.avatar_url || defaultImage"
                                            @error="e => e.target.src = defaultImage"
                                            class="absolute inset-0 w-full h-full object-cover" />
                                    </div>
                                    <div class="flex-1 min-w-0 flex flex-col justify-start gap-1">
                                        <div class="flex justify-start items-center gap-2">
                                            <h3 class="font-semibold text-gray-900 text-base leading-tight truncate"
                                                v-tooltip="user.full_name">
                                                {{ user.full_name }}
                                            </h3>
                                            <span
                                                class="px-2 py-1 rounded text-xs font-medium capitalize cursor-pointer whitespace-nowrap"
                                                :class="{
                                                    'bg-green-100 text-green-700': user.visibility === 'open',
                                                    'bg-yellow-100 text-yellow-700': user.visibility === 'friend-only',
                                                    'bg-red-100 text-red-700': user.visibility === 'private'
                                                }" @click.stop="toggleVisibilityMenu">
                                                {{ getVisibilityText(user.visibility) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-xs text-gray-600 truncate">
                                            <img v-if="user.gender == 1" :src="maleIcon" alt="male" class="w-4 h-4" />
                                            <img v-else-if="user.gender == 2" :src="femaleIcon" alt="female"
                                                class="w-4 h-4" />
                                            <span class="truncate">
                                                {{ user.gender_text || 'Khác' }}{{ user.age_group ? ' • ' +
                                                user.age_group : '' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 w-1/4">
                                        <p class="text-xs text-[#207AD5] line-clamp-2 break-words"
                                            v-tooltip="user.address">
                                            {{ user.address }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-8 h-[86vh] bg-white shadow-lg rounded-md overflow-hidden p-5">
                    <div id="map" class="w-full h-full"></div>
                </div>
            </div>
        </div>

        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="isFilterModalOpen" @click.self="closeFilterModal"
                class="fixed inset-0 z-[9999] bg-gray-900 bg-opacity-40 backdrop-brightness-90 backdrop-blur-[1px]"
                aria-modal="true" role="dialog">
            </div>
        </Transition>

        <template v-if="activeTab === 'courts'">
            <Transition enter-active-class="transition ease-out duration-300" enter-from-class="translate-x-full"
                enter-to-class="translate-x-0" leave-active-class="transition ease-in duration-200"
                leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                <div v-if="isFilterModalOpen"
                    class="fixed inset-y-0 right-4 z-[10000] w-full max-w-sm h-[95vh] mt-6 bg-white shadow-xl overflow-y-auto transform flex flex-col rounded-md">
                    <div class="px-4 pt-4 flex justify-between items-center sticky top-0 bg-white z-10">
                        <h3 class="text-2xl font-semibold text-gray-900">
                            Trình lọc sân bóng
                        </h3>
                        <button @click="closeFilterModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>
                    <div class="px-4 pb-4 border-b sticky top-0 bg-white z-10">
                        <h3 class="text-xl text-gray-900">
                            Bộ môn thể thao
                        </h3>
                        <div class="mt-4 flex gap-2 font-semibold">
                            <div
                                class="px-6 py-2 rounded-full bg-[#D72D36] border inline-block text-white text-sm cursor-pointer">
                                Bóng đá
                            </div>
                            <div
                                class="px-6 py-2 rounded-full border border-[#BBBFCC] bg-white inline-block text-sm cursor-pointer">
                                Tennis
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 p-4 space-y-6">
                        <div class="flex justify-between items-center">
                            <p class="font-medium text-gray-900">Hiển thị sân bóng tôi theo dõi</p>
                            <button @click="isShowMyFollow = !isShowMyFollow"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="isShowMyFollow ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    :class="isShowMyFollow ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-medium text-gray-900">Xung quanh bạn</p>
                        </div>
                    </div>

                    <div class="p-4 border-t sticky bottom-0 bg-white flex justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <p>Làm mới</p>
                            <ArrowPathIcon class="w-5 h-5 text-[#4392E0] cursor-pointer"
                                :class="{ 'animate-spin-once': spinning }" @click="refresh" />
                        </div>
                        <button @click="applyFilter"
                            class="px-4 py-2 text-sm font-medium text-white bg-[#D72D36] rounded-md hover:bg-[#c22830] transition-colors">
                            Áp dụng Lọc
                        </button>
                    </div>
                </div>
            </Transition>
        </template>
        <template v-else-if="activeTab === 'match'">
            <Transition enter-active-class="transition ease-out duration-300" enter-from-class="translate-x-full"
                enter-to-class="translate-x-0" leave-active-class="transition ease-in duration-200"
                leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                <div v-if="isFilterModalOpen"
                    class="fixed inset-y-0 right-4 z-[10000] w-full max-w-sm h-[95vh] mt-6 bg-white shadow-xl overflow-y-auto transform flex flex-col rounded-md">
                    <div class="px-4 pt-4 flex justify-between items-center sticky top-0 bg-white z-10">
                        <h3 class="text-2xl font-semibold text-gray-900">
                            Trình lọc trận đấu
                        </h3>
                        <button @click="closeFilterModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>
                    <div class="px-4 pb-4 border-b sticky top-0 bg-white z-10">
                        <h3 class="text-xl text-gray-900">
                            Bộ môn thể thao
                        </h3>
                        <div class="mt-4 flex gap-2 font-semibold">
                            <div
                                class="px-6 py-2 rounded-full bg-[#D72D36] border inline-block text-white text-sm cursor-pointer">
                                Bóng đá
                            </div>
                            <div
                                class="px-6 py-2 rounded-full border border-[#BBBFCC] bg-white inline-block text-sm cursor-pointer">
                                Tennis
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 p-4 space-y-6">
                        <div class="flex justify-between items-center">
                            <p class="font-medium text-gray-900">Hiển thị sân bóng tôi theo dõi</p>
                            <button @click="isShowMyFollow = !isShowMyFollow"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="isShowMyFollow ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    :class="isShowMyFollow ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-medium text-gray-900">Xung quanh bạn</p>
                        </div>
                    </div>

                    <div class="p-4 border-t sticky bottom-0 bg-white flex justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <p>Làm mới</p>
                            <ArrowPathIcon class="w-5 h-5 text-[#4392E0] cursor-pointer"
                                :class="{ 'animate-spin-once': spinning }" @click="refresh" />
                        </div>
                        <button @click="applyFilter"
                            class="px-4 py-2 text-sm font-medium text-white bg-[#D72D36] rounded-md hover:bg-[#c22830] transition-colors">
                            Áp dụng Lọc
                        </button>
                    </div>
                </div>
            </Transition>
        </template>
        <template v-else-if="activeTab === 'players'">
            <Transition enter-active-class="transition ease-out duration-300" enter-from-class="translate-x-full"
                enter-to-class="translate-x-0" leave-active-class="transition ease-in duration-200"
                leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                <div v-if="isFilterModalOpen"
                    class="fixed inset-y-0 right-4 z-[10000] w-full max-w-sm h-[95vh] mt-6 bg-white shadow-xl overflow-y-auto transform flex flex-col rounded-md">
                    <div class="px-4 pt-4 flex justify-between items-center sticky top-0 bg-white z-10">
                        <h3 class="text-2xl font-semibold text-gray-900">
                            Trình lọc người chơi
                        </h3>
                        <button @click="closeFilterModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>
                    <div class="px-4 pb-4 border-b sticky top-0 bg-white z-10">
                        <h3 class="text-xl text-gray-900">
                            Bộ môn thể thao
                        </h3>
                        <div class="mt-4 flex gap-2 font-semibold">
                            <div
                                class="px-6 py-2 rounded-full bg-[#D72D36] border inline-block text-white text-sm cursor-pointer">
                                Bóng đá
                            </div>
                            <div
                                class="px-6 py-2 rounded-full border border-[#BBBFCC] bg-white inline-block text-sm cursor-pointer">
                                Tennis
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 p-4 space-y-6">
                        <div class="flex justify-between items-center">
                            <p class="font-medium text-gray-900">Hiển thị sân bóng tôi theo dõi</p>
                            <button @click="isShowMyFollow = !isShowMyFollow"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="isShowMyFollow ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    :class="isShowMyFollow ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-medium text-gray-900">Xung quanh bạn</p>
                        </div>
                    </div>

                    <div class="p-4 border-t sticky bottom-0 bg-white flex justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <p>Làm mới</p>
                            <ArrowPathIcon class="w-5 h-5 text-[#4392E0] cursor-pointer"
                                :class="{ 'animate-spin-once': spinning }" @click="refresh" />
                        </div>
                        <button @click="applyFilter"
                            class="px-4 py-2 text-sm font-medium text-white bg-[#D72D36] rounded-md hover:bg-[#c22830] transition-colors">
                            Áp dụng Lọc
                        </button>
                    </div>
                </div>
            </Transition>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { FunnelIcon, MagnifyingGlassIcon, ClockIcon, PhoneIcon, MapPinIcon, XMarkIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import "leaflet.markercluster";
import { toast } from 'vue3-toastify';
import * as MapService from '@/service/map.js';
import * as UserService from '@/service/auth.js';
import { useTimeFormat } from '@/composables/formatTime.js';
import defaultImage from '@/assets/images/default-image.jpeg'
import maleIcon from '@/assets/images/male.svg';
import femaleIcon from '@/assets/images/female.svg';
import { getVisibilityText } from "@/composables/formatVisibilityText";

const activeTab = ref('courts');
const isShowMyFollow = ref(false);
const selectedCourt = ref(null);
const selectedUser = ref(null);
const courts = ref([]);
const matches = ref([]);
const users = ref([]);
const isFilterModalOpen = ref(false); // TRẠNG THÁI MỚI CHO MODAL
const { toHourMinute } = useTimeFormat();
const spinning = ref(false);
const tabs = [
    { id: 'courts', label: 'Sân bóng' },
    { id: 'match', label: 'Trận đấu' },
    { id: 'players', label: 'Người chơi' }
];

watch(activeTab, (tab) => {
    if (tab === 'courts') getCompetitionLocation()
    if (tab === 'match') getListMatches()
    if (tab === 'players') getListUser()
})

const refresh = async () => {
    if (spinning.value) return;
    spinning.value = true;
    setTimeout(() => {
        spinning.value = false;
    }, 700);
};

const listData = computed(() => {
    if (activeTab.value === 'courts') return courts.value
    if (activeTab.value === 'match') return matches.value
    if (activeTab.value === 'players') return users.value
    return []
})

const getCompetitionLocation = async () => {
    try {
        const res = await MapService.getCourtData();
        courts.value = res.competition_locations;
    } catch (error) {
        console.error("Error fetching map data:", error);
        toast.error(error.response?.data?.message || "Lỗi khi tải dữ liệu sân bóng");
    }
};

const getListUser = async () => {
    try {
        const res = await UserService.getUserData();
        users.value = res.users || [];
    } catch (error) {
        console.error("Error fetching user data:", error);
        toast.error(error.response?.data?.message || "Lỗi khi tải dữ liệu người chơi");
    }
};

const getListMatches = async () => {
    try {
        const res = await MapService.getMatchesData();
        matches.value = res || [];
    } catch (error) {
        console.error("Error fetching match data:", error);
        toast.error(error.response?.data?.message || "Lỗi khi tải dữ liệu trận đấu");
    }
};

const closeFilterModal = () => {
    isFilterModalOpen.value = false;
};

const applyFilter = () => {
    if (activeTab.value === 'courts') getCompetitionLocation()
    if (activeTab.value === 'match') getListMatches()
    if (activeTab.value === 'players') getListUser()
    isFilterModalOpen.value = false;
};

let map;
let markers = {};
let markerClusterGroup;

onMounted(async () => {
    await getCompetitionLocation();

    // Khởi tạo Base Layers
    const defaults = L.tileLayer('https://api.maptiler.com/maps/outdoor-v2/{z}/{x}/{y}.png?key=cVxgYKHPCe98W6oTrqUQ');
    const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
    const satellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}');
    const topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png');

    const baseLayers = {
        "Bản đồ mặc định": defaults,
        "Bản đồ giao thông": streets,
        "Bản đồ vệ tinh": satellite,
        "Bản đồ địa hình": topo
    };

    const defaultCenter = [21.0285, 105.8542]; // Tọa độ Hà Nội
    const defaultZoom = 13;
    const bounds = L.latLngBounds(
        [8.0, 102.0],
        [23.5, 109.5]
    );

    map = L.map('map', {
        center: defaultCenter,
        zoom: defaultZoom,
        layers: [defaults],
        maxBounds: bounds,
        maxBoundsViscosity: 1.0,
        minZoom: 9,
        maxZoom: 18,
        attributionControl: false
    });

    const redIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    // Controls: Reset và Current Location
    const resetControl = L.control({ position: window.innerWidth <= 1024 ? 'topright' : 'bottomright' });
    const currentLocation = L.control({ position: window.innerWidth <= 1024 ? 'topright' : 'bottomright' });

    window.addEventListener('resize', () => {
        const newPosition = window.innerWidth <= 1024 ? 'topright' : 'bottomright';
        resetControl.setPosition(newPosition);
        currentLocation.setPosition(newPosition);
    });

    resetControl.onAdd = function (map) {
        const btn = L.DomUtil.create('button', 'leaflet-bar leaflet-control leaflet-control-custom');
        btn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px; margin: auto;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        `;
        btn.title = 'Đặt lại bản đồ';
        btn.style.cssText = 'background-color: white; width: 48px; height: 48px; cursor: pointer; font-size: 18px; line-height: 30px; text-align: center; margin: 10px;';

        L.DomEvent.disableClickPropagation(btn);
        btn.onclick = function () {
            resetMap();
        };
        return btn;
    };

    resetControl.addTo(map);

    currentLocation.onAdd = function (map) {
        const btn = L.DomUtil.create('button', 'leaflet-bar leaflet-control leaflet-control-custom');
        btn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px; margin: auto;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
        `;
        btn.title = 'Vị trí hiện tại của tôi';
        btn.style.cssText = 'background-color: white; width: 48px; height: 48px; cursor: pointer; font-size: 18px; line-height: 30px; text-align: center; margin: 10px; margin-bottom: 0;';

        L.DomEvent.disableClickPropagation(btn);

        btn.onclick = function () {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    const latLng = [position.coords.latitude, position.coords.longitude];
                    map.setView(latLng, 16);

                    if (map._currentLocationMarker) {
                        map.removeLayer(map._currentLocationMarker);
                    }
                    map._currentLocationMarker = L.marker(latLng, {
                        icon: redIcon
                    }).addTo(map)
                        .bindPopup("Vị trí hiện tại của bạn")
                        .openPopup();
                }, function () {
                    toast.error('Không thể lấy vị trí hiện tại');
                });
            } else {
                toast.error('Trình duyệt không hỗ trợ định vị địa lý');
            }
        };

        return btn;
    };

    currentLocation.addTo(map);

    function resetMap() {
        if (map._currentLocationMarker) {
            map.removeLayer(map._currentLocationMarker);
            map._currentLocationMarker = null;
        }
        map.setView(defaultCenter, defaultZoom);
    }

    L.control.layers(baseLayers).addTo(map);

    // TẠO MARKER CLUSTER GROUP
    markerClusterGroup = L.markerClusterGroup({
        iconCreateFunction: function (cluster) {
            const childCount = cluster.getChildCount();
            let bgColor, textColor, borderColor;

            if (childCount < 5) {
                bgColor = '#22c55e'; // Green
                borderColor = '#16a34a';
                textColor = '#ffffff';
            } else if (childCount < 10) {
                bgColor = '#3b82f6'; // Blue
                borderColor = '#1d4ed8';
                textColor = '#ffffff';
            } else if (childCount < 20) {
                bgColor = '#fb923c'; // Orange
                borderColor = '#ea580c';
                textColor = '#ffffff';
            } else {
                bgColor = '#f43f5e'; // Red
                borderColor = '#dc2626';
                textColor = '#ffffff';
            }

            return new L.DivIcon({
                html: `
                    <div style="
                        background: ${bgColor};
                        border: 4px solid ${borderColor};
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: ${textColor};
                        font-weight: 700;
                        font-size: 17px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.4), 0 2px 4px rgba(0,0,0,0.3);
                    ">
                        ${childCount}
                    </div>
                `,
                className: 'custom-cluster-icon',
                iconSize: L.point(50, 50)
            });
        },
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: true,
        zoomToBoundsOnClick: true,
        maxClusterRadius: 80,
        disableClusteringAtZoom: 16
    });

    // Thêm các marker vào cluster group
    const clockIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`;
    const phoneIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle;"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>`;
    const mapPinIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>`;
    courts.value.forEach(c => {
        const m = L.marker([c.latitude, c.longitude])
            .bindPopup(`
                <div style="min-width: 220px; font-family: system-ui; margin-top: 20px;">
                    <img src="${c.image || defaultImage}" alt="Court Image" style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; margin-bottom: 10px;" onerror="this.onerror=null;this.src='${defaultImage}'" />
                    <h3 style="margin: 0 0 10px 0; font-weight: 600; font-size: 16px; color: #1f2937;">${c.name}</h3>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <p style="margin: 0;display:flex; justify-content:start; align-items:center; gap:6px; font-size: 14px; color: #4b5563;">
                            <span style="color: #4392E0; font-weight: 500;">
                                ${clockIcon}
                            </span> 
                            Giờ Mở cửa:
                            ${toHourMinute(c.opening_time)} - ${toHourMinute(c.closing_time)}
                        </p>
                        <p style="margin: 0;display:flex; justify-content:start; align-items:center; gap:6px; font-size: 14px; color: #4b5563;">
                            <span style="color: #4392E0; font-weight: 500;">
                                ${phoneIcon}
                            </span> 
                            ${c.phone}
                        </p>
                        <p style="margin: 0;display:flex; justify-content:start; align-items:baseline; gap:6px; font-size: 14px; color: #4b5563; line-height: 1.4;">
                            <span style="color: #4392E0; font-weight: 500;">
                                ${mapPinIcon}
                                </span> 
                            ${c.address}
                        </p>
                    </div>
                </div>
            `, {
                maxWidth: 300
            });

        markers[c.id] = m;
        markerClusterGroup.addLayer(m);
    });

    // Thêm cluster group vào map
    map.addLayer(markerClusterGroup);
});

// Focus + zoom when click in sidebar - CẢI TIẾN
const focusCourt = (court) => {
    selectedCourt.value = court.id;
    const marker = markers[court.id];

    if (marker && map) {
        // Zoom đến marker với animation mượt
        map.setView(marker.getLatLng(), 17, {
            animate: true,
            duration: 0.8,
            easeLinearity: 0.5
        });

        // Đợi animation hoàn thành rồi mở popup
        setTimeout(() => {
            // Nếu marker đang trong cluster, tự động spiderfy và hiện popup
            if (markerClusterGroup && markerClusterGroup.hasLayer(marker)) {
                markerClusterGroup.zoomToShowLayer(marker, () => {
                    marker.openPopup();
                });
            } else {
                marker.openPopup();
            }
        }, 900);
    }
};

const focusUser = (user) => {
    selectedUser.value = user.id;
    const marker = markers[user.id];

    if (marker && map) {
        // Zoom đến marker với animation mượt
        map.setView(marker.getLatLng(), 17, {
            animate: true,
            duration: 0.8,
            easeLinearity: 0.5
        });

        // Đợi animation hoàn thành rồi mở popup
        setTimeout(() => {
            // Nếu marker đang trong cluster, tự động spiderfy và hiện popup
            if (markerClusterGroup && markerClusterGroup.hasLayer(marker)) {
                markerClusterGroup.zoomToShowLayer(marker, () => {
                    marker.openPopup();
                });
            } else {
                marker.openPopup();
            }
        }, 900);
    }
};

const focusMatches = (match) => {
    selectedMatches.value = match.id;
    const marker = markers[match.id];

    if (marker && map) {
        // Zoom đến marker với animation mượt
        map.setView(marker.getLatLng(), 17, {
            animate: true,
            duration: 0.8,
            easeLinearity: 0.5
        });

        // Đợi animation hoàn thành rồi mở popup
        setTimeout(() => {
            // Nếu marker đang trong cluster, tự động spiderfy và hiện popup
            if (markerClusterGroup && markerClusterGroup.hasLayer(marker)) {
                markerClusterGroup.zoomToShowLayer(marker, () => {
                    marker.openPopup();
                });
            } else {
                marker.openPopup();
            }
        }, 900);
    }
};
</script>

<style>
/* Đảm bảo map Leaflet không có z-index quá cao, nếu không nó sẽ hiển thị trên modal */
#map {
    z-index: 0 !important;
}

/* Custom styles cho cluster */
.custom-cluster-icon {
    background: transparent !important;
}

/* Animation cho cluster khi hover */
.leaflet-marker-icon:hover {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

/* global.css / tailwind.css */
@keyframes spin-once {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

.animate-spin-once {
    animation: spin-once 0.7s ease-in-out forwards;
}
</style>