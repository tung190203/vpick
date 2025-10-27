<template>
    <header
        class="w-full bg-white shadow-sm px-6 py-3 flex items-center justify-between"
    >
        <!-- Search -->
        <div class="flex items-center w-72 bg-[#EDEEF2] rounded-md px-2 py-3">
            <MagnifyingGlassIcon
                class="w-5 h-5 text-gray-700 mr-2 cursor-pointer"
            />
            <input
                type="text"
                placeholder="Tìm kiếm"
                class="bg-[#EDEEF2] flex-1 text-sm text-gray-700 placeholder-gray-400 focus:outline-none"
            />
        </div>

        <!-- Navigation (Desktop only >= 1024px) -->
        <nav class="hidden lg:flex items-center space-x-4 lg:gap-4 sm:gap-2">
            <!-- Player -->
            <template v-if="getRole === ROLE.PLAYER">
                <RouterLink to="/dashboard" :class="linkClass('/')">
                    <HomeIcon class="w-5 h-5" />
                    Trang chủ
                </RouterLink>

                <RouterLink to="/friends" :class="linkClass('/friends')">
                    <UsersIcon class="w-5 h-5" />
                    Bạn bè
                </RouterLink>

                <RouterLink
                    to="/mini-tournament/create"
                    :class="linkClass('/mini-tournament/create')"
                >
                    <PlusCircleIcon class="w-5 h-5" />
                    Tạo kèo đấu
                </RouterLink>

                <RouterLink to="/tools" :class="linkClass('/tools')">
                    <BriefcaseIcon class="w-5 h-5" />
                    Công cụ
                </RouterLink>
            </template>

            <!-- Referee -->
            <template v-else-if="getRole === ROLE.REFEREE">
                <RouterLink
                    to="/referee/dashboard"
                    :class="linkClass('/referee/dashboard')"
                >
                    <HomeIcon class="w-5 h-5" />
                    Trang chủ
                </RouterLink>

                <RouterLink
                    to="/referee/tournaments"
                    :class="linkClass('/referee/tournaments')"
                >
                    <BriefcaseIcon class="w-5 h-5" />
                    Giải đấu được phân công
                </RouterLink>

                <RouterLink
                    to="/referee/reports"
                    :class="linkClass('/referee/reports')"
                >
                    <UsersIcon class="w-5 h-5" />
                    Báo cáo / Khiếu nại
                </RouterLink>
            </template>

            <!-- Admin -->
            <template v-else-if="getRole === ROLE.ADMIN">
                <RouterLink
                    to="/admin/dashboard"
                    :class="linkClass('/admin/dashboard')"
                >
                    <HomeIcon class="w-5 h-5 mr-2" />
                    Trang chủ
                </RouterLink>

                <RouterLink
                    to="/admin/tournament"
                    :class="linkClass('/admin/tournament')"
                >
                    <BriefcaseIcon class="w-5 h-5" />
                    Quản lý giải
                </RouterLink>

                <RouterLink
                    to="/admin/users"
                    :class="linkClass('/admin/users')"
                >
                    <UsersIcon class="w-5 h-5" />
                    Người dùng
                </RouterLink>
            </template>
        </nav>

        <!-- User Info (Desktop only >= 1024px) -->
        <div class="hidden lg:flex items-center space-x-3 cursor-pointer">
            <div class="relative">
                <img
                    :src="getUser.avatar_url"
                    alt="avatar"
                    class="w-10 h-10 rounded-full"
                />
                <span
                    class="absolute -bottom-1 -left-1 bg-blue-500 text-white text-[8px] font-semibold border border-white rounded-full px-1.5 w-4 h-4 flex items-center justify-center"
                >
                    45
                </span>
            </div>
            <div class="text-left">
                <p class="text-[13px] text-gray-600">Xin chào,</p>
                <p class="font-semibold text-gray-800">
                    {{ getUser.full_name }}
                </p>
            </div>
        </div>
    </header>
</template>

<script setup>
import {
    HomeIcon,
    UsersIcon,
    PlusCircleIcon,
    BriefcaseIcon,
    MagnifyingGlassIcon,
} from "@heroicons/vue/24/outline";
import { useUserStore } from "@/store/auth";
import { storeToRefs } from "pinia";
import { ROLE } from "@/constants/index";
import { useRoute } from "vue-router";

const route = useRoute();
const userStore = useUserStore();
const { getUser } = storeToRefs(userStore);
const { getRole } = storeToRefs(userStore);

const linkClass = (path) => {
    const base =
        "flex items-center font-medium px-2 py-2 rounded-md transition";
    const active = "bg-red-600 text-white";
    const normal = "text-gray-700 hover:text-red-600";

    const isActive =
        path === "/" ? route.path === "/" : route.path.startsWith(path);

    return isActive ? `${base} ${active}` : `${base} ${normal}`;
};
</script>