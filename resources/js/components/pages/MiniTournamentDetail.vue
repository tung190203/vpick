<template>
    <div class="p-4 max-w-6xl mx-auto">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
            <!-- Main content -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Tabs -->
                <div class="bg-white rounded-[8px] shadow p-5">
                    <div class="flex items-center justify-center space-x-4 flex-wrap">
                        <template v-for="(tab, index) in tabs" :key="tab.name">
                            <button @click="activeTab = tab.name"
                                class="text-sm font-semibold transition-all flex items-center" :class="[
                                    activeTab === tab.name
                                        ? 'bg-red-600 text-white rounded-full px-5 py-2 shadow-sm'
                                        : 'text-gray-700 hover:text-red-600 px-5 py-2'
                                ]">
                                {{ tab.label }}
                            </button>
                            <ChevronRightIcon v-if="index < tabs.length - 1"
                                class="w-4 h-4 text-gray-500 font-semibold mx-2" />
                        </template>
                    </div>
                </div>

                <!-- Tab content -->
                <div class="bg-white rounded-[8px] shadow min-h-[200px] transition-all duration-300"
                    :class="[activeTab === 'chat' ? 'p-0' : 'p-5']">
                    <Transition name="fade" mode="out-in">
                        <!-- Tab 1: Chi tiết -->
                        <div v-if="activeTab === 'detail'" key="detail">
                            <!-- Title -->
                            <div class="flex flex-wrap items-center gap-2 mb-4">
                                <h3 class="font-semibold text-gray-900 text-[20px]">
                                    {{ mini.name }}
                                </h3>
                                <LockClosedIcon class="w-5 h-5" />
                            </div>

                            <!-- Time + Location -->
                            <div class="space-y-2 mb-4">
                                <div class="flex items-start gap-2">
                                    <CalendarDaysIcon class="w-5 h-5 shrink-0" />
                                    <div>
                                        <p class="text-gray-900 font-medium">{{ formatEventDate(mini.starts_at) }}</p>
                                        <p class="text-gray-500 text-sm">{{ mini.duration_minutes }} phút</p>
                                        <a href="#" class="text-blue-600 text-sm font-medium hover:underline">Thêm vào
                                            lịch</a>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2">
                                    <MapPinIcon class="w-5 h-5 shrink-0" />
                                    <div>
                                        <p class="text-gray-900 font-medium">
                                            {{ mini.competition_location?.name }}
                                        </p>
                                        <p class="text-gray-500 text-sm">{{ mini.competition_location?.address }}</p>
                                        <a href="#" class="text-blue-600 text-sm font-medium hover:underline">Hiển thị
                                            trên bản đồ</a>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <CircleStackIcon class="w-6 h-6" />
                                    <span class="text-gray-800">{{ mini.match_type_text }}</span>
                                </div>
                            </div>

                            <!-- Info boxes -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                <div
                                    class="border rounded p-3 flex items-center gap-3 hover:shadow-md transition cursor-pointer">
                                    <CircleStackIcon class="w-6 h-6 text-red-600" />
                                    <div>
                                        <p class="font-medium text-gray-800">{{ mini.min_rating }} - {{ mini.max_rating
                                        }} </p>
                                        <p class="text-sm text-gray-500">Trung bình điểm DUPR</p>
                                    </div>
                                </div>

                                <div
                                    class="border rounded p-3 flex items-center gap-3 hover:shadow-md transition cursor-pointer">
                                    <UserIcon class="w-6 h-6 text-red-600" />
                                    <div>
                                        <p class="font-medium text-gray-800">{{ mini.gender_policy_text }}</p>
                                        <p class="text-sm text-gray-500">{{ mini.age_group_text }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mb-4">
                                <label class="block text-gray-800 font-medium mb-2">Thể lệ kèo đấu</label>
                                <textarea rows="4" placeholder="Thêm ghi chú cho kèo đấu"
                                    class="w-full rounded border-gray-300 bg-[#EDEEF2] focus:ring-red-500 focus:border-red-500 text-gray-700 text-sm p-3 resize-none">{{
                                        mini.description }}</textarea>
                                <a href="#"
                                    class="text-blue-600 text-sm font-medium hover:underline mt-2 inline-block">Thêm ghi
                                    chú</a>
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-wrap gap-3">
                                <button
                                    class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-4 py-2 rounded-md transition">
                                    Chỉnh sửa
                                    <PencilIcon class="w-4 h-4" />
                                </button>

                                <button
                                    class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-4 py-2 rounded-md transition">
                                    Hủy bỏ
                                    <XCircleIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Tab 2: Người tham gia -->
                        <div v-else-if="activeTab === 'participants'" key="participants">
                            <div class="flex items-center justify-start border border-[#BBBFCC] rounded px-3 py-2 mb-4">
                                <ArrowTrendingUpIcon class="w-5 h-5 mr-2" />
                                <p class="font-semibold">Quản lý điểm DUPR</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
                                <div class="flex items-center justify-start border border-[#BBBFCC] rounded px-3 py-2">
                                    <CreditCardIcon class="w-5 h-5 mr-2" />
                                    <p class="font-semibold">Thanh toán</p>
                                </div>
                                <div
                                    class="flex items-center justify-between border border-[#BBBFCC] rounded px-3 py-2">
                                    <p class="font-semibold">Duyệt tự động</p>
                                    <button @click="autoApprove = !autoApprove"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                        :class="autoApprove ? 'bg-[#D72D36]' : 'bg-gray-300'">
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            :class="autoApprove ? 'translate-x-6' : 'translate-x-1'" />
                                    </button>
                                </div>
                            </div>
                            <div class="border border-[#BBBFCC] rounded-lg my-4 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-[#6B6F80] uppercase text-sm">
                                        NGƯỜI TỔ CHỨC • {{ mini?.staff?.organizer?.length || 0 }}
                                    </h4>
                                </div>
                                <div v-if="mini?.staff?.organizer?.length">
                                    <div class="grid grid-cols-2 sm:grid-cols-6 lg:grid-cols-6 gap-4">
                                        <UserCard v-for="(item, index) in mini.staff.organizer" :key="index"
                                            :name="item.user.full_name" :avatar="item.user.avatar_url" :rating="getUserScore(item.user)"
                                            status="approved" />
                                        <UserCard :empty="true" />
                                    </div>
                                </div>
                            </div>
                            <div class="border border-[#BBBFCC] rounded-lg my-4 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-[#6B6F80] uppercase text-sm">
                                        NGƯỜI THAM GIA • {{ mini?.participants?.users?.length || 0 }} / {{
                                            mini.max_players
                                        }}
                                    </h4>
                                    <span class="text-[#207AD5] text-xs font-semibold cursor-pointer">Mời bạn bè</span>
                                </div>
                                <div v-if="mini?.participants?.users?.length">
                                    <div class="grid grid-cols-2 sm:grid-cols-6 lg:grid-cols-6 gap-4">
                                        <UserCard v-for="(item, index) in mini.participants.users" :key="index"
                                            :name="item.user.full_name" :avatar="item.user.avatar_url" :rating="getUserScore(item.user)"
                                            :status="item.is_confirmed == true ? 'approved': 'pending'" />
                                        <UserCard v-if="mini?.participants?.users?.length < mini.max_players"
                                            :empty="true" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3 -->
                        <div v-else-if="activeTab === 'matches'" key="matches">
                            <div class="flex justify-start gap-2 mb-4">
                                <button v-for="tab in subtabs" :key="tab.id" @click="subActiveTab = tab.id" :class="[
                                    'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
                                    subActiveTab === tab.id
                                        ? 'bg-red-500 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]">
                                    {{ tab.label }}
                                </button>
                            </div>
                            <template v-if="subActiveTab === 'ranking'">
                                <p>Hiển thị bảng xếp hạng tại đây.</p>
                            </template>
                            <template v-else-if="subActiveTab === 'match'">
                                <div class="grid grid-cols-2 gap-4">
                                    <div
                                        class="border border-[#BBBFCC]  p-4 rounded-lg cursor-pointer hover:shadow-md transition">
                                        <MatchCard matchTitle="Trận đấu số 1" matchTime="19:00 - 21/9/25"
                                            courtName="Sân 2" :team1="[
                                                { id: 1, name: 'John', avatar: 'https://i.pravatar.cc/150?img=5', rating: 5.0, status: 'approved' },
                                                { id: 2, name: 'Jane', avatar: 'https://i.pravatar.cc/150?img=6', rating: 4.8, status: 'pending' }
                                            ]" :team2="[
                                                { id: 3, name: 'Mike', avatar: 'https://i.pravatar.cc/150?img=7', rating: 4.3, status: 'rejected' },
                                                { id: 4, name: 'Lisa', avatar: 'https://i.pravatar.cc/150?img=8', rating: 4.6, status: 'approved' }
                                            ]" :sets="[
                                                { team1: '15', team2: '13' },
                                                { team1: '15', team2: '10' },
                                                { team1: '00', team2: '00' }
                                            ]" />
                                    </div>
                                    <div
                                        class="border border-[#BBBFCC]  p-4 rounded-lg cursor-pointer hover:shadow-md transition">
                                        <MatchCard matchTitle="Trận đấu số 2" matchTime="19:00 - 21/9/25"
                                            courtName="Sân 2" :team1="[
                                                { id: 1, name: 'John', avatar: 'https://i.pravatar.cc/150?img=5', rating: 5.0, status: 'approved' },
                                                { id: 2, name: 'Jane', avatar: 'https://i.pravatar.cc/150?img=6', rating: 4.8, status: 'approved' }
                                            ]" :team2="[
                                                { id: 3, name: 'Mike', avatar: 'https://i.pravatar.cc/150?img=7', rating: 4.3, status: 'approved' },
                                                { id: 4, name: 'Lisa', avatar: 'https://i.pravatar.cc/150?img=8', rating: 4.6, status: 'approved' }
                                            ]" :sets="[
                                                { team1: '15', team2: '13' },
                                                { team1: '15', team2: '10' },
                                                { team1: '00', team2: '00' }
                                            ]" />
                                    </div>
                                    <div
                                        class="border border-[#BBBFCC]  p-4 rounded-lg cursor-pointer hover:shadow-md transition">
                                        <MatchCard matchTitle="Trận đấu số 3" matchTime="19:00 - 21/9/25"
                                            courtName="Sân 2" :team1="[
                                                { id: 1, name: 'John', avatar: 'https://i.pravatar.cc/150?img=5', rating: 5.0, status: 'approved' },
                                                { id: 2, name: 'Jane', avatar: 'https://i.pravatar.cc/150?img=6', rating: 4.8, status: 'approved' }
                                            ]" :team2="[
                                                { id: 3, name: 'Mike', avatar: 'https://i.pravatar.cc/150?img=7', rating: 4.3, status: 'approved' },
                                                { id: 4, name: 'Lisa', avatar: 'https://i.pravatar.cc/150?img=8', rating: 4.6, status: 'approved' }
                                            ]" :sets="[
                                                { team1: '15', team2: '13' },
                                                { team1: '15', team2: '10' },
                                                { team1: '00', team2: '00' }
                                            ]" />
                                    </div>
                                    <div
                                        class="border border-[#BBBFCC]  p-4 rounded-lg cursor-pointer hover:shadow-md transition">
                                        <MatchCard matchTitle="Trận đấu số 4" matchTime="19:00 - 21/9/25"
                                            courtName="Sân 2" :team1="[
                                                { id: 1, name: 'John', avatar: 'https://i.pravatar.cc/150?img=5', rating: 5.0, status: 'approved' },
                                                { id: 2, name: 'Jane', avatar: 'https://i.pravatar.cc/150?img=6', rating: 4.8, status: 'approved' }
                                            ]" :team2="[
                                                { id: 3, name: 'Mike', avatar: 'https://i.pravatar.cc/150?img=7', rating: 4.3, status: 'approved' },
                                            ]" :sets="[
                                                { team1: '15', team2: '13' },
                                                { team1: '15', team2: '10' },
                                                { team1: '00', team2: '00' }
                                            ]" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-5 gap-4 mt-4">
                                    <div class="col-span-2 flex justify-start items-center gap-4">
                                        <div>
                                            <span class="text-[#6B6F80] text-xs">Tổng số trận</span>
                                            <p class="text[#3E414C] font-semibold">4 trận</p>
                                        </div>
                                        <div>
                                            <span class="text-[#6B6F80] text-xs">Tổng thời lượng</span>
                                            <p class="text[#3E414C] font-semibold">4 tiếng</p>
                                        </div>
                                    </div>
                                    <div class="col-span-3 flex justify-end items-center gap-4">
                                        <button @click="showCreateMatchModal = true"
                                            class="bg-[#D72D36] border border-1 border-[#D72D36] text-white px-4 py-2 rounded hover:bg-red-700 transition">
                                            Thêm trận đấu
                                        </button>
                                        <button disabled
                                            class="bg-[#EDEEF2] border border-1 border-[#BBBFCC] text-[#BBBFCC] px-4 py-2 rounded transition cursor-not-allowed">
                                            Huỷ kèo đã chọn (0/4)
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template v-else-if="subActiveTab === 'your-match'">
                                <div class="grid grid-cols-2 gap-4">
                                    <div
                                        class="border border-[#BBBFCC]  p-4 rounded-lg cursor-pointer hover:shadow-md transition">
                                        <MatchCard matchTitle="Trận đấu số 1" matchTime="19:00 - 21/9/25"
                                            courtName="Sân 2" :team1="[
                                                { id: 1, name: 'John', avatar: 'https://i.pravatar.cc/150?img=5', rating: 5.0, status: 'approved' },
                                                { id: 2, name: 'Jane', avatar: 'https://i.pravatar.cc/150?img=6', rating: 4.8, status: 'pending' }
                                            ]" :team2="[
                                                { id: 3, name: 'Mike', avatar: 'https://i.pravatar.cc/150?img=7', rating: 4.3, status: 'rejected' },
                                                { id: 4, name: 'Lisa', avatar: 'https://i.pravatar.cc/150?img=8', rating: 4.6, status: 'approved' }
                                            ]" :sets="[
                                                { team1: '15', team2: '13' },
                                                { team1: '15', team2: '10' },
                                                { team1: '00', team2: '00' }
                                            ]" />
                                    </div>
                                    <div
                                        class="border border-[#BBBFCC]  p-4 rounded-lg cursor-pointer hover:shadow-md transition">
                                        <MatchCard matchTitle="Trận đấu số 2" matchTime="19:00 - 21/9/25"
                                            courtName="Sân 2" :team1="[
                                                { id: 1, name: 'John', avatar: 'https://i.pravatar.cc/150?img=5', rating: 5.0, status: 'approved' },
                                                { id: 2, name: 'Jane', avatar: 'https://i.pravatar.cc/150?img=6', rating: 4.8, status: 'approved' }
                                            ]" :team2="[
                                                { id: 3, name: 'Mike', avatar: 'https://i.pravatar.cc/150?img=7', rating: 4.3, status: 'approved' },
                                                { id: 4, name: 'Lisa', avatar: 'https://i.pravatar.cc/150?img=8', rating: 4.6, status: 'approved' }
                                            ]" :sets="[
                                                { team1: '15', team2: '13' },
                                                { team1: '15', team2: '10' },
                                                { team1: '00', team2: '00' }
                                            ]" />
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Tab 4 -->
                        <div v-else key="chat" class="flex flex-col h-[70vh]">
                            <div class="flex-1 overflow-y-auto p-4 mx-10 space-y-4 ">
                                <!-- Other User Message -->
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="flex-1 max-w-[60%]">
                                        <div
                                            class="bg-[#EDEEF2] rounded-md px-4 py-3 grid grid-cols-[auto_1fr] gap-3 items-start">
                                            <div class="w-10 h-10 rounded-full overflow-hidden">
                                                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100"
                                                    alt="User" class="w-full h-full object-cover" />
                                            </div>
                                            <p class="text-gray-800 text-sm leading-relaxed">
                                                Chào bạn! Mình có thể tham gia trận đấu lúc 17:00 được không?
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current User Message -->
                                <div class="flex items-start gap-3 flex-row-reverse mb-4">
                                    <div class="flex-1 max-w-[60%] flex flex-col items-end">
                                        <div class="bg-[#f8f5ff] rounded-md border border-1 border-[#5422C6] px-4 py-3">
                                            <p class="text-[#3E414C] text-sm leading-relaxed">
                                                Được chứ! Mình đang cần thêm 1 người nữa.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="flex-1 max-w-[60%]">
                                        <div
                                            class="bg-[#EDEEF2] rounded-md px-4 py-3 grid grid-cols-[auto_1fr] gap-3 items-start">
                                            <div class="w-10 h-10 rounded-full overflow-hidden">
                                                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100"
                                                    alt="User" class="w-full h-full object-cover" />
                                            </div>
                                            <p class="text-gray-800 text-sm leading-relaxed">
                                                Chào bạn! Mình có thể tham gia trận đấu lúc 17:00 được không?
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current User Message -->
                                <div class="flex items-start gap-3 flex-row-reverse mb-4">
                                    <div class="flex-1 max-w-[60%] flex flex-col items-end">
                                        <div class="bg-[#f8f5ff] rounded-md border border-1 border-[#5422C6] px-4 py-3">
                                            <p class="text-[#3E414C] text-sm leading-relaxed">
                                                Được chứ! Mình đang cần thêm 1 người nữa.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="flex-1 max-w-[60%]">
                                        <div
                                            class="bg-[#EDEEF2] rounded-md px-4 py-3 grid grid-cols-[auto_1fr] gap-3 items-start">
                                            <div class="w-10 h-10 rounded-full overflow-hidden">
                                                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100"
                                                    alt="User" class="w-full h-full object-cover" />
                                            </div>
                                            <p class="text-gray-800 text-sm leading-relaxed">
                                                Chào bạn! Mình có thể tham gia trận đấu lúc 17:00 được không?
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current User Message -->
                                <div class="flex items-start gap-3 flex-row-reverse mb-4">
                                    <div class="flex-1 max-w-[60%] flex flex-col items-end">
                                        <div class="bg-[#f8f5ff] rounded-md border border-1 border-[#5422C6] px-4 py-3">
                                            <p class="text-[#3E414C] text-sm leading-relaxed">
                                                Được chứ! Mình đang cần thêm 1 người nữa.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="flex-1 max-w-[60%]">
                                        <div
                                            class="bg-[#EDEEF2] rounded-md px-4 py-3 grid grid-cols-[auto_1fr] gap-3 items-start">
                                            <div class="w-10 h-10 rounded-full overflow-hidden">
                                                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100"
                                                    alt="User" class="w-full h-full object-cover" />
                                            </div>
                                            <p class="text-gray-800 text-sm leading-relaxed">
                                                Chào bạn! Mình có thể tham gia trận đấu lúc 17:00 được không?
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current User Message -->
                                <div class="flex items-start gap-3 flex-row-reverse mb-4">
                                    <div class="flex-1 max-w-[60%] flex flex-col items-end">
                                        <div class="bg-[#f8f5ff] rounded-md border border-1 border-[#5422C6] px-4 py-3">
                                            <p class="text-[#3E414C] text-sm leading-relaxed">
                                                Được chứ! Mình đang cần thêm 1 người nữa.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="flex-1 max-w-[60%]">
                                        <div
                                            class="bg-[#EDEEF2] rounded-md px-4 py-3 grid grid-cols-[auto_1fr] gap-3 items-start">
                                            <div class="w-10 h-10 rounded-full overflow-hidden">
                                                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100"
                                                    alt="User" class="w-full h-full object-cover" />
                                            </div>
                                            <p class="text-gray-800 text-sm leading-relaxed">
                                                Chào bạn! Mình có thể tham gia trận đấu lúc 17:00 được không?
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current User Message -->
                                <div class="flex items-start gap-3 flex-row-reverse mb-4">
                                    <div class="flex-1 max-w-[60%] flex flex-col items-end">
                                        <div class="bg-[#f8f5ff] rounded-md border border-1 border-[#5422C6] px-4 py-3">
                                            <p class="text-[#3E414C] text-sm leading-relaxed">
                                                Được chứ! Mình đang cần thêm 1 người nữa.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="flex-shrink-0 border-t border-gray-200 flex justify-between items-center px-4 py-4">
                                <PhotoIcon class="w-8 h-8 text-[#3E414C] mr-3 cursor-pointer" />
                                <div class="flex-1 flex items-center relative">
                                    <input type="text" placeholder="Viết tin nhắn"
                                        class="w-full border border-gray-300 bg-[#edeef2] rounded-full pl-4 pr-12 py-2 focus:outline-none focus:ring-1 focus:ring-red-500 placeholder:text-[#BBBFCC] placeholder:text-sm" />
                                    <FaceSmileIcon class="w-6 h-6 text-[#3E414C] cursor-pointer absolute right-3" />
                                </div>
                                <PaperAirplaneIcon class="w-8 h-8 text-[#3E414C] ml-3 cursor-pointer" />
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-[8px] p-5 sticky top-4">
                    <div class="flex items-center justify-center mt-5 rounded-full bg-[#FFF5F5] w-20 h-20 mx-auto">
                        <UserGroupIcon class="w-10 h-10 text-[#D72D36]" />
                    </div>
                    <div class="flex flex-col items-center p-5 space-y-2">
                        <h3 class="text-gray-800 font-medium text-center text-[20px]">Chia sẻ thông tin</h3>
                        <p class="text-[#3E414C] text-sm text-center">Hãy chia sẻ thông tin tới bạn bè để cùng tham gia
                            kèo đấu</p>
                    </div>
                    <div class="flex flex-col item-center space-y-3">
                        <button
                            class="flex items-center justify-center gap-2 w-full bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-2 py-2 rounded transition">
                            Gửi link
                            <LinkIcon class="w-4 h-4 inline-block ml-1" />
                        </button>
                        <button
                            class="flex items-center justify-center gap-2 w-full bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-2 py-2 rounded transition">
                            Quét mã QR
                            <QrCodeIcon class="w-4 h-4 inline-block ml-1" />
                        </button>
                        <button
                            class="flex items-center justify-center gap-2 w-full bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-2 py-2 rounded transition">
                            Mời người chơi
                            <UsersIcon class="w-4 h-4 inline-block ml-1" />
                        </button>
                        <button @click="showInviteModal = true"
                            class="flex items-center justify-center gap-2 w-full bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-2 py-2 rounded transition">
                            Mời nhóm
                            <UserMultiple class="w-4 h-4 inline-block ml-1" />
                        </button>
                        <button
                            class="flex items-center justify-center gap-2 w-full bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-2 py-2 rounded transition">
                            Yêu cầu xác nhận KQ
                            <ClipboardDocumentCheckIcon class="w-4 h-4 inline-block ml-1" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invite Group Modal -->
        <InviteGroup v-model="showInviteModal" @invite="handleInvite" />
        <CreateMatch v-model="showCreateMatchModal" @create="handleCreateMatch" />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ArrowTrendingUpIcon, ChevronRightIcon, LinkIcon, LockClosedIcon, PaperAirplaneIcon, PhotoIcon, QrCodeIcon, UserGroupIcon } from '@heroicons/vue/24/solid'
