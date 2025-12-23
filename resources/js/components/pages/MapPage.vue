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
                        <p class="text-[#D72D36] font-semibold text-sm">
                            {{
                                {
                                    courts: `${quantityCourts ?? 0} Sân bóng được tìm thấy`,
                                    match: `${quantityMatches ?? 0} Trận đấu được tìm thấy`,
                                    players: `${quantityUsers ?? 0} Người dùng được tìm thấy`
                                }[activeTab] ?? '0 kết quả được tìm thấy'
                            }}
                        </p>

                    </div>

                    <div class="flex-1 overflow-y-auto px-4 py-1" @scroll="handleScroll">
                        <div class="space-y-3">
                            <template v-if="activeTab === 'courts'">
                                <div v-for="court in displayedListData" :key="court.id" @click="focusCourt(court)" :class="[
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
                                <div v-if="visibleItems < listData.length" class="text-center py-2 text-sm text-gray-500">
                                    Đang tải thêm...
                                </div>
                            </template>
                            <template v-else-if="activeTab === 'match'">
                                <div v-for="match in displayedListData" :key="match.id"
                                    class="border rounded-lg p-3 cursor-pointer hover:border-gray-300 shadow-md">
                                    <h3 class="font-semibold text-gray-900 text-base leading-tight line-clamp-2">
                                        {{ match }}
                                    </h3>
                                </div>
                                <div v-if="visibleItems < listData.length" class="text-center py-2 text-sm text-gray-500">
                                    Đang tải thêm...
                                </div>
                            </template>
                            <template v-else-if="activeTab === 'players'">
                                <div v-for="user in displayedListData" :key="user.id" @click="focusUser(user)" :class="[
                                    'border rounded-lg cursor-pointer transition-all overflow-hidden flex h-fit p-2 items-center gap-3',
                                    user.id === selectedUser
                                        ? 'border-blue-500 shadow-md'
                                        : 'border-gray-200 hover:border-gray-300 shadow-md'
                                ]">
                                    <UserCard :avatar="user.avatar_url" :show-hover-delete="false"
                                        :rating="getUserRating(user)" :defaultImage="defaultImage" />
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
                                                }">
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
                                <div v-if="visibleItems < listData.length" class="text-center py-2 text-sm text-gray-500">
                                    Đang tải thêm...
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-8 h-[86vh] bg-white shadow-lg rounded-md overflow-hidden p-5 relative">
                    <Transition enter-active-class="transition-opacity duration-200"
                        leave-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                        enter-to-class="opacity-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <div v-if="isLoadingMap"
                            class="absolute top-6 left-1/2 transform -translate-x-1/2 z-[1000] pointer-events-none">
                            <div
                                class="bg-white px-4 py-2 rounded-full shadow-lg border border-gray-200 flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-[#4392E0]" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span class="text-sm font-medium text-gray-700">Đang tải dữ liệu...</span>
                            </div>
                        </div>
                    </Transition>

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
                    class="fixed inset-y-0 right-4 z-[10000] w-full max-w-sm h-[95vh] mt-6 bg-white shadow-xl rounded-md flex flex-col">

                    <!-- ===== HEADER (KHÔNG SCROLL) ===== -->
                    <div class="px-4 pt-4 pb-3 flex justify-between items-center border-b bg-white">
                        <h3 class="text-2xl font-semibold text-gray-900">
                            Trình lọc sân bóng
                        </h3>
                        <button @click="closeFilterModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div class="px-4 py-4 border-b bg-white">
                            <h3 class="text-xl text-gray-900 mb-4">
                                Bộ môn thể thao
                            </h3>
                            <Swiper :slides-per-view="'auto'" :space-between="8" :freeMode="true"
                                :mousewheel="{ forceToAxis: true }" :modules="modules" class="mt-2 !pb-2">
                                <SwiperSlide v-for="sport in sports" :key="sport.id" class="!w-auto">
                                    <div @click="selectedSportId = sport.id" :class="[
                                        'px-6 py-2 rounded-full text-sm font-semibold cursor-pointer transition select-none whitespace-nowrap flex items-center gap-2',
                                        selectedSportId === sport.id
                                            ? 'bg-[#D72D36] text-white border border-[#D72D36]'
                                            : 'border border-[#BBBFCC] bg-white text-gray-700 hover:border-gray-400'
                                    ]">
                                        <img v-if="sport.icon" :src="sport.icon" class="w-4 h-4"
                                            :class="{ 'filter brightness-0 invert': selectedSportId === sport.id }"
                                            draggable="false" />
                                        {{ sport.name }}
                                    </div>
                                </SwiperSlide>
                            </Swiper>
                        </div>

                        <div class="p-4 space-y-6">

                            <div class="flex justify-between items-center">
                                <p class="font-medium text-gray-900 text-xl">
                                    Hiển thị sân bóng tôi theo dõi
                                </p>
                                <button @click="isShowMyFollow = !isShowMyFollow"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                    :class="isShowMyFollow ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                        :class="isShowMyFollow ? 'translate-x-6' : 'translate-x-1'" />
                                </button>
                            </div>

                            <!-- Xung quanh -->
                            <div class="flex justify-between items-center">
                                <p class="font-medium text-gray-900 text-xl">Xung quanh bạn</p>
                                <div class="text-[#207AD5] flex items-center gap-1 cursor-pointer font-semibold">
                                    <p>Gần đây</p>
                                    <ChevronRightIcon class="w-4 h-4" />
                                </div>
                            </div>

                            <!-- Khu vực -->
                            <div class="flex justify-between items-center">
                                <p class="font-medium text-gray-900 text-xl">Khu vực</p>
                                <div class="text-[#207AD5] flex items-center gap-1 cursor-pointer font-semibold">
                                    <p>Chọn địa điểm</p>
                                    <ChevronRightIcon class="w-4 h-4" />
                                </div>
                            </div>

                            <!-- Số sân -->
                            <div class="border-t pt-4">
                                <p class="font-medium text-gray-900 mb-4 text-xl">Số sân</p>
                                <div class="grid grid-cols-3 gap-4">
                                    <label v-for="n in courtCounts" :key="n"
                                        class="flex items-center gap-3 cursor-pointer relative"
                                        @click="toggleCourtCount(n)">
                                        <input type="checkbox" :checked="isCourtCountSelected(n)"
                                            class="peer appearance-none w-5 h-5 rounded border-2 border-[#D72D36] checked:bg-[#D72D36] checked:border-[#D72D36]"
                                            @click.prevent />
                                        <CheckIcon
                                            class="w-4 h-4 text-white absolute left-[2px] opacity-0 peer-checked:opacity-100 pointer-events-none" />
                                        <span>{{ n }}+</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Loại sân -->
                            <div class="border-t pt-4">
                                <p class="font-medium text-gray-900 mb-4 text-xl">Loại sân</p>
                                <div class="grid grid-cols-2 gap-4">
                                    <label v-for="yardType in yardTypes" :key="yardType.id"
                                        class="flex items-center gap-3 cursor-pointer relative"
                                        @click="toggleCourtType(yardType.id)">
                                        <input type="checkbox" :checked="isCourtTypeSelected(yardType.id)"
                                            class="peer appearance-none w-5 h-5 rounded border-2 border-[#D72D36] checked:bg-[#D72D36] checked:border-[#D72D36]"
                                            @click.prevent />
                                        <CheckIcon
                                            class="w-4 h-4 text-white absolute left-[2px] opacity-0 peer-checked:opacity-100 pointer-events-none" />
                                        <span>{{ yardType.name }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Tiện ích -->
                            <div class="border-t pt-4">
                                <p class="font-medium text-gray-900 mb-4 text-xl">Tiện ích đi kèm</p>
                                <div class="space-y-4">
                                    <label v-for="facility in facilities" :key="facility.id"
                                        class="flex items-center gap-3 cursor-pointer relative"
                                        @click="toggleFacility(facility.id)">
                                        <input type="checkbox" :checked="isFacilitySelected(facility.id)"
                                            class="peer appearance-none w-5 h-5 rounded border-2 border-[#D72D36] checked:bg-[#D72D36] checked:border-[#D72D36]"
                                            @click.prevent />
                                        <CheckIcon
                                            class="w-4 h-4 text-white absolute left-[2px] opacity-0 peer-checked:opacity-100 pointer-events-none" />
                                        <span>{{ facility.name }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== FOOTER (KHÔNG SCROLL) ===== -->
                    <div class="p-4 border-t bg-white flex justify-between gap-3">
                        <div class="flex items-center gap-2 cursor-pointer" @click="resetFilter">
                            <p>Làm mới</p>
                            <ArrowPathIcon class="w-5 h-5 text-[#4392E0]" :class="{ 'animate-spin': spinning }" />
                        </div>
                        <button @click="applyFilter"
                            class="px-8 py-2 text-sm font-medium text-white bg-[#D72D36] rounded-full hover:bg-[#c22830]">
                            Lọc
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
    import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
    import { useRouter } from 'vue-router';
    import { FunnelIcon, MagnifyingGlassIcon, ClockIcon, PhoneIcon, MapPinIcon, XMarkIcon, ArrowPathIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
    import { toast } from 'vue3-toastify';
    import * as MapService from '@/service/map.js';
    import * as UserService from '@/service/auth.js';
    import * as SportService from '@/service/sport.js';
    import { useTimeFormat } from '@/composables/formatTime.js';
    import { getVisibilityText } from "@/composables/formatVisibilityText";
    import UserCard from '@/components/molecules/UserCard.vue';
    import defaultImage from '@/assets/images/default-image.jpeg';
    import maleIcon from '@/assets/images/male.svg';
    import femaleIcon from '@/assets/images/female.svg';
    import { useMap } from '@/composables/useMap.js';
    import { CheckIcon } from '@heroicons/vue/16/solid';
    import { Swiper, SwiperSlide } from 'swiper/vue';
    import { FreeMode, Mousewheel } from 'swiper/modules';
    import 'swiper/css';
    import 'swiper/css/free-mode';
    
    const router = useRouter();
    const { toHourMinute } = useTimeFormat();
    
    // Map composable
    const {
        initMap,
        clearAllMarkers,
        addCourtMarkers,
        addUserMarkers,
        addMatchMarkers,
        focusItem
    } = useMap();
    
    // ✅ LƯU BOUNDS HIỆN TẠI
    const currentBounds = ref(null);
    
    // Map để lưu data theo ID
    const courtsMap = ref(new Map());
    const usersMap = ref(new Map());
    const matchesMap = ref(new Map());
    
    const isInitialLoad = ref(true);
    const isLoadingMap = ref(false);
    
    // State
    const activeTab = ref('courts');
    const isShowMyFollow = ref(false);
    const selectedCourt = ref(null);
    const selectedUser = ref(null);
    const selectedMatches = ref(null);
    const quantityCourts = ref(0);
    const quantityUsers = ref(0);
    const quantityMatches = ref(0);
    const sports = ref([]);
    const selectedSportId = ref(null);
    const isFilterModalOpen = ref(false);
    const spinning = ref(false);
    const searchCourt = ref('');
    const searchMatch = ref('');
    const searchUser = ref('');
    
    // Filter states
    const selectedCourtCounts = ref([]);
    const selectedCourtTypes = ref([]);
    const selectedFacilities = ref([]);
    const facilities = ref([]);
    const yardTypes = ref([]);
    
    // Constants
    const courtCounts = [2, 4, 6, 8, 10];
    const modules = [FreeMode, Mousewheel];
    
    const tabs = [
        { id: 'courts', label: 'Sân bóng' },
        { id: 'match', label: 'Trận đấu' },
        { id: 'players', label: 'Người chơi' }
    ];
    
    // Convert Map sang Array
    const courts = computed(() => Array.from(courtsMap.value.values()));
    const users = computed(() => Array.from(usersMap.value.values()));
    const matches = computed(() => Array.from(matchesMap.value.values()));
    
    // Convert Map sang Array
    const listData = computed(() => {
        if (activeTab.value === 'courts') return courts.value;
        if (activeTab.value === 'match') return matches.value;
        if (activeTab.value === 'players') return users.value;
        return [];
    });
    
    // ✅ THÊM: Virtual scrolling - chỉ render items trong viewport
    const visibleItems = ref(20); // Số items hiển thị ban đầu
    const itemsPerLoad = ref(10); // Mỗi lần scroll load thêm bao nhiêu
    
    const displayedListData = computed(() => {
        const data = listData.value;
        return data.slice(0, visibleItems.value);
    });
    
    // Scroll handler cho infinite loading
    const handleScroll = (event) => {
        const target = event.target;
        const scrollPercentage = (target.scrollTop + target.clientHeight) / target.scrollHeight;
        
        // Khi scroll được 80% thì load thêm
        if (scrollPercentage > 0.8 && visibleItems.value < listData.value.length) {
            visibleItems.value = Math.min(
                visibleItems.value + itemsPerLoad.value,
                listData.value.length
            );
        }
    };
    
    // Reset visible items khi đổi tab hoặc search
    watch([activeTab, searchCourt, searchMatch, searchUser], () => {
        visibleItems.value = 20;
    });
    
    // Merge data thông minh
    const mergeData = (existingMap, newDataArray, isFiltered = false) => {
        if (isFiltered) {
            existingMap.clear();
            newDataArray.forEach(item => {
                existingMap.set(item.id, item);
            });
        } else {
            newDataArray.forEach(item => {
                existingMap.set(item.id, item);
            });
        }
    };
    
    // Kiểm tra có filter không
    const hasActiveFilters = computed(() => {
        return !!(
            searchCourt.value?.trim() ||
            searchMatch.value?.trim() ||
            searchUser.value?.trim() ||
            selectedSportId.value ||
            isShowMyFollow.value ||
            selectedCourtCounts.value.length > 0 ||
            selectedCourtTypes.value.length > 0 ||
            selectedFacilities.value.length > 0
        );
    });
    
    const getCompetitionLocation = async (bounds = null) => {
        try {
            const params = {
                is_map: 1,
                keyword: searchCourt.value?.trim() || undefined,
                sport_id: selectedSportId.value || undefined,
                is_followed: isShowMyFollow.value ? 1 : 0 || undefined,
                number_of_yards: selectedCourtCounts.value.length > 0 ? selectedCourtCounts.value : undefined,
                yard_type: selectedCourtTypes.value.length > 0 ? selectedCourtTypes.value : undefined,
                facility_id: selectedFacilities.value.length > 0 ? selectedFacilities.value : undefined,
            };
    
            if (bounds) {
                params.minLat = bounds.getSouth();
                params.maxLat = bounds.getNorth();
                params.minLng = bounds.getWest();
                params.maxLng = bounds.getEast();
            }
    
            Object.keys(params).forEach(key => {
                if (params[key] === undefined) {
                    delete params[key];
                }
            });
    
            const res = await MapService.getCourtData(params);
            if (res.data) {
                mergeData(courtsMap.value, res.data.competition_locations, hasActiveFilters.value);
                quantityCourts.value = courtsMap.value.size;
    
                if (res.data.facilities) {
                    facilities.value = res.data.facilities;
                }
                if (res.data.yard_types) {
                    yardTypes.value = res.data.yard_types;
                }
            }
        } catch (error) {
            console.error("Error fetching map data:", error);
            toast.error(error.response?.data?.message || "Lỗi khi tải dữ liệu sân bóng");
        }
    };
    
    const getListUser = async (bounds = null) => {
        try {
            const params = {
                keyword: searchUser.value?.trim() || undefined,
                sport_id: selectedSportId.value || undefined,
                is_followed: isShowMyFollow.value ? 1 : 0 || undefined,
            };
    
            if (bounds) {
                params.min_lat = bounds.getSouth();
                params.max_lat = bounds.getNorth();
                params.min_lng = bounds.getWest();
                params.max_lng = bounds.getEast();
            }
    
            Object.keys(params).forEach(key => {
                if (params[key] === undefined) {
                    delete params[key];
                }
            });
    
            const res = await UserService.getUserData(params);
            if (res.data) {
                mergeData(usersMap.value, res.data.users || [], hasActiveFilters.value);
                quantityUsers.value = usersMap.value.size;
            }
        } catch (error) {
            console.error("Error fetching user data:", error);
            toast.error(error.response?.data?.message || "Lỗi khi tải dữ liệu người chơi");
        }
    };
    
    const getListMatches = async (bounds = null) => {
        try {
            const params = {
                keyword: searchMatch.value?.trim() || undefined,
                sport_id: selectedSportId.value || undefined,
                is_followed: isShowMyFollow.value ? 1 : 0 || undefined,
            };
    
            if (bounds) {
                params.min_lat = bounds.getSouth();
                params.max_lat = bounds.getNorth();
                params.min_lng = bounds.getWest();
                params.max_lng = bounds.getEast();
            }
    
            Object.keys(params).forEach(key => {
                if (params[key] === undefined) {
                    delete params[key];
                }
            });
    
            const res = await MapService.getMatchesData(params);
            mergeData(matchesMap.value, res || [], hasActiveFilters.value);
            quantityMatches.value = matchesMap.value.size;
        } catch (error) {
            console.error("Error fetching match data:", error);
            toast.error(error.response?.data?.message || "Lỗi khi tải dữ liệu trận đấu");
        }
    };
    
    const getListSports = async () => {
        try {
            const res = await SportService.getAllSports();
            sports.value = res || [];
        } catch (error) {
            console.error("Error fetching sports data:", error);
            toast.error(error.response?.data?.message || "Lỗi khi tải dữ liệu bộ môn thể thao");
        }
    };
    
    const loadTabContent = async (tab, bounds = null) => {
        // ✅ LƯU BOUNDS MỖI LẦN LOAD
        if (bounds) {
            currentBounds.value = bounds;
        }
    
        if (hasActiveFilters.value) {
            clearAllMarkers();
        }
    
        const shouldUpdate = !isInitialLoad.value && bounds !== null && !hasActiveFilters.value;
    
        if (shouldUpdate) {
            isLoadingMap.value = true;
        }
    
        try {
            if (tab === 'courts') {
                await getCompetitionLocation(bounds);
                addCourtMarkers(courts.value, toHourMinute, defaultImage, focusCourt, shouldUpdate);
            } else if (tab === 'match') {
                await getListMatches(bounds);
                addMatchMarkers(matches.value, focusMatches, shouldUpdate);
            } else if (tab === 'players') {
                await getListUser(bounds);
                addUserMarkers(users.value, defaultImage, maleIcon, femaleIcon, getVisibilityText, getUserRating, router, focusUser, shouldUpdate);
            }
        } finally {
            isLoadingMap.value = false;
        }
    
        if (isInitialLoad.value) {
            isInitialLoad.value = false;
        }
    };
    
    // Handlers
    const refresh = async () => {
        if (spinning.value) return;
        spinning.value = true;
    
        if (activeTab.value === 'courts') {
            courtsMap.value.clear();
        } else if (activeTab.value === 'match') {
            matchesMap.value.clear();
        } else if (activeTab.value === 'players') {
            usersMap.value.clear();
        }
    
        clearAllMarkers();
        await loadTabContent(activeTab.value, currentBounds.value);
    
        setTimeout(() => {
            spinning.value = false;
        }, 700);
    };
    
    const closeFilterModal = () => {
        isFilterModalOpen.value = false;
    };
    
    const applyFilter = async () => {
        if (activeTab.value === 'courts') {
            courtsMap.value.clear();
        } else if (activeTab.value === 'match') {
            matchesMap.value.clear();
        } else if (activeTab.value === 'players') {
            usersMap.value.clear();
        }
    
        clearAllMarkers();
        await loadTabContent(activeTab.value, currentBounds.value);
        isFilterModalOpen.value = false;
        toast.success('Đã áp dụng bộ lọc');
    };
    
    const resetFilter = async () => {
        selectedCourtCounts.value = [];
        selectedCourtTypes.value = [];
        selectedFacilities.value = [];
        selectedSportId.value = null;
        isShowMyFollow.value = false;
        searchCourt.value = '';
        searchMatch.value = '';
        searchUser.value = '';
    
        courtsMap.value.clear();
        usersMap.value.clear();
        matchesMap.value.clear();
        clearAllMarkers();
    
        await loadTabContent(activeTab.value, currentBounds.value);
        toast.success('Đã làm mới bộ lọc');
    };
    
    // Toggle functions
    const toggleCourtCount = (count) => {
        const index = selectedCourtCounts.value.indexOf(count);
        if (index > -1) {
            selectedCourtCounts.value.splice(index, 1);
        } else {
            selectedCourtCounts.value.push(count);
        }
    };
    
    const toggleCourtType = (typeId) => {
        const index = selectedCourtTypes.value.indexOf(typeId);
        if (index > -1) {
            selectedCourtTypes.value.splice(index, 1);
        } else {
            selectedCourtTypes.value.push(typeId);
        }
    };
    
    const toggleFacility = (facilityId) => {
        const index = selectedFacilities.value.indexOf(facilityId);
        if (index > -1) {
            selectedFacilities.value.splice(index, 1);
        } else {
            selectedFacilities.value.push(facilityId);
        }
    };
    
    // Check selection functions
    const isCourtCountSelected = (count) => {
        return selectedCourtCounts.value.includes(count);
    };
    
    const isCourtTypeSelected = (typeId) => {
        return selectedCourtTypes.value.includes(typeId);
    };
    
    const isFacilitySelected = (facilityId) => {
        return selectedFacilities.value.includes(facilityId);
    };
    
    // Focus functions
    const focusCourt = (court) => {
        selectedCourt.value = court.id;
        focusItem(court.id);
    };
    
    const focusUser = (user) => {
        selectedUser.value = user.id;
        focusItem(user.id);
    };
    
    const focusMatches = (match) => {
        selectedMatches.value = match.id;
        focusItem(match.id);
    };
    
    // Utility functions
    const getUserRating = (user) => {
        if (!user?.sports?.length) return "0";
        const pickleballSport = user.sports.find(sport => sport.sport_name === "Pickleball");
        if (!pickleballSport) return "0";
        return parseFloat(pickleballSport.scores.vndupr_score).toFixed(1) || "0";
    };
    
    // Lifecycle
    onMounted(async () => {
        await getListSports();
    });
    
    // Watchers
    watch(activeTab, (newTab) => {
        isInitialLoad.value = true;
    
        if (newTab === 'courts') {
            courtsMap.value.clear();
        } else if (newTab === 'match') {
            matchesMap.value.clear();
        } else if (newTab === 'players') {
            usersMap.value.clear();
        }
    
        clearAllMarkers();
        // ✅ TRUYỀN currentBounds đã lưu
        loadTabContent(newTab, currentBounds.value);
    });
    
    // Search debounce - Tăng thời gian chờ lên 1000ms (1 giây)
    let searchDebounceTimer = null;
    watch([searchCourt, searchMatch, searchUser], ([newCourt, newMatch, newUser], [oldCourt, oldMatch, oldUser]) => {
        // Xác định search nào đang active
        const activeSearchValue = activeTab.value === 'courts' ? newCourt : 
                                 activeTab.value === 'match' ? newMatch : newUser;
        const oldSearchValue = activeTab.value === 'courts' ? oldCourt : 
                              activeTab.value === 'match' ? oldMatch : oldUser;
        
        // Chỉ debounce nếu search value của tab hiện tại thay đổi
        if (activeSearchValue === oldSearchValue) return;
    
        if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
        
        // Nếu xóa hết text (empty search), load ngay không đợi
        if (!activeSearchValue?.trim()) {
            searchDebounceTimer = setTimeout(async () => {
                isInitialLoad.value = true;
    
                if (activeTab.value === 'courts') {
                    courtsMap.value.clear();
                } else if (activeTab.value === 'match') {
                    matchesMap.value.clear();
                } else if (activeTab.value === 'players') {
                    usersMap.value.clear();
                }
    
                clearAllMarkers();
                await loadTabContent(activeTab.value, currentBounds.value);
            }, 300); // Clear search thì đợi ngắn thôi
            return;
        }
        
        // Search có text thì đợi lâu hơn (1.2 giây)
        searchDebounceTimer = setTimeout(async () => {
            isInitialLoad.value = true;
    
            if (activeTab.value === 'courts') {
                courtsMap.value.clear();
            } else if (activeTab.value === 'match') {
                matchesMap.value.clear();
            } else if (activeTab.value === 'players') {
                usersMap.value.clear();
            }
    
            clearAllMarkers();
            await loadTabContent(activeTab.value, currentBounds.value);
        }, 1200); // Tăng lên 1.2 giây để user gõ xong mới search
    });
    
    // Watch for follow toggle
    watch(isShowMyFollow, async () => {
        isInitialLoad.value = true;
    
        if (activeTab.value === 'courts') {
            courtsMap.value.clear();
        } else if (activeTab.value === 'match') {
            matchesMap.value.clear();
        } else if (activeTab.value === 'players') {
            usersMap.value.clear();
        }
    
        clearAllMarkers();
        await loadTabContent(activeTab.value, currentBounds.value);
    });
    
    // Watch for sport selection
    watch(selectedSportId, async () => {
        isInitialLoad.value = true;
    
        if (activeTab.value === 'courts') {
            courtsMap.value.clear();
        } else if (activeTab.value === 'match') {
            matchesMap.value.clear();
        } else if (activeTab.value === 'players') {
            usersMap.value.clear();
        }
    
        clearAllMarkers();
        await loadTabContent(activeTab.value, currentBounds.value);
    });
    
    // Cleanup
    onUnmounted(() => {
        if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    });
    
    // ✅ Initialize map - LƯU BOUNDS VÀO currentBounds
    const handleBoundsChange = (bounds) => {
        currentBounds.value = bounds;
        loadTabContent(activeTab.value, bounds);
    };
    
    initMap(handleBoundsChange, handleBoundsChange);
    </script>
<style>
#map {
    z-index: 0 !important;
}

.custom-cluster-icon {
    background: transparent !important;
}

.leaflet-marker-icon:hover {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

.leaflet-popup-content-wrapper {
    cursor: pointer !important;
}

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