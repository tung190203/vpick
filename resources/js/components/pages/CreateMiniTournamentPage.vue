<template>
    <div class="p-4 max-w-5xl mx-auto">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-[8px] shadow p-5 sticky top-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 text-[20px]">Môn thể thao</h3>
                    </div>
                    <p class="text-[#838799]">Môn thể thao của tôi • {{ sports.length }}</p>
                    <Swiper :slides-per-view="'auto'" :space-between="8" :freeMode="true" @swiper="onSwiperInit"
                        :mousewheel="{ forceToAxis: true }" :modules="modules" class="mt-2 !pb-2">
                        <SwiperSlide v-for="sport in sports" :key="sport.id" class="!w-32">
                            <div @click="selectedSportId = sport.id" :class="[
                                'flex flex-col items-center justify-center px-6 py-4 rounded-lg cursor-pointer transition select-none',
                                selectedSportId === sport.id
                                    ? 'bg-[#D72D36] text-white'
                                    : 'border border-[#BBBFCC] text-gray-700 hover:border-gray-400'
                            ]">
                                <div class="text-3xl my-4">
                                    <img :src="sport.icon || '/images/basketball.png'"
                                        :class="{ 'filter brightness-0 invert': selectedSportId === sport.id }" alt=""
                                        draggable="false" class="w-10 h-10" />
                                </div>
                                <div class="font-semibold text-sm text-center">
                                    {{ sport.name }}
                                </div>
                            </div>
                        </SwiperSlide>
                    </Swiper>
                    <div>

                    </div>
                    <hr class="my-4 border-1">
                    <p class="text-[#838799]">Cấp độ giải đấu • {{ matchTypes.length }}</p>
                    <div class="grid lg:grid-cols-2 grid-cols-4 gap-2 mt-2">
                        <button v-for="type in matchTypes" :key="type.id" @click="selectedType = type.id" :class="[
                            'text-base px-2 text-center py-2 rounded transition-colors',
                            selectedType === type.id
                                ? 'bg-[#D72D36] text-white border border-[#D72D36]'
                                : 'border border-[#BBBFCC] text-gray-700 hover:border-gray-400'
                        ]">
                            {{ type.name }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-6 lg:col-span-2">
                <div class="bg-white rounded-[8px] shadow p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 text-[20px]">Thông tin kèo đấu</h3>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <input v-model="tournamentName" type="text" placeholder="Tên kèo đấu"
                                class="w-full px-2 py-2 my-1 border rounded focus:outline-none placeholder:text-sm placeholder:text-[#BBBFCC] bg-[#EDEEF2]" />
                            <textarea v-model="tournamentNote" rows="4" placeholder="Thêm ghi chú cho kèo đấu"
                                class="w-full px-2 py-2 my-1 border rounded focus:outline-none placeholder:text-sm placeholder:text-[#BBBFCC] bg-[#EDEEF2] resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[8px] shadow p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 text-[20px]">Thời gian</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-[#EDEEF2] rounded-[4px] overflow-visible relative" @click.stop>
                            <button @click="toggleOpenDate"
                                class="w-full flex items-center justify-between rounded-[4px] px-2 py-1 hover:bg-gray-200 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-9 h-9 flex items-center justify-center">
                                        <CalendarDaysIcon class="w-5 h-5 text-gray-700" />
                                    </div>
                                    <span class="text-sm"
                                        :class="{ 'text-[#BBBFCC]': !formattedDate, 'text-gray-900 font-medium': formattedDate }">
                                        {{ formattedDate || 'Ngày & Giờ' }}
                                    </span>
                                </div>
                                <ChevronDownIcon class="w-5 h-5 transition-transform text-gray-700"
                                    :class="{ 'rotate-180': openDate }" />
                            </button>

                            <Transition name="fade">
                                <div v-if="openDate"
                                    class="absolute top-full left-0 right-0 mt-2 p-4 z-50 bg-white rounded-lg shadow-lg">
                                    <VueDatePicker v-model="date" :locale="vi" inline auto-apply enable-time-picker />
                                </div>
                            </Transition>
                        </div>

                        <div class="bg-[#EDEEF2] rounded-[4px] overflow-visible relative" @click.stop>
                            <button @click="toggleOpenTime"
                                class="w-full flex items-center justify-between rounded-[4px] px-2 py-1 hover:bg-gray-200 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-9 h-9 flex items-center justify-center">
                                        <ClockIcon class="w-5 h-5 text-gray-700" />
                                    </div>
                                    <span class="text-sm"
                                        :class="{ 'text-[#BBBFCC]': !selectedDuration, 'text-gray-900 font-medium': selectedDuration }">
                                        {{ selectedDuration || 'Thời lượng' }}
                                    </span>
                                </div>
                                <ChevronDownIcon class="w-5 h-5 transition-transform text-gray-700"
                                    :class="{ 'rotate-180': openTime }" />
                            </button>

                            <Transition name="fade">
                                <div v-if="openTime"
                                    class="absolute top-full left-0 right-0 mt-2 p-2 z-40 bg-white rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <button v-for="option in durationOptions" :key="option.value"
                                        @click="selectDuration(option)"
                                        class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 rounded block whitespace-nowrap"
                                        :class="{ 'bg-gray-50 font-medium': durationMinutes === option.value }">
                                        {{ option.label }}
                                    </button>
                                </div>
                            </Transition>
                        </div>

                        <div class="relative flex items-center" @click.stop>
                            <MapPinIcon class="w-5 h-5 text-gray-700 absolute top-1/2 left-4 -translate-y-1/2" />
                            <input v-model="locationKeyword" @input="fetchCompetitionLocations(locationKeyword)"
                                @focus="isLocationDropdownOpen = competitionLocations.length > 0 || locationKeyword.length >= 2"
                                @blur="setTimeout(() => isLocationDropdownOpen = false, 200)" type="text"
                                placeholder="Địa điểm"
                                class="w-full pl-11 pr-4 py-2 my-1 border rounded focus:outline-none placeholder:text-sm placeholder:text-[#BBBFCC] bg-[#EDEEF2]" />

                            <div v-if="isLocationDropdownOpen"
                                class="absolute left-0 right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50 max-h-60 overflow-y-auto">
                                <button v-for="location in competitionLocations" :key="location.id"
                                    @mousedown.prevent="selectLocation(location)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': selectedLocation && selectedLocation.id === location.id }">
                                    {{ location.name }}
                                    <p v-if="location.address" class="text-xs text-gray-500 truncate">{{
                                        location.address }}</p>
                                </button>
                                <p v-if="!competitionLocations.length && locationKeyword.length >= 2"
                                    class="p-4 text-gray-500 text-sm">Không tìm thấy địa điểm nào.</p>
                            </div>
                        </div>
                        <hr>
                        <div class="bg-white rounded-lg py-2 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <UsersIcon class="w-5 h-5 text-gray-700" />
                                    <span class="text-gray-700">Số người chơi</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="decreasePlayer"
                                        class="w-6 h-6 bg-gray-800 text-white rounded hover:bg-gray-700 flex items-center justify-center text-sm select-none">
                                        −
                                    </button>
                                    <span class="text-xl font-semibold w-12 text-center select-none">{{ playerCount
                                        }}</span>
                                    <button @click="increasePlayer"
                                        class="w-6 h-6 bg-gray-800 text-white rounded hover:bg-gray-700 flex items-center justify-center text-sm select-none">
                                        +
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between relative">
                                <div class="flex items-center gap-3">
                                    <LockClosedIcon class="w-5 h-5 text-gray-700" />
                                    <span class="text-gray-700">Quyền riêng tư</span>
                                </div>
                                <button @click="toggleOpenPrivacy" @click.stop
                                    class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                    <span class="font-medium">{{ privacy }}</span>
                                    <ChevronRightIcon class="w-5 h-5 transition-transform"
                                        :class="{ 'rotate-90': openPrivacy }" />
                                </button>

                                <div v-if="openPrivacy" @click.stop
                                    class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                    <button @click="selectPrivacy('Công khai')"
                                        class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg block whitespace-nowrap"
                                        :class="{ 'bg-gray-50 font-medium': privacy === 'Công khai' }">
                                        Công khai
                                        <p class="text-[11px] text-gray-500">Ai cũng có thể tìm thấy và đăng kí tham gia
                                        </p>
                                    </button>
                                    <button @click="selectPrivacy('Riêng tư')"
                                        class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 last:rounded-b-lg block whitespace-nowrap"
                                        :class="{ 'bg-gray-50 font-medium': privacy === 'Riêng tư' }">
                                        Riêng tư
                                        <p class="text-[11px] text-gray-500">Chỉ có những thành viên được mời có thể
                                            thấy và yêu cầu tham gia</p>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between relative">
                                <div class="flex items-center gap-3">
                                    <CurrencyDollarIcon class="w-5 h-5 text-gray-700" />
                                    <span class="text-gray-700">Phí tham gia</span>
                                </div>
                                <button @click="toggleOpenFee" @click.stop
                                    class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                    <span class="font-medium">{{feeOptions.find(f => f.value === fee)?.label}}</span>
                                    <ChevronRightIcon class="w-5 h-5 transition-transform"
                                        :class="{ 'rotate-90': openFee }" />
                                </button>

                                <div v-if="openFee" @click.stop
                                    class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                    <button v-for="option in feeOptions" :key="option.value"
                                        @click="selectFee(option.value)"
                                        class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                        :class="{ 'bg-gray-50 font-medium': fee === option.value }">
                                        {{ option.label }}
                                        <p class="text-[11px] text-gray-500">{{ option.smallText }}</p>
                                    </button>
                                </div>
                            </div>

                            <div v-if="fee === 'auto_split' || fee === 'per_person'" class="pl-8">
                                <input v-model="formattedFeeAmount" @input="handleFeeInput" type="text"
                                    placeholder="Số tiền (VNĐ)"
                                    class="w-full px-2 py-2 border rounded focus:outline-none placeholder:text-sm placeholder:text-[#BBBFCC] bg-[#EDEEF2]" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[8px] shadow p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 text-[20px]">DUPR</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Tích điểm DUPR</span>
                            <button @click="toggleDUPR"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="duprEnabled ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    :class="duprEnabled ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Tích điểm VNDUPR</span>
                            <button @click="toggleVNDUPR"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="vnduprEnabled ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    :class="vnduprEnabled ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>
                        <hr class="my-4 border-1">
                        <div class="flex items-center justify-between relative">
                            <div class="flex items-center gap-3">
                                <span class="text-gray-700">Trình độ tối thiểu</span>
                            </div>
                            <button @click="toggleOpenMinLevel" @click.stop
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ minLevel }}</span>
                                <ChevronRightIcon class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': openMinLevel }" />
                            </button>

                            <div v-if="openMinLevel" @click.stop
                                class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                <button v-for="level in levels" :key="level" @click="selectMinLevel(level)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': minLevel === level }">
                                    {{ level }}
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between relative">
                            <div class="flex items-center gap-3">
                                <span class="text-gray-700">Trình độ tối đa</span>
                            </div>
                            <button @click="toggleOpenMaxLevel" @click.stop
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ maxLevel }}</span>
                                <ChevronRightIcon class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': openMaxLevel }" />
                            </button>

                            <div v-if="openMaxLevel" @click.stop
                                class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                <button v-for="level in levels" :key="level" @click="selectMaxLevel(level)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': maxLevel === level }">
                                    {{ level }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[8px] shadow p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 text-[20px]">Luật thi đấu</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between relative">
                            <span class="text-gray-700">Số set đấu</span>
                            <button @click="toggleOpenSet" @click.stop
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ setNumber }} Set</span>
                                <ChevronRightIcon class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': openSet }" />
                            </button>
                            <div v-if="openSet" @click.stop
                                class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                <button v-for="set in setOptions" :key="set.value" @click="selectSet(set.value)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': setNumber === set.value }">
                                    {{ set.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Điểm kết thúc mỗi trận</span>
                            <button @click="openPointModal('games_per_set')"
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ gamesPerSet }} Điểm</span>
                                <ChevronRightIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between relative">
                            <span class="text-gray-700">Quy tắc thắng</span>
                            <button @click="toggleOpenWinRule" @click.stop
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ winRuleLabel }}</span>
                                <ChevronRightIcon class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': openWinRule }" />
                            </button>
                            <div v-if="openWinRule" @click.stop
                                class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                <button v-for="rule in winRuleOptions" :key="rule.value"
                                    @click="selectWinRule(rule.value)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': pointsDifference === rule.value }">
                                    {{ rule.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Điểm tối đa</span>
                            <button @click="openPointModal('max_points')"
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ maxPoints }} Điểm</span>
                                <ChevronRightIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Điểm đổi sân</span>
                            <button @click="openPointModal('court_switch_points')"
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ courtSwitchPoints }} Điểm</span>
                                <ChevronRightIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[8px] shadow p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 text-[20px]">Cài đặt nâng cao</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between relative">
                            <span class="text-gray-700">Giới tính</span>
                            <button @click="toggleOpenGender" @click.stop
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ genderLabel }}</span>
                                <ChevronRightIcon class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': openGender }" />
                            </button>
                            <div v-if="openGender" @click.stop
                                class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                <button v-for="gender in genderOptions" :key="gender.value"
                                    @click="selectGender(gender.value)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': genderPolicy === gender.value }">
                                    {{ gender.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between relative">
                            <span class="text-gray-700">Độ tuổi</span>
                            <button @click="toggleOpenAge" @click.stop
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ ageGroupLabel }}</span>
                                <ChevronRightIcon class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': openAge }" />
                            </button>
                            <div v-if="openAge" @click.stop
                                class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                <button v-for="age in ageGroupOptions" :key="age.value" @click="selectAge(age.value)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': ageGroup === age.value }">
                                    {{ age.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between relative">
                            <span class="text-gray-700">Lặp lại</span>
                            <button @click="toggleOpenRepeat" @click.stop
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ repeatLabel }}</span>
                                <ChevronRightIcon class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': openRepeat }" />
                            </button>
                            <div v-if="openRepeat" @click.stop
                                class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                <button v-for="repeat in repeatOptions" :key="repeat.value"
                                    @click="selectRepeat(repeat.value)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': repeatType === repeat.value }">
                                    {{ repeat.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between relative">
                            <span class="text-gray-700">Vai trò</span>
                            <button @click="toggleOpenRole" @click.stop
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ roleLabel }}</span>
                                <ChevronRightIcon class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': openRole }" />
                            </button>
                            <div v-if="openRole" @click.stop
                                class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                <button v-for="role in roleOptions" :key="role.value" @click="selectRole(role.value)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': roleType === role.value }">
                                    {{ role.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between relative">
                            <span class="text-gray-700">Chặn bỏ kèo đấu</span>
                            <button @click="toggleOpenLock" @click.stop
                                class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                                <span class="font-medium">{{ lockCancellationLabel }}</span>
                                <ChevronRightIcon class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': openLock }" />
                            </button>
                            <div v-if="openLock" @click.stop
                                class="absolute right-0 top-full mt-2 bg-white border rounded-lg shadow-lg z-50">
                                <button v-for="lock in lockCancellationOptions" :key="lock.value"
                                    @click="selectLock(lock.value)"
                                    class="px-4 py-2 w-full text-sm text-left hover:bg-gray-100 first:rounded-t-lg last:rounded-b-lg block whitespace-nowrap"
                                    :class="{ 'bg-gray-50 font-medium': lockCancellation === lock.value }">
                                    {{ lock.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Duyệt tự động</span>
                            <button @click="autoApprove = !autoApprove"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="autoApprove ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    :class="autoApprove ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Cho phép người tham gia thêm bạn</span>
                            <button @click="allowParticipantAddFriends = !allowParticipantAddFriends"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="allowParticipantAddFriends ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    :class="allowParticipantAddFriends ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Gửi thông báo</span>
                            <button @click="sendNotification = !sendNotification"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="sendNotification ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    :class="sendNotification ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-start gap-4">
                    <button @click="handleSubmit"
                        class="w-full max-w-[228px] py-3 bg-[#D72D36] text-white rounded font-semibold hover:bg-red-700 transition-colors">
                        {{ btnTitle }}
                    </button>
                    <button @click="router.back()" v-if="isEditMode"
                        class="w-fit px-4 py-3 bg-gray-200 text-gray-700 rounded font-semibold hover:bg-gray-300 transition-colors">
                        Quay lại
                    </button>
                </div>
            </div>
        </div>

        <div v-if="isPointModalOpen"
            class="fixed inset-0 z-[99] flex items-center justify-center bg-gray-600 bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm" @click.stop>
                <h4 class="text-lg font-semibold mb-4">{{ pointModalTitle }}</h4>
                <div class="grid grid-cols-12 gap-2 items-center">
                    <!-- Nút trừ -->
                    <button @click="pointInput = Math.max(minPointValue, pointInput - 1)"
                        class="col-span-2 aspect-square bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 flex items-center justify-center text-2xl font-bold">
                        −
                    </button>

                    <!-- Input -->
                    <input type="text" v-model.number="pointInput"
                        class="col-span-8 text-3xl text-center border-b-2 border-gray-300 focus:border-[#D72D36] outline-none py-2"
                        :min="minPointValue" readonly />

                    <!-- Nút cộng -->
                    <button @click="pointInput++"
                        class="col-span-2 aspect-square bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 flex items-center justify-center text-2xl font-bold">
                        +
                    </button>
                </div>

                <p v-if="pointInputError" class="text-sm text-red-500 mt-2">{{ pointInputError }}</p>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="closePointModal"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">
                        Hủy
                    </button>
                    <button @click="handlePointConfirm"
                        class="px-4 py-2 bg-[#D72D36] text-white rounded-lg hover:bg-red-700">
                        Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { vi } from 'date-fns/locale'
import { ChevronDownIcon, ChevronRightIcon } from "@heroicons/vue/24/solid";
import { CalendarDaysIcon, ClockIcon, MapPinIcon, UsersIcon, LockClosedIcon, CurrencyDollarIcon } from "@heroicons/vue/24/outline";
import * as MiniTournamentService from '@/service/miniTournament'
import * as SportService from '@/service/sport'
import * as CompetitionLocationService from '@/service/competitionLocation'
import { toast } from 'vue3-toastify'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { FreeMode, Mousewheel } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/free-mode'
import { genderOptions } from '@/constants/genderOption';
import { feeOptions } from '@/constants/feeOption';
import { matchTypes } from '@/constants/matchTypes';
import { levels } from '@/constants/levels';
import { setOptions } from '@/constants/setOption';
import { winRuleOptions } from '@/constants/winRuleOption';
import { ageGroupOptions } from '@/constants/ageGroupOption';
import { repeatOptions } from '@/constants/repeatOption';
import { roleOptions } from '@/constants/roleOption';
import { lockCancellationOptions } from '@/constants/lockCancellationOption';
import { durationOptions } from '@/constants/durationOption';
import { useFormattedDate } from '@/composables/formatedDate'
import { useRoute, useRouter } from 'vue-router'
const modules = [FreeMode, Mousewheel]
const router = useRouter()
const route = useRoute()
const miniTournamentId = route.params.id || null
const isEditMode = computed(() => !!miniTournamentId)
const btnTitle = computed(() => isEditMode.value ? 'Chỉnh sửa kèo đấu' : 'Tạo kèo đấu');

// =================================================================================
// Refs and State (Existing)
// =================================================================================
const openDate = ref(false)
const openTime = ref(false)
const openPrivacy = ref(false)
const openFee = ref(false)
const openMinLevel = ref(false)
const openMaxLevel = ref(false)
const date = ref(null)
const durationMinutes = ref(null)
const selectedDuration = ref('')
const playerCount = ref(1)
const privacy = ref('Công khai')
const fee = ref('none')
const feeAmount = ref(0)
const formattedFeeAmount = ref('')
const sports = ref([])
const selectedSportId = ref(null)
const tournamentName = ref('')
const tournamentNote = ref('')

const duprEnabled = ref(true)
const vnduprEnabled = ref(true)

const minLevel = ref('Không giới hạn')
const maxLevel = ref('Không giới hạn')
const selectedType = ref(1)
const autoApprove = ref(true)
const allowParticipantAddFriends = ref(true)
const sendNotification = ref(true)
const { formattedDate } = useFormattedDate(date)

// =================================================================================
// REFS CHO PHẦN TÌM KIẾM ĐỊA ĐIỂM
// =================================================================================
const locationKeyword = ref('')
const competitionLocations = ref([])
const selectedLocation = ref(null)
const isLocationDropdownOpen = ref(false)

// =================================================================================
// New Refs and Consts for Tournament Rules and Advanced Settings
// =================================================================================

// Luật thi đấu
const setNumber = ref(1)
const gamesPerSet = ref(11)
const pointsDifference = ref(2)
const maxPoints = ref(11)
const courtSwitchPoints = ref(1)

const openSet = ref(false)
const openWinRule = ref(false)
const winRuleLabel = computed(() => {
    return winRuleOptions.find(r => r.value === pointsDifference.value)?.label || 'Cách biệt 2 điểm'
})

const isPointModalOpen = ref(false)
const pointModalTitle = ref('')
const pointInputType = ref('')
const pointInput = ref(0)
const pointInputError = ref('')

const minPointValue = computed(() => {
    if (pointInputType.value === 'max_points') {
        return gamesPerSet.value > 0 ? gamesPerSet.value : 1
    }
    return 1
})

const swiperInstance = ref(null);
const onSwiperInit = (swiper) => {
  swiperInstance.value = swiper;
};

watch(selectedSportId, (id) => {
  const index = sports.value.findIndex(s => s.id === id);
  if (index !== -1 && swiperInstance.value) {
    swiperInstance.value.slideTo(index);
  }
});


const genderPolicy = ref(3)
const ageGroup = ref(1)
const repeatType = ref(1)
const roleType = ref(2)
const lockCancellation = ref(1)

const openGender = ref(false)
const openAge = ref(false)
const openRepeat = ref(false)
const openRole = ref(false)
const openLock = ref(false)

const genderLabel = computed(() => genderOptions.find(g => g.value === genderPolicy.value)?.label || 'Không giới hạn')
const ageGroupLabel = computed(() => ageGroupOptions.find(a => a.value === ageGroup.value)?.label || 'Không giới hạn')
const repeatLabel = computed(() => repeatOptions.find(r => r.value === repeatType.value)?.label || '1 tuần')
const roleLabel = computed(() => roleOptions.find(r => r.value === roleType.value)?.label || 'Tổ chức và tham gia')
const lockCancellationLabel = computed(() => {
    return lockCancellation.value === 0
        ? 'Không có'
        : lockCancellationOptions.find(l => l.value === lockCancellation.value)?.label || 'Không có'
})

// Định nghĩa trạng thái ban đầu để reset form
const initialStates = {
    openDate: false, openTime: false, openPrivacy: false, openFee: false, openMinLevel: false, openMaxLevel: false,
    openSet: false, openWinRule: false, openGender: false, openAge: false, openRepeat: false, openRole: false, openLock: false,
    isLocationDropdownOpen: false, isPointModalOpen: false,
    date: null, durationMinutes: null, selectedDuration: '', playerCount: 1, privacy: 'Công khai',
    fee: 'none', feeAmount: 0, formattedFeeAmount: '',
    tournamentName: '', tournamentNote: '', selectedType: 1, selectedSportId: null,
    duprEnabled: true, vnduprEnabled: true, minLevel: 'Không giới hạn', maxLevel: 'Không giới hạn',
    locationKeyword: '', selectedLocation: null, competitionLocations: [],
    setNumber: 1, gamesPerSet: 11, pointsDifference: 2, maxPoints: 11, courtSwitchPoints: 1,
    genderPolicy: 3, ageGroup: 1, repeatType: 1, roleType: 2, lockCancellation: 1,
    autoApprove: true, allowParticipantAddFriends: true, sendNotification: true,
};

const resetFormState = () => {
    // Cập nhật tất cả refs về trạng thái ban đầu
    date.value = initialStates.date;
    durationMinutes.value = initialStates.durationMinutes;
    selectedDuration.value = initialStates.selectedDuration;
    playerCount.value = initialStates.playerCount;
    privacy.value = initialStates.privacy;
    fee.value = initialStates.fee;
    feeAmount.value = initialStates.feeAmount;
    formattedFeeAmount.value = initialStates.formattedFeeAmount;
    tournamentName.value = initialStates.tournamentName;
    tournamentNote.value = initialStates.tournamentNote;
    duprEnabled.value = initialStates.duprEnabled;
    vnduprEnabled.value = initialStates.vnduprEnabled;
    minLevel.value = initialStates.minLevel;
    maxLevel.value = initialStates.maxLevel;
    locationKeyword.value = initialStates.locationKeyword;
    selectedLocation.value = initialStates.selectedLocation;
    setNumber.value = initialStates.setNumber;
    gamesPerSet.value = initialStates.gamesPerSet;
    pointsDifference.value = initialStates.pointsDifference;
    maxPoints.value = initialStates.maxPoints;
    courtSwitchPoints.value = initialStates.courtSwitchPoints;
    genderPolicy.value = initialStates.genderPolicy;
    ageGroup.value = initialStates.ageGroup;
    repeatType.value = initialStates.repeatType;
    roleType.value = initialStates.roleType;
    lockCancellation.value = initialStates.lockCancellation;
    autoApprove.value = initialStates.autoApprove;
    allowParticipantAddFriends.value = initialStates.allowParticipantAddFriends;
    sendNotification.value = initialStates.sendNotification;
    competitionLocations.value = initialStates.competitionLocations;
    isLocationDropdownOpen.value = initialStates.isLocationDropdownOpen;
    // Đảm bảo chọn lại môn thể thao đầu tiên
    if (sports.value.length > 0) {
        selectedSportId.value = sports.value[0].id;
    }
    // Đóng tất cả dropdown UI
    closeOtherDropdowns(null);
};
// =================================================================================
// Global Dropdown/Modal Handlers
// =================================================================================

const closeOtherDropdowns = (exceptRef) => {
    // Chỉ đóng nếu không phải là ngoại lệ được truyền vào
    if (exceptRef !== openDate) openDate.value = false
    if (exceptRef !== openTime) openTime.value = false
    if (exceptRef !== openPrivacy) openPrivacy.value = false
    if (exceptRef !== openFee) openFee.value = false
    if (exceptRef !== openMinLevel) openMinLevel.value = false
    if (exceptRef !== openMaxLevel) openMaxLevel.value = false
    if (exceptRef !== openSet) openSet.value = false
    if (exceptRef !== openWinRule) openWinRule.value = false
    if (exceptRef !== openGender) openGender.value = false
    if (exceptRef !== openAge) openAge.value = false
    if (exceptRef !== openRepeat) openRepeat.value = false
    if (exceptRef !== openRole) openRole.value = false
    if (exceptRef !== openLock) openLock.value = false
    // Đóng dropdown địa điểm
    if (exceptRef !== isLocationDropdownOpen) isLocationDropdownOpen.value = false
}

// Hàm xử lý click bên ngoài để đóng dropdown (trừ modal điểm)
const handleClickOutside = (event) => {
    if (isPointModalOpen.value) return;
    // Nếu event không bị dừng lan truyền bởi @click.stop trên các element tương tác, thì đóng tất cả.
    closeOtherDropdowns(null);
}

// Toggles (Đã được cập nhật để sử dụng closeOtherDropdowns)
const toggleOpenDate = () => {
    const currentState = openDate.value
    closeOtherDropdowns(openDate)
    openDate.value = !currentState
}

const toggleOpenTime = () => {
    const currentState = openTime.value
    closeOtherDropdowns(openTime)
    openTime.value = !currentState
}

const toggleOpenPrivacy = () => {
    const currentState = openPrivacy.value
    closeOtherDropdowns(openPrivacy)
    openPrivacy.value = !currentState
}

const toggleOpenFee = () => {
    const currentState = openFee.value
    closeOtherDropdowns(openFee)
    openFee.value = !currentState
}

const toggleOpenMinLevel = () => {
    const currentState = openMinLevel.value
    closeOtherDropdowns(openMinLevel)
    openMinLevel.value = !currentState
}

const toggleOpenMaxLevel = () => {
    const currentState = openMaxLevel.value
    closeOtherDropdowns(openMaxLevel)
    openMaxLevel.value = !currentState
}

const toggleDUPR = () => {
    duprEnabled.value = !duprEnabled.value
}

const toggleVNDUPR = () => {
    vnduprEnabled.value = !vnduprEnabled.value
}

const toggleOpenSet = () => {
    const currentState = openSet.value
    closeOtherDropdowns(openSet)
    openSet.value = !currentState
}

const toggleOpenWinRule = () => {
    const currentState = openWinRule.value
    closeOtherDropdowns(openWinRule)
    openWinRule.value = !currentState
}

const toggleOpenGender = () => {
    const currentState = openGender.value
    closeOtherDropdowns(openGender)
    openGender.value = !currentState
}

const toggleOpenAge = () => {
    const currentState = openAge.value
    closeOtherDropdowns(openAge)
    openAge.value = !currentState
}

const toggleOpenRepeat = () => {
    const currentState = openRepeat.value
    closeOtherDropdowns(openRepeat)
    openRepeat.value = !currentState
}

const toggleOpenRole = () => {
    const currentState = openRole.value
    closeOtherDropdowns(openRole)
    openRole.value = !currentState
}

const toggleOpenLock = () => {
    const currentState = openLock.value
    closeOtherDropdowns(openLock)
    openLock.value = !currentState
}

// =================================================================================
// Select Handlers (Giữ nguyên)
// =================================================================================
const decreasePlayer = () => {
    if (playerCount.value > 1) {
        playerCount.value--
    }
}

const increasePlayer = () => {
    playerCount.value++
}

const selectPrivacy = (value) => {
    privacy.value = value
    openPrivacy.value = false
}

const selectFee = (value) => {
    fee.value = value
    openFee.value = false
}

const selectMinLevel = (level) => {
    minLevel.value = level
    openMinLevel.value = false
}

const selectMaxLevel = (level) => {
    maxLevel.value = level
    openMaxLevel.value = false
}

const selectDuration = (option) => {
    durationMinutes.value = option.value
    selectedDuration.value = option.label
    openTime.value = false
}

const handleFeeInput = (event) => {
    let value = event.target.value.replace(/[^\d]/g, '')
    feeAmount.value = value ? parseInt(value) : 0
    if (value) {
        formattedFeeAmount.value = parseInt(value).toLocaleString('vi-VN')
    } else {
        formattedFeeAmount.value = ''
    }
}

const selectSet = (value) => {
    setNumber.value = value
    openSet.value = false
}

const selectWinRule = (value) => {
    pointsDifference.value = value
    openWinRule.value = false
}

const selectGender = (value) => {
    genderPolicy.value = value
    openGender.value = false
}

const selectAge = (value) => {
    ageGroup.value = value
    openAge.value = false
}

const selectRepeat = (value) => {
    repeatType.value = value
    openRepeat.value = false
}

const selectRole = (value) => {
    roleType.value = value
    openRole.value = false
}

const selectLock = (value) => {
    lockCancellation.value = value
    openLock.value = false
}

const selectLocation = (location) => {
    selectedLocation.value = location
    locationKeyword.value = location.name
    isLocationDropdownOpen.value = false
}

// =================================================================================
// Point Modal Logic (Giữ nguyên)
// =================================================================================

const openPointModal = (type) => {
    closeOtherDropdowns(null)
    pointInputType.value = type
    pointInputError.value = ''

    if (type === 'games_per_set') {
        pointModalTitle.value = 'Điểm kết thúc mỗi trận'
        pointInput.value = gamesPerSet.value
    } else if (type === 'max_points') {
        pointModalTitle.value = 'Điểm tối đa'
        pointInput.value = maxPoints.value
    } else if (type === 'court_switch_points') {
        pointModalTitle.value = 'Điểm đổi sân'
        pointInput.value = courtSwitchPoints.value
    }

    isPointModalOpen.value = true
}

const closePointModal = () => {
    isPointModalOpen.value = false
    pointInputError.value = ''
}

const handlePointConfirm = () => {
    if (pointInput.value < minPointValue.value) {
        pointInputError.value = `Điểm tối thiểu phải là ${minPointValue.value}.`
        return
    }

    if (pointInputType.value === 'games_per_set') {
        gamesPerSet.value = pointInput.value
        if (maxPoints.value < gamesPerSet.value) {
            maxPoints.value = gamesPerSet.value
        }
    } else if (pointInputType.value === 'max_points') {
        maxPoints.value = pointInput.value
    } else if (pointInputType.value === 'court_switch_points') {
        courtSwitchPoints.value = pointInput.value
    }

    closePointModal()
}


// =================================================================================
// Computed and Submit
// =================================================================================

const handleSubmit = async () => {
    let startsAt = null;
    if (date.value) {
        const d = new Date(date.value);

        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');

        startsAt = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    const getNumericLevel = (level) => {
        if (level === 'Không giới hạn') return null
        return parseFloat(level)
    }

    const lockValue = lockCancellation.value === 0 ? 'unlock' : lockCancellation.value

    const data = {
        sport_id: selectedSportId.value,
        name: tournamentName.value,
        description: tournamentNote.value || null,
        match_type: selectedType.value,
        starts_at: startsAt,
        duration_minutes: durationMinutes.value,
        competition_location_id: selectedLocation.value ? selectedLocation.value?.id : null,
        max_players: playerCount.value,
        is_private: privacy.value === 'Riêng tư',
        fee: fee.value,
        fee_amount: (fee.value === 'per_person') ? feeAmount.value : 0,
        prize_pool: (fee.value === 'auto_split') ? feeAmount.value : 0,
        enable_dupr: duprEnabled.value,
        enable_vndupr: vnduprEnabled.value,
        min_rating: getNumericLevel(minLevel.value),
        max_rating: getNumericLevel(maxLevel.value),
        set_number: setNumber.value,
        games_per_set: gamesPerSet.value,
        points_difference: pointsDifference.value,
        max_points: maxPoints.value,
        court_switch_points: courtSwitchPoints.value,
        gender_policy: genderPolicy.value,
        age_group: ageGroup.value,
        repeat_type: repeatType.value,
        role_type: roleType.value,
        lock_cancellation: lockValue,
        auto_approve: autoApprove.value,
        allow_participant_add_friends: allowParticipantAddFriends.value,
        send_notification: sendNotification.value,
        status: 1,
        invite_user: []
    }

    if(isEditMode.value) {
        await updateMiniTournament(miniTournamentId, data)
        return
    } else {
        await createMiniTournament(data)   
    }
}

const updateMiniTournament = async (id, data) => {
    try {
        const res = await MiniTournamentService.updateMiniTournament(id, data)
        toast.success('Chỉnh sửa kèo đấu thành công!')
        setTimeout(() => {
            router.push({ name: 'mini-tournament-detail', params: { id: miniTournamentId } })
        }, 1000)
    } catch (error) {
        console.error('Error updating mini tournament:', error)
        toast.error('Cập nhật kèo đấu thất bại. Vui lòng kiểm tra lại thông tin.')
    }
}

const createMiniTournament = async (data) => {
    try {
        const res = await MiniTournamentService.storeMiniTournament(data)
        toast.success('Tạo kèo đấu thành công!')
        resetFormState()
        if(res && res.id) {
            setTimeout(() => {
                router.push({ name: 'mini-tournament-detail', params: { id: res.id } })
            }, 1000)
        }
    } catch (error) {
        console.error('Error creating mini tournament:', error)
        toast.error('Tạo kèo đấu thất bại. Vui lòng kiểm tra lại thông tin.')
    }
}

const fetchSports = async () => {
    try {
        const res = await SportService.getAllSports()
        sports.value = res
        if (res.length > 0) {
            selectedSportId.value = res[0].id
        }
    } catch (error) {
        console.error('Error fetching sports:', error)
    }
}

const fetchCompetitionLocations = async (keyword) => {
    if (!keyword || keyword.length < 2) {
        competitionLocations.value = []
        isLocationDropdownOpen.value = false
        return
    }

    closeOtherDropdowns(isLocationDropdownOpen)

    try {
        const res = await CompetitionLocationService.getAllCompetitionLocations(keyword)

        if (Array.isArray(res)) {
            competitionLocations.value = res
            isLocationDropdownOpen.value = competitionLocations.value.length > 0
        } else {
            competitionLocations.value = []
            isLocationDropdownOpen.value = false
        }
    } catch (error) {
        console.error('Error fetching competition locations:', error)
        competitionLocations.value = []
        isLocationDropdownOpen.value = false
    }
}

const prefillForm = (data) => {
    if(!data) return;
    // Thông tin cơ bản
    selectedSportId.value = data?.sport.id || null;
    tournamentName.value = data?.name || '';
    tournamentNote.value = data?.description || '';
    selectedType.value = data?.match_type || 1;
    // Ngày giờ - địa điểm  - người chơi
    if(data?.starts_at) {
        date.value = new Date(data.starts_at);
    }
    if(data?.duration_minutes) {
        durationMinutes.value = data.duration_minutes;
        const durationOption = durationOptions.find(option => option.value === data.duration_minutes);
        selectedDuration.value = durationOption ? durationOption.label : '';
    }
    if(data?.competition_location) {
        selectedLocation.value = data.competition_location;
        locationKeyword.value = data.competition_location.name || '';
    }
    playerCount.value = data?.max_players || 1;
    privacy.value = data?.is_private ? 'Riêng tư' : 'Công khai';
    // Phí
    fee.value = data?.fee || 'none';
    feeAmount.value = data?.fee_amount || 0;
    if(feeAmount.value) {
        formattedFeeAmount.value = feeAmount.value.toLocaleString('vi-VN');
    } else {
        formattedFeeAmount.value = '';
    }
    // điểm DUPR và VNDUPR
    duprEnabled.value = data?.enable_dupr || false;
    vnduprEnabled.value = data?.enable_vndupr || false;
    minLevel.value = data?.min_rating ? data.min_rating.toString() : 'Không giới hạn';
    maxLevel.value = data?.max_rating ? data.max_rating.toString() : 'Không giới hạn';
    // Luật thi đấu
    setNumber.value = data?.set_number || 1;
    gamesPerSet.value = data?.games_per_set || 11;
    pointsDifference.value = data?.points_difference || 2;
    maxPoints.value = data?.max_points || 11;
    courtSwitchPoints.value = data?.court_switch_points || 1;
    // Cài đặt nâng cao
    genderPolicy.value = data?.gender_policy
    ageGroup.value = data?.age_group
    repeatType.value = data?.repeat_type
    roleType.value = data?.role_type
    lockCancellation.value = data?.lock_cancellation === 'unlock' ? 0 : data?.lock_cancellation
    autoApprove.value = data?.auto_approve || false
    allowParticipantAddFriends.value = data?.allow_participant_add_friends || false
    sendNotification.value = data?.send_notification || false
}

const detailMiniTournament = async (id) => {
    try {
        const data = await MiniTournamentService.getMiniTournamentById(id);
        prefillForm(data);
    } catch (error) {
        console.error('Error fetching mini tournament details:', error);
        throw error;
    }
};

onMounted(async () => {
    await fetchSports()
    if(isEditMode.value) {
        await detailMiniTournament(miniTournamentId);
    }
    // Thêm listener cho sự kiện click toàn cục
    document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
    // Xóa listener khi component bị hủy
    document.removeEventListener('click', handleClickOutside)
})

</script>

<style scoped>
.filter-invert-white {
    filter: invert(1) grayscale(100%) brightness(200%) contrast(150%);
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.1s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>