import {
    CalendarDaysIcon,
    MapPinIcon,
    CircleStackIcon,
    UserIcon,
    CalendarIcon,
    PencilIcon,
    XCircleIcon,
    UserGroupIcon as UserMultiple,
    UsersIcon,
    CreditCardIcon,
    ClipboardDocumentCheckIcon,
    FaceSmileIcon
} from '@heroicons/vue/24/outline'
import UserCard from '@/components/molecules/UserCard.vue'
import InviteGroup from '@/components/molecules/InviteGroup.vue'
import MatchCard from '@/components/molecules/MatchCard.vue'
import CreateMatch from '@/components/molecules/CreateMatch.vue'
import * as MiniTournamnetService from '@/service/miniTournament.js'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const id = route.params.id
const mini = ref([])

const tabs = [
    { name: 'detail', label: 'Chi tiết' },
    { name: 'participants', label: 'Người tham gia' },
    { name: 'matches', label: 'Trận đấu' },
    { name: 'chat', label: 'Trò chuyện' }
]

const subtabs = [
    { id: 'ranking', label: 'Xếp hạng' },
    { id: 'match', label: 'Trận đấu' },
    { id: 'your-match', label: 'Trận đấu của bạn' }
]

const activeTab = ref('detail')
const subActiveTab = ref('ranking')
const autoApprove = ref(false)
const showInviteModal = ref(false)
const showCreateMatchModal = ref(false)

