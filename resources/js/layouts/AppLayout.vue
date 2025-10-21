<template>
    <div class="flex h-screen overflow-hidden relative bg-gray-50">
        <Sidebar ref="sidebarRef" :isMobile="isMobile" />
        <div
            class="flex-1 flex flex-col overflow-hidden transition-all duration-300 ease-in-out"
            :style="mainStyle"
        >
            <Header />
            <main class="flex-1 overflow-y-auto">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from "vue";
import Header from "@/components/organisms/Header.vue";
import Sidebar from "@/components/organisms/Sidebar.vue";

const sidebarRef = ref(null);
const isMobile = ref(window.innerWidth <= 1024);

const onResize = () => {
    isMobile.value = window.innerWidth <= 1024;
};

onMounted(() => window.addEventListener("resize", onResize));
onBeforeUnmount(() => window.removeEventListener("resize", onResize));

const collapsedPx = "4rem";
const mainStyle = computed(() => ({
    // Luôn giữ khoảng 4rem để tránh sidebar đè
    marginLeft: collapsedPx,
    width: `calc(100% - ${collapsedPx})`,
}));
</script>
