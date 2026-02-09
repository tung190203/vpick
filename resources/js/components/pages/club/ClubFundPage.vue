<template>
    <div class="m-4 max-w-8xl rounded-md flex flex-col relative">
        <ClubFundSkeleton v-if="isInitialLoading" />

        <template v-else>
            <!-- Header Section -->
            <div class="bg-club-default text-white rounded-[16px] shadow-lg p-8 relative overflow-hidden flex flex-col justify-between"
                :style="{ backgroundImage: `url(${Background})` }">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center space-x-3">
                        <ArrowLeftIcon class="w-6 h-6 cursor-pointer text-white" @click="goBack" />
                        <p class="text-2xl font-semibold">Thu Chi</p>
                    </div>
                    <div class="flex items-center space-x-1 relative rounded-[8px] bg-[#3E414C] p-3 cursor-pointer" @click="showQRModal = true">
                        <QRCodeIcon class="w-6 h-6 text-white" />
                    </div>
                </div>
                
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-sm font-medium text-white opacity-60 mb-1">Quỹ chung hiện có</p>
                        <div class="flex items-baseline space-x-2">
                            <p class="text-[64px] font-bold leading-tight">5.240.000</p>
                            <p class="text-xs font-semibold text-[#00B377]">VND</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-6">
                        <div class="flex flex-col items-start bg-[#3E414C]/80 backdrop-blur-sm p-6 rounded-2xl min-w-[280px] space-y-2 border border-white/10">
                            <div class="flex items-center space-x-2 text-[#4ADE80] font-semibold text-sm">
                                <ArrowDownIcon class="w-4 h-4" />
                                <p>Thu tháng này</p>
                            </div>
                            <div class="flex items-baseline space-x-1">
                                <p class="text-3xl font-bold">+ 8.400K</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col items-start bg-[#3E414C]/80 backdrop-blur-sm p-6 rounded-2xl min-w-[280px] space-y-2 border border-white/10">
                            <div class="flex items-center space-x-2 text-[#F87171] font-semibold text-sm">
                                <ArrowUpIcon class="w-4 h-4" />
                                <p>Chi tháng này</p>
                            </div>
                            <div class="flex items-baseline space-x-1">
                                <p class="text-3xl font-bold">- 3.160K</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <!-- Content Grid: Admin / Secretary / Treasurer -->
            <div class="grid grid-cols-12 gap-6 py-6 flex-1 overflow-hidden" v-if="hasAnyRole(['admin', 'secretary', 'treasurer'])">
                <!-- Left Column: ĐỢT THU & XÁC NHẬN -->
                <div class="col-span-4 flex flex-col h-full overflow-hidden">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full overflow-hidden">
                        <!-- ĐỢT THU ĐANG MỞ -->
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-[#838799] font-bold text-[13px] tracking-wider uppercase">ĐỢT THU ĐANG MỞ</h2>
                                <button class="text-[#D72D36] text-[13px] font-bold">Xem chi tiết</button>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-[17px] font-bold text-[#1F2937]">Quỹ tháng 10/2024</h3>
                                    <div class="text-right">
                                        <p class="text-[17px] font-bold text-[#1F2937]">200.000đ</p>
                                        <p class="text-[12px] text-[#838799]">/người</p>
                                    </div>
                                </div>
                                
                                <p class="text-[13px] text-[#838799]">Hạn chót: 30/10</p>

                                <div class="space-y-2">
                                    <div class="w-full h-2 bg-[#F2F3F5] rounded-full overflow-hidden">
                                        <div class="h-full bg-[#D72D36] rounded-full" style="width: 70%"></div>
                                    </div>
                                    <div class="flex justify-between items-center text-[13px]">
                                        <span class="text-[#1F2937] font-medium">Đã thu: 28/40 người</span>
                                        <span class="text-[#1F2937] font-bold">70%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cần xác nhận -->
                        <div class="flex-1 flex flex-col overflow-hidden p-6">
                            <div class="mb-4">
                                <div class="flex items-center space-x-2 text-[#838799] font-bold text-[15px] tracking-wider">
                                    <span>Cần xác nhận</span>
                                    <span>•</span>
                                    <span class="text-[#D72D36]">({{ 2 }})</span>
                                </div>
                            </div>

                            <div class="flex-1 overflow-y-auto">
                                <div v-for="i in 2" :key="i" class="flex items-center justify-between py-4 border-b border-[#F2F3F5] last:border-b-0">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 overflow-hidden">
                                            <img :src="`https://ui-avatars.com/api/?name=${i === 1 ? 'Hoang Nam' : 'Quoc Tuan'}&background=random`" alt="avatar" />
                                        </div>
                                        <span class="font-bold text-[#1F2937] text-sm">{{ i === 1 ? 'Hoàng Nam' : 'Quốc Tuấn' }}</span>
                                    </div>
                                    <button class="bg-[#10B981] text-white px-4 py-1.5 rounded-[4px] text-[9px] font-bold hover:bg-[#059669] transition-colors">
                                        Duyệt
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: LỊCH SỬ THU CHI -->
                <div class="col-span-8 flex flex-col h-full overflow-hidden">
                    <div class="bg-white rounded-[24px] shadow-sm border border-gray-50 flex-1 flex flex-col overflow-hidden">
                        <!-- Section Header -->
                        <div class="px-6 pt-6 pb-2 mb-2 flex items-center justify-between">
                            <div class="text-[#838799] font-bold text-[15px] tracking-wider uppercase">
                                Lịch sử thu chi
                            </div>
                            <button class="text-[#D72D36] text-[13px] font-bold">Xem tất cả</button>
                        </div>
                        
                        <!-- Search & Filter -->
                        <div class="px-6 py-4">
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon class="w-5 h-5 text-[#838799]" />
                                </div>
                                <input 
                                    type="text" 
                                    placeholder="Tìm kiếm lịch sử giao dịch"
                                    class="w-full bg-[#EDEEF2] border-none rounded-2xl py-3.5 pl-12 pr-12 text-sm focus:ring-0 placeholder:text-[#9EA2B3] placeholder:font-normal"
                                />
                                <div class="absolute inset-y-0 right-4 flex items-center">
                                    <FunnelIcon class="w-5 h-5 text-[#838799] cursor-pointer" />
                                </div>
                            </div>
                        </div>

                        <!-- History List -->
                        <div class="flex-1 overflow-y-auto px-6">
                            <!-- Transaction Item -->
                            <div class="flex items-center justify-between py-5 border-b border-[#F2F3F5] hover:bg-gray-50/30 transition-colors cursor-pointer last:border-b-0"
                                v-for="(item, index) in [
                                    { title: 'Mua bóng', amount: '- 100.000', date: '20/9/2024', type: 'expense', icon: 'racket' },
                                    { title: 'Quỹ tháng 9/2024', amount: '+ 9.000.000', date: '20/9/2024', type: 'income', icon: 'fund' },
                                    { title: 'Liên hoan vô địch', amount: '- 3.000.000', date: '20/9/2024', type: 'expense', icon: 'trophy' },
                                    { title: 'Thu phí lớp học viên', amount: '+ 1.000.000', date: '20/9/2024', type: 'income', icon: 'class' }
                                ]" :key="index">
                                <div class="flex items-center space-x-4">
                                    <div :class="[
                                        'w-11 h-11 rounded-full flex items-center justify-center',
                                        item.type === 'expense' ? 'bg-[#FEE2E2]' : 'bg-[#E5F7ED]'
                                    ]">
                                        <BanknotesIcon v-if="item.icon === 'fund'" class="w-6 h-6 text-[#10B981]" />
                                        <TrophyIcon v-else-if="item.icon === 'trophy'" class="w-6 h-6 text-[#D72D36]" />
                                        <AcademicCapIcon v-else-if="item.icon === 'class'" class="w-6 h-6 text-[#10B981]" />
                                        <div v-else class="w-6 h-6 text-[#D72D36] flex items-center justify-center">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M15 9L9 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M9 9L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-bold text-[#1F2937] text-[15px]">{{ item.title }}</p>
                                        <p class="text-[12px] text-[#838799] font-medium mt-0.5">
                                            {{ item.type === 'income' ? 'Thu' : 'Chi' }} ngày {{ item.date }}
                                        </p>
                                    </div>
                                </div>
                                <span :class="['font-bold text-[16px]', item.type === 'expense' ? 'text-[#D72D36]' : 'text-[#10B981]']">
                                    {{ item.amount }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid: Member / Manager -->
            <div class="grid grid-cols-12 gap-6 py-6 flex-1 overflow-hidden" v-else>
                <!-- Left Column: CẦN THANH TOÁN -->
                <div class="col-span-4 flex flex-col h-full overflow-hidden" v-if="hasAnyRole(['manager', 'member'])">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full overflow-hidden">
                        <!-- Section Header -->
                        <div class="p-6 pb-4">
                            <div class="flex items-center space-x-2 text-[#838799] font-semibold tracking-wide">
                                <span>CẦN THANH TOÁN</span>
                                <span class="text-[#838799]">•</span>
                                <span class="text-[#D72D36] tracking-normal">({{ 1 }})</span>
                            </div>
                        </div>

                        <!-- Items Container -->
                        <div class="flex-1 overflow-y-auto px-6 space-y-6">
                            <!-- Actionable Payment Item -->
                            <div class="space-y-4 border-b border-[#dcdee6] pb-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-[#1F2937]">Quỹ tháng 10/2024</h3>
                                        <div class="flex items-center space-x-1.5 mt-1 text-[#D72D36]">
                                            <CalendarIcon class="w-4 h-4" />
                                            <span class="text-sm font-semibold">Hạn chót: Hôm nay</span>
                                        </div>
                                    </div>
                                    <span class="text-[#D72D36] font-semibold">200.000đ</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <button class="py-2.5 px-4 bg-[#F2F3F5] text-[#2D3139] rounded-[4px] font-bold text-sm hover:bg-gray-200 transition-colors">
                                        Chi tiết
                                    </button>
                                    <button class="py-2.5 px-4 bg-[#2D3139] text-white rounded-[4px] font-bold text-sm hover:bg-black transition-colors">
                                        Thanh toán ngay
                                    </button>
                                </div>
                            </div>

                            <!-- Pending Approval Card -->
                            <div class="bg-[#FDF2E2] rounded-lg p-4 border-l-[3px] border-[#F0AC3A] relative flex flex-col space-y-4 shadow-sm mb-6">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-semibold text-[#1F2937]">Quỹ tháng 10/2024</h3>
                                    <span class="text-[#1F2937] font-semibold">200.000đ</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-[12px] text-[#A6753A] font-medium">
                                        <span>Đã chuyển khoản</span>
                                        <span class="mx-1.5 text-[#A6753A]">•</span>
                                        <span>Chờ admin xác nhận</span>
                                    </div>
                                    <div class="bg-[#F0A31D] text-white px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider">Chờ duyệt</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: LỊCH SỬ CỦA TÔI -->
                <div :class="[
                    'flex flex-col h-full overflow-hidden',
                    hasAnyRole(['manager', 'member']) ? 'col-span-8' : 'col-span-12'
                ]">
                    <div class="bg-white rounded-[24px] shadow-sm border border-gray-50 flex-1 flex flex-col overflow-hidden">
                        <!-- Section Header -->
                        <div class="px-6 pt-6 pb-2">
                            <div class="text-[#838799] font-bold text-[15px] tracking-wide uppercase">
                                Lịch sử của tôi
                            </div>
                        </div>
                        
                        <!-- Search & Filter -->
                        <div class="px-6 py-4">
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon class="w-5 h-5 text-[#838799]" />
                                </div>
                                <input 
                                    type="text" 
                                    placeholder="Tìm kiếm lịch sử giao dịch"
                                    class="w-full bg-[#EDEEF2] border-none rounded-2xl py-3.5 pl-12 pr-12 text-sm focus:ring-0 placeholder:text-[#9EA2B3] placeholder:font-normal"
                                />
                                <div class="absolute inset-y-0 right-4 flex items-center">
                                    <FunnelIcon class="w-5 h-5 text-[#838799] cursor-pointer" />
                                </div>
                            </div>
                        </div>

                        <!-- History List -->
                        <div class="flex-1 overflow-y-auto">
                            <div v-for="i in 2" :key="i" 
                                class="flex items-center justify-between mx-6 py-5 border-b border-[#dcdee6] hover:bg-gray-50/30 transition-colors cursor-pointer last:border-b-0">
                                <div class="flex items-center space-x-4">
                                    <div class="w-11 h-11 rounded-full bg-[#E5F7ED] flex items-center justify-center text-[#10B981]">
                                        <div class="relative">
                                            <div class="w-6 h-6 border-2 border-[#10B981] rounded-full flex items-center justify-center">
                                                <span class="font-bold text-[13px]">$</span>
                                            </div>
                                            <div class="absolute -bottom-0.5 -right-0.5 bg-white rounded-full">
                                                <CheckCircleIcon class="w-4 h-4 text-[#10B981]" />
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-bold text-[#1F2937] text-[15px]">Quỹ tháng {{ 11 - i }}/2024</p>
                                        <p class="text-[12px] text-[#10B981] font-medium mt-0.5">Hoàn tất: 05/0{{ 10 - i }}/2024</p>
                                    </div>
                                </div>
                                <span class="font-bold text-[#1F2937] text-[15px]">200.000đ</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR Code Modal -->
            <Transition name="fade">
                <div v-if="showQRModal" 
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                    @click.self="showQRModal = false">
                    <Transition name="scale">
                        <div v-if="showQRModal" 
                            :class="[
                                'bg-white rounded-[24px] w-full transition-all duration-300 flex flex-col p-8 relative shadow-2xl overflow-hidden',
                                hasAnyRole(['admin', 'secretary', 'treasurer']) ? 'max-w-[850px] min-h-[600px]' : 'max-w-[390px] h-[85vh]'
                            ]">
                            <!-- Modal Close -->
                            <button 
                                @click="showQRModal = false"
                                class="absolute right-8 top-8 text-gray-400 hover:text-gray-600 transition-colors z-10">
                                <XMarkIcon class="w-7 h-7" />
                            </button>

                            <!-- Modal Header -->
                            <div class="mb-6 flex-shrink-0">
                                <h2 class="text-[22px] font-bold text-[#2D3139] mb-4">Mã QR</h2>
                            </div>

                            <div :class="['flex min-h-0 gap-10', hasAnyRole(['admin', 'secretary', 'treasurer']) ? 'flex-row' : 'flex-col']">
                                <!-- Left Column: MÃ QR HIỆN CÓ -->
                                <div :class="[hasAnyRole(['admin', 'secretary', 'treasurer']) ? 'w-[45%] flex flex-col' : 'flex-1 flex flex-col min-h-0']">
                                    <!-- Section Subtitle -->
                                    <div class="mb-6 flex-shrink-0">
                                        <div class="flex items-center space-x-2 text-[#838799] text-sm font-semibold tracking-wider uppercase">
                                            <span>MÃ QR HIỆN CÓ</span>
                                            <span>•</span>
                                            <span>{{ qrList.length }}</span>
                                        </div>
                                    </div>

                                    <!-- Swiper Container -->
                                    <div class="flex-1 overflow-hidden relative mb-6 min-h-0">
                                        <div 
                                            class="flex h-full transition-transform duration-500 ease-out"
                                            :style="{ transform: `translateX(-${currentIndex * 100}%)` }">
                                            <div v-for="(qr, index) in qrList" :key="index" class="min-w-full h-full p-1">
                                                <!-- QR Content Card -->
                                                <div class="bg-white border border-[#F2F3F5] rounded-[24px] p-6 h-full flex flex-col items-center shadow-sm">
                                                    <h3 class="text-[20px] font-bold text-[#1F2937] mb-4 truncate w-full text-center">{{ qr.title }}</h3>
                                                    
                                                    <!-- QR Placeholder -->
                                                    <div class="w-full aspect-square bg-[#F8FAFC] rounded-2xl flex items-center justify-center mb-4 overflow-hidden border border-gray-100 flex-shrink min-h-0">
                                                        <img src="@/assets/images/qr_code.svg" class="w-2/3 h-2/3 opacity-90" :alt="qr.title" />
                                                    </div>

                                                    <div class="text-center mb-4 flex-shrink-0">
                                                        <div class="flex items-center justify-center space-x-1.5 mb-1">
                                                            <span class="text-[14px] font-normal text-[#1F2937]">VNĐ</span>
                                                            <span class="text-[20px] font-bold text-[#4392E0]">{{ qr.amount }}</span>
                                                        </div>
                                                        <p class="text-[14px] text-[#838799] font-normal">{{ qr.description }}</p>
                                                    </div>

                                                    <!-- Action Buttons -->
                                                    <div class="flex items-center justify-center space-x-4 w-full mt-auto">
                                                        <button class="w-12 h-12 rounded-full bg-[#F3F4F6] flex items-center justify-center text-[#141519] hover:bg-gray-200 transition-colors">
                                                            <ArrowDownTrayIcon class="w-5 h-5" />
                                                        </button>
                                                        <button class="w-12 h-12 rounded-full bg-[#F3F4F6] flex items-center justify-center text-[#141519] hover:bg-gray-200 transition-colors">
                                                            <ShareIcon class="w-5 h-5" />
                                                        </button>
                                                        <button class="w-12 h-12 rounded-full bg-[#F3F4F6] flex items-center justify-center hover:bg-gray-200 transition-colors text-[#141519]">
                                                            <TrashIcon class="w-5 h-5" />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pagination & Navigation -->
                                    <div class="flex items-center justify-center space-x-6 flex-shrink-0">
                                        <button 
                                            @click="prevQR"
                                            :disabled="currentIndex === 0"
                                            :class="['transition-opacity duration-300', currentIndex === 0 ? 'opacity-20 cursor-not-allowed' : 'text-[#D72D36] hover:opacity-70']">
                                            <ChevronLeftIcon class="w-6 h-6" />
                                        </button>
                                        <div class="flex space-x-2">
                                            <div 
                                                v-for="(_, index) in qrList" 
                                                :key="index"
                                                @click="currentIndex = index"
                                                :class="[
                                                    'h-1.5 rounded-full transition-all duration-300 cursor-pointer',
                                                    currentIndex === index ? 'w-8 bg-[#D72D36]' : 'w-1.5 bg-[#EDEEF2]'
                                                ]">
                                            </div>
                                        </div>
                                        <button 
                                            @click="nextQR"
                                            :disabled="currentIndex === qrList.length - 1"
                                            :class="['transition-opacity duration-300', currentIndex === qrList.length - 1 ? 'opacity-20 cursor-not-allowed' : 'text-[#D72D36] hover:opacity-70']">
                                            <ChevronRightIcon class="w-6 h-6" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Right Column: THÊM MÃ QR MỚI (Admin only) -->
                                <div v-if="hasAnyRole(['admin', 'secretary', 'treasurer'])" class="w-[55%] flex flex-col">
                                    <div class="mb-6">
                                        <h2 class="text-[14px] font-bold text-[#838799] tracking-wider uppercase">THÊM MÃ QR MỚI</h2>
                                    </div>
                                    
                                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                                        
                                        <!-- Upload Area -->
                                        <div 
                                            class="w-full h-44 bg-white border-2 border-dashed border-gray-200 rounded-[4px] flex flex-col items-center justify-center cursor-pointer hover:border-[#D72D36] transition-colors mb-6 group relative overflow-hidden"
                                            @click="triggerFileInput"
                                        >
                                            <input 
                                                type="file" 
                                                ref="fileInput" 
                                                class="hidden" 
                                                accept="image/*" 
                                                @change="handleFileUpload"
                                            />
                                            
                                            <!-- Image Preview -->
                                            <template v-if="previewImage">
                                                <img :src="previewImage" class="w-full h-full object-contain" />
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <button 
                                                        @click.stop="removePreview"
                                                        class="bg-white/20 hover:bg-white/40 p-2 rounded-full backdrop-blur-md transition-colors"
                                                    >
                                                        <TrashIcon class="w-6 h-6 text-white" />
                                                    </button>
                                                </div>
                                            </template>

                                            <!-- Placeholder -->
                                            <template v-else>
                                                <div class="w-12 h-12 bg-[#F3F4F6] rounded-full flex items-center justify-center mb-3 group-hover:bg-[#FEE2E2] transition-colors">
                                                    <PhotoIcon class="w-6 h-6 text-gray-500 group-hover:text-[#D72D36]" />
                                                </div>
                                                <p class="font-bold text-[#1F2937]">Nhấn để tải ảnh lên</p>
                                                <p class="text-xs text-[#838799] mt-1">PNG, JPG, GIF (Tối đa 5MB)</p>
                                            </template>
                                        </div>

                                        <!-- Form Fields -->
                                        <div class="space-y-6">
                                            <div>
                                                <label class="block text-sm font-bold text-[#1F2937] mb-2 uppercase tracking-wide">Số tiền</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                                                        <span class="text-[#9EA2B3] font-bold text-sm">VNĐ</span>
                                                    </div>
                                                    <input 
                                                        type="text" 
                                                        v-model="newQRAmount"
                                                        placeholder="0"
                                                        class="w-full bg-[#EDEEF2] border-none rounded-[4px] py-3 pl-14 pr-4 font-bold text-[#1F2937] focus:ring-0 placeholder:text-[#9EA2B3]"
                                                    />
                                                </div>
                                            </div>

                                            <div>
                                                <div class="flex items-center justify-between mb-2">
                                                    <label class="block text-sm font-bold text-[#1F2937] uppercase tracking-wide">Nội dung</label>
                                                    <span class="text-[10px] text-[#9EA2B3] font-normal uppercase">{{ newQRDescription.length }}/300</span>
                                                </div>
                                                <div class="relative">
                                                    <div class="absolute top-4 left-4 pointer-events-none">
                                                        <PencilSquareIcon class="w-5 h-5 text-[#9EA2B3]" />
                                                    </div>
                                                    <textarea 
                                                        v-model="newQRDescription"
                                                        rows="3"
                                                        placeholder="VD: Pickleball SGP - Quy thang 10"
                                                        class="w-full bg-[#EDEEF2] border-none rounded-[4px] py-5 pl-12 pr-4 text-sm focus:ring-0 placeholder:text-[#9EA2B3] resize-none"
                                                    ></textarea>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-bold text-[#1F2937]">Áp dụng cho các CLB khác</p>
                                                    <p class="text-[12px] text-[#838799]">Gửi thông báo tới các CLB mà bạn làm quản trị viên</p>
                                                </div>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" v-model="applyToOtherClubs" class="sr-only peer">
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#D72D36]"></div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <button class="mx-auto w-fit px-10 py-3 bg-[#D72D36] text-white rounded-[4px] font-bold text-lg hover:bg-[#b91c1c] transition-colors mt-6 flex items-center justify-center">
                                        Lưu mã QR
                                     </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <!-- Create Fund Modal -->
            <ClubCreateFundModal 
                v-model:isOpen="showCreateFundModal"
                :club="club"
                @submit="handleSubmitFund"
            />


            <!-- Floating Action Buttons -->
            <div class="fixed bottom-8 right-8 flex flex-col space-y-4" v-if="hasAnyRole(['admin', 'secretary', 'treasurer'])">
                <button class="w-12 h-12 bg-[#2D3139] text-white rounded-full flex items-center justify-center shadow-lg hover:bg-black transition-all">
                    <MinusIcon class="w-6 h-6" />
                </button>
                <button 
                    @click="showCreateFundModal = true"
                    class="w-12 h-12 bg-[#E36C72] text-white rounded-full flex items-center justify-center shadow-lg hover:bg-[#d05a60] transition-all"
                >
                    <PlusIcon class="w-6 h-6" />
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
import Background from '@/assets/images/club-default-thumbnail.svg?url'
import ClubFundSkeleton from '@/components/molecules/ClubFundSkeleton.vue'
import ClubCreateFundModal from '@/components/pages/club/partials/ClubCreateFundModal.vue'
import {
    ArrowDownIcon,
    ArrowLeftIcon,
    ArrowUpIcon,
    CalendarIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    CheckCircleIcon,
    PlusIcon,
    MinusIcon,
    BanknotesIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
    ShareIcon,
    TrashIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    PhotoIcon,
    PencilSquareIcon,
    TrophyIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'
import { useRouter, useRoute } from 'vue-router'
import { onMounted, ref, computed } from 'vue'
import QRCodeIcon from "@/assets/images/qr_code.svg";
import * as ClubService from '@/service/club.js'
import { toast } from 'vue3-toastify'
import { useUserStore } from '@/store/auth'
import { storeToRefs } from 'pinia'

const userStore = useUserStore()
const { getUser } = storeToRefs(userStore)
const router = useRouter()
const route = useRoute()
const club = ref([]);
const clubId = ref(route.params.id);
const showQRModal = ref(false)
const showCreateFundModal = ref(false)
const currentIndex = ref(0)
const isInitialLoading = ref(true)
const fileInput = ref(null)
const previewImage = ref(null)

// Add New QR Form State
const newQRAmount = ref('')
const newQRDescription = ref('')
const applyToOtherClubs = ref(false)

const triggerFileInput = () => {
    fileInput.value?.click()
}

const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
        // Create preview URL
        previewImage.value = URL.createObjectURL(file)
        console.log('File selected:', file.name)
    }
}

const removePreview = () => {
    previewImage.value = null
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const currentUserMember = computed(() => {
    return club.value?.members?.find(member => member.user_id === getUser.value.id) || null
})

const hasAnyRole = (roles = []) => {
    return roles.includes(currentUserMember.value?.role)
}

const qrList = ref([
    {
        title: 'Quỹ tháng 10/2024',
        amount: '200.000',
        description: 'Pickleball SGP - Quy thang 10'
    },
    {
        title: 'Quỹ tháng 11/2024',
        amount: '200.000',
        description: 'Pickleball SGP - Quy thang 11'
    },
    {
        title: 'Quỹ tháng 12/2024',
        amount: '200.000',
        description: 'Pickleball SGP - Quy thang 12'
    }
])

const nextQR = () => {
    if (currentIndex.value < qrList.value.length - 1) {
        currentIndex.value++
    }
}

const prevQR = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--
    }
}



const handleSubmitFund = (data) => {
    console.log('Received fund submission:', data)
    // Add logic here to call API
    showCreateFundModal.value = false
}

const goBack = () => {
    router.back()
}

const getClubDetail = async () => {
    try {
        const response = await ClubService.clubDetail(clubId.value)
        club.value = response
    } catch (error) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi lấy thông tin câu lạc bộ')
    }
}

onMounted(async () => {
    if (!clubId.value) {
        isInitialLoading.value = false;
        return;
    }
    
    isInitialLoading.value = true;
    await getClubDetail();

    // Simulate loading delay
    setTimeout(() => {
        isInitialLoading.value = false
    }, 1000)
})
</script>
<style scoped>
.bg-club-default {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-color: #000;
    min-height: 286px;
}

/* Custom scrollbar for a cleaner look */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.scale-enter-active,
.scale-leave-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.scale-enter-from,
.scale-leave-to {
    opacity: 0;
    transform: scale(0.9) translateY(20px);
}
</style>