const handleInvite = (user) => {
    console.log('Invited user:', user)
}

const handleCreateMatch = (match) => {
    console.log('Created match:', match)
}

function formatEventDate(dateString) {
    const date = new Date(dateString);

    const daysOfWeek = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];

    const months = [
        'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
        'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
    ];

    const dayOfWeek = daysOfWeek[date.getDay()];
    const day = date.getDate();
    const month = months[date.getMonth()];
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');

    return `${dayOfWeek} ${day} ${month} lúc ${hours}:${minutes}`;
}

const getUserScore = (user) => {
  if (!user?.sports?.length || !mini.value?.sport?.id) {
    return '0'
  }

  const matchedSport = user.sports.find(s => s.sport_id === mini.value.sport.id)
  
  if (!matchedSport?.scores?.length) {
    return '0'
  }

  const vnduprScore = matchedSport.scores.find(sc => sc.score_type === 'vndupr_score')
  if (vnduprScore) {
    return parseFloat(vnduprScore.score_value).toFixed(1)
  }

  const personalScore = matchedSport.scores.find(sc => sc.score_type === 'personal_score')
  if (personalScore) {
    return parseFloat(personalScore.score_value).toFixed(1)
  }

  return '0'
}

onMounted(async () => {
    if (id) {
        try {
            const response = await MiniTournamnetService.getMiniTournamentById(id)
            console.log('Mini Tournament Data:', response)
            mini.value = response
            autoApprove.value = response.auto_approve
        } catch (error) {
            console.error('Error fetching mini tournament:', error)
        }
    }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 0;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>