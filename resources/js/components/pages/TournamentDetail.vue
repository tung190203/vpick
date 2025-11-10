<template>
  <div class="p-4 max-w-6xl mx-auto">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
      <div class="space-y-6 lg:col-span-2">
        <div class="bg-white rounded-[8px] shadow p-5">
          <div class="flex items-center justify-between space-x-2 flex-wrap">
            <template v-for="(tab, index) in tabs" :key="tab.name">
              <button @click="activeTab = tab.name" class="text-sm font-semibold transition-all flex items-center"
                :class="[
                  activeTab === tab.name
                    ? 'bg-red-600 text-white rounded-full px-5 py-2 shadow-sm'
                    : 'text-gray-700 hover:text-red-600 px-5 py-2'
                ]">
                {{ tab.label }}
              </button>
              <ChevronRightIcon v-if="index < tabs.length - 1" class="w-4 h-4 text-gray-500 font-semibold mx-2" />
            </template>
          </div>
        </div>

        <div class="bg-white rounded-[8px] shadow min-h-[200px] transition-all duration-300"
          :class="[activeTab === 'discuss' ? 'p-0' : 'p-5']">
          <Transition name="fade" mode="out-in">
            <div v-if="activeTab === 'detail'" key="detail">
              <div class="flex flex-wrap items-center gap-2 mb-4">
                <h3 class="font-semibold text-gray-900 text-[20px]">
                  {{ tournament.name }}
                </h3>
                <LockClosedIcon v-if="tournament.is_private" class="w-5 h-5" />
                <LockOpenIcon v-else class="w-5 h-5" />
              </div>
              <div class="py-4">
                <div v-if="preview" class="relative">
                  <div class="relative rounded-xl overflow-hidden h-72">
                    <img :src="preview" alt="Preview" class="w-full h-full object-cover" />
                    <button @click="handleRemove" v-if="tournament?.created_by?.id == getUser.id"
                      class="absolute top-2 right-2 bg-white rounded-full p-1.5 shadow-lg hover:bg-gray-100 transition-colors">
                      <XMarkIcon class="w-5 h-5 text-gray-700" />
                    </button>
                  </div>
                </div>

                <div v-else @click="handleClick" @dragover.prevent="handleDragOver" @dragleave.prevent="handleDragLeave"
                  @drop.prevent="handleDrop" :class="[
                    'relative border rounded-xl text-center cursor-pointer transition-all duration-200 bg-white/50 h-72 flex flex-col justify-center items-center',
                    isDragging ? 'border-rose-500 bg-white' : 'border-[#D72D36] hover:bg-white hover:border-red-500'
                  ]">

                  <div class="text-center mb-4">
                    <h2 class="font-semibold text-gray-800 mb-1">
                      Thêm ảnh bia giải đấu
                    </h2>
                    <p class="text-gray-600 text-xs">
                      Kích thước ảnh tải lên không quá 5MB
                    </p>
                  </div>
                  <div :class="[
                    'p-4 rounded-md border border-dashed transition-colors',
                    isDragging ? 'border-[#D72D36] bg-white' : 'border-[#D72D36] bg-white'
                  ]">
                    <ArrowUpTrayIcon class="w-8 h-8 text-[#D72D36]" />
                  </div>

                  <input ref="fileInput" type="file" accept="image/*" @change="handleFileInputChange" class="hidden" />
                </div>
              </div>

              <div class="space-y-2 mb-4">
                <div class="flex items-start justify-between gap-2">
                  <div class="flex gap-2">
                    <CalendarDaysIcon class="w-5 h-5 shrink-0" />
                    <div>
                      <p class="text-gray-900 font-medium">{{ formatEventDate(tournament.start_date) }}</p>
                      <p class="text-gray-500 text-sm">{{ tournament.duration }} phút</p>
                    </div>
                  </div>
                  <a href="#" class="text-blue-600 text-sm font-medium hover:underline">Thêm vào
                    lịch</a>
                </div>

                <div class="flex items-start justify-between gap-2">
                  <div class="flex gap-2">
                    <MapPinIcon class="w-5 h-5 shrink-0" />
                    <div>
                      <p class="text-gray-900 font-medium">
                        {{ tournament.competition_location?.name }}
                      </p>
                      <p class="text-gray-500 text-sm">{{ tournament.competition_location?.address }}</p>
                    </div>
                  </div>
                  <a href="#" class="text-blue-600 text-sm font-medium hover:underline">Hiển thị
                    trên bản đồ</a>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <div class="border rounded p-3 flex items-center gap-3 hover:shadow-md transition cursor-pointer">
                  <UsersIcon class="w-6 h-6 text-red-600" />
                  <div>
                    <p class="font-medium text-gray-800">{{ tournament.max_team }} Đội</p>
                    <p class="text-sm text-gray-500">{{ tournament.player_per_team }} thành viên mỗi đội</p>
                  </div>
                </div>

                <div class="border rounded p-3 flex items-center gap-3 hover:shadow-md transition cursor-pointer">
                  <template v-if="tournament.tournament_types && tournament.tournament_types.length">
                    <img :src="displayFormat.icon" alt="" class="w-6 h-6"
                      style="filter: invert(17%) sepia(75%) saturate(3884%) hue-rotate(346deg) brightness(99%) contrast(81%);" />
                    <div v-for="type in tournament.tournament_types">
                      <p class="font-medium text-gray-800">{{ type.format_label }}</p>
                      <p class="text-sm text-gray-500">Tổng {{ type.total_matches ?? 0 }} trận</p>
                    </div>
                  </template>
                  <template v-else>
                    <AdjustmentsVerticalIcon class="w-6 h-6 text-red-600" />
                    <div>
                      <p class="font-medium text-gray-800">Chưa chọn thể thức thi đấu</p>
                    </div>
                  </template>
                </div>

                <div class="border rounded p-3 flex items-center gap-3 hover:shadow-md transition cursor-pointer">
                  <CircleStackIcon class="w-6 h-6 text-red-600" />
                  <div>
                    <p class="font-medium text-gray-800">
                      {{ tournament.min_rating != null && tournament.max_rating != null
                        ? `${tournament.min_rating} - ${tournament.max_rating}`
                        : 'Không giới hạn'
                      }}
                    </p>
                    <p class="text-sm text-gray-500">Trung bình điểm DUPR</p>
                  </div>
                </div>
                <div class="border rounded p-3 flex items-center gap-3 hover:shadow-md transition cursor-pointer">
                  <UserIcon class="w-6 h-6 text-red-600" />
                  <div>
                    <p class="font-medium text-gray-800">{{ tournament.gender_policy_text }}</p>
                    <p class="text-sm text-gray-500">{{ tournament.age_group_text }}</p>
                  </div>
                </div>
                <div class="border rounded p-3 flex items-center gap-3 hover:shadow-md transition cursor-pointer">
                  <CalendarDaysIcon class="w-6 h-6" />
                  <div>
                    <p class="font-medium text-gray-800">Mở đăng kí</p>
                    <p class="text-sm text-gray-500">{{ formatDateTime(tournament.registration_open_at) }}</p>
                  </div>
                </div>
                <div class="border rounded p-3 flex items-center gap-3 hover:shadow-md transition cursor-pointer">
                  <CalendarDaysIcon class="w-6 h-6" />
                  <div>
                    <p class="font-medium text-gray-800">Hạn chót đăng kí</p>
                    <p class="text-sm text-gray-500">{{ formatDateTime(tournament.registration_closed_at) }}</p>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <div v-if="!tournament.description && !isEditingDescription">
                  <a href="javascript:void(0)" @click="setupDescription"
                    class="text-blue-600 text-sm font-medium hover:underline mt-2 inline-block">Thêm ghi
                    chú</a>
                </div>

                <div v-if="isEditingDescription || tournament.description">
                  <label class="block font-medium text-gray-700 mb-2">Ghi chú về giải đấu</label>
                  <textarea rows="4" placeholder="Ghi chú về giải đấu..." v-model="descriptionModel"
                    class="w-full border border-gray-300 rounded-md p-3 resize-none focus:outline-none focus:ring-1 focus:ring-red-500 placeholder:text-gray-400"
                    :class="tournament?.created_by?.id == getUser.id ? '' : 'bg-gray-100 cursor-not-allowed'"
                    :readonly="tournament?.created_by?.id != getUser.id"></textarea>
                  <Transition name="fade">
                    <button v-if="isDescriptionChanged" @click="saveDescription" :disabled="!isDescriptionChanged"
                      :class="[
                        'mt-2 px-4 py-2 font-medium rounded-md transition-colors shadow-md',
                        isDescriptionChanged
                          ? 'bg-red-600 text-white hover:bg-red-700'
                          : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                      ]">
                      {{ tournament.description ? 'Lưu thay đổi' : 'Thêm ghi chú' }}
                    </button>
                  </Transition>
                </div>
              </div>
              <div class="flex flex-wrap gap-3" v-if="tournament?.created_by?.id == getUser.id">
                <button @click="publicTournament"
                  class="flex items-center justify-center gap-2 bg-[#D72D36] hover:bg-white text-white hover:text-[#D72D36] border hover:border-[#D72D36] font-medium px-6 py-2 rounded-md transition">
                  {{ tournament?.status == 1 ? 'Công bố giải' : 'Huỷ công bố' }}
                </button>
                <button @click="goToEditPage"
                  class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-4 py-2 rounded-md transition">
                  Chỉnh sửa
                  <PencilIcon class="w-4 h-4" />
                </button>

                <button @click="confirmRemoval"
                  class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-4 py-2 rounded-md transition">
                  Hủy bỏ
                  <XCircleIcon class="w-5 h-5" />
                </button>
              </div>
            </div>

            <div v-else-if="activeTab === 'list'" key="list">
              <div class="flex items-center justify-between border-b border-[#BBBFCC] px-3 py-4 mb-4"
                v-if="tournament?.created_by?.id == getUser.id">
                <p class="font-semibold uppercase">Duyệt yêu cầu tham gia tự động</p>
                <button @click="toggleAutoApprove"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                  :class="autoApprove ? 'bg-[#D72D36]' : 'bg-gray-300'">
                  <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                    :class="autoApprove ? 'translate-x-6' : 'translate-x-1'" />
                </button>
              </div>
              <div class="flex justify-start gap-2 mb-4">
                <button v-for="tab in listTabs" :key="tab.id" @click="listActiveTab = tab.id" :class="[
                  'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
                  listActiveTab === tab.id
                    ? 'bg-red-500 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]">
                  {{ tab.label }}
                </button>
              </div>
              <template v-if="listActiveTab === 'staffs'">
                <div class="border border-[#BBBFCC] rounded-lg my-4 p-4">
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-[#6B6F80] uppercase text-sm">
                      NGƯỜI TỔ CHỨC • {{ tournament?.tournament_staff?.length || 0 }}
                    </h4>
                  </div>
                  <div v-if="tournament?.tournament_staff?.length">
                    <div class="grid grid-cols-2 sm:grid-cols-6 lg:grid-cols-6 gap-4">
                      <UserCard v-for="(item, index) in tournament.tournament_staff" :key="index"
                        :name="item.staff.name" :avatar="item.staff.avatar" :rating="getUserScore(item.staff)"
                        status="approved" />
                      <UserCard :empty="true" @click="showInviteStaffModal = true"
                        v-if="tournament?.created_by?.id == getUser.id" />
                    </div>
                  </div>
                </div>
              </template>
              <template v-else-if="listActiveTab === 'paticipants'">
                <div class="border border-[#BBBFCC] rounded-lg my-4 p-4">
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-[#6B6F80] uppercase text-sm">
                      NGƯỜI THAM GIA • {{ tournament?.tournamnet_participants?.length || 0 }} / {{
                        tournament.max_team * tournament.player_per_team
                      }}
                    </h4>
                    <span class="text-[#207AD5] text-xs font-semibold cursor-pointer"
                      v-if="tournament?.created_by?.id == getUser.id" @click="showInviteFriendModal = true">Mời bạn
                      bè</span>
                  </div>
                  <div v-if="tournament?.tournamnet_participants?.length">
                    <div class="grid grid-cols-2 sm:grid-cols-6 lg:grid-cols-6 gap-4">
                      <UserCard v-for="(item, index) in tournament.tournamnet_participants" :key="index"
                        :name="item.user.name" :avatar="item.user.avatar" :rating="getUserScore(item.user)"
                        :status="item.is_confirmed == true ? 'approved' : 'pending'" />
                      <UserCard
                        v-if="tournament?.tournamnet_participants?.length < (tournament.max_team * tournament.player_per_team) && tournament?.created_by?.id == getUser.id"
                        :empty="true" @clickEmpty="showInviteFriendModal = true" />
                    </div>
                  </div>
                </div>
              </template>
              <template v-else-if="listActiveTab === 'split'">
                <div class="flex items-center justify-between mb-4 uppercase">
                  <p class="text-sm font-semibold">Xác nhận tham gia • {{ listTeams.length ?? 0 }}</p>
                  <p class="text-sm font-semibold">Chờ xác nhận • 0</p>
                </div>
                <template v-if="listTeams">
                  <div class="border border-[#BBBFCC] rounded flex items-center justify-between p-4 mb-4"
                    v-for="team in listTeams" :key="team.id">
                    <div class="flex items-center gap-3">
                      <div class="relative w-[4.875rem] h-[4.875rem]" @click="openEditTeamModal(team)">
                        <div class="w-full h-full rounded-lg overflow-hidden" v-if="team.avatar">
                          <img :src="team.avatar" alt="User" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-full h-full flex items-center justify-center rounded-lg"
                          :class="getTeamBgClass(team.id)">
                          <UserMultiple class="w-8 h-8 text-white" />
                        </div>
                        <button
                          class="absolute bottom-0 right-0 translate-x-1/4 translate-y-1/4 bg-blue-500 hover:bg-blue-600 text-white rounded-full p-1 shadow-md transition">
                          <PencilIcon class="w-4 h-4" />
                        </button>
                      </div>
                      <p class="font-medium text-gray-900">{{ team.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                      <UserCard v-for="(member, index) in team.members" :key="index" :name="member.full_name"
                        :avatar="member.avatar" :rating="getUserScore(member)"
                        :status="member.is_confirmed == true ? 'approved' : 'pending'" />

                      <UserCard v-for="n in getRemainingSlots(team.members)" :key="`empty-${team.id}-${n}`"
                        :empty="true" />
                    </div>
                  </div>
                </template>
                <div class="flex items-center justify-start mb-2 mt-28 gap-4"
                  v-if="tournament?.created_by?.id == getUser.id">
                  <button @click="openCreateTeamModal"
                    class="flex items-center justify-center gap-2 bg-[#D72D36] hover:bg-red-500 text-white font-medium px-4 py-2 rounded-md transition">
                    (+) Thêm đội</button>
                  <button @click="autoAssign"
                    class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium px-4 py-2 rounded-md transition">
                    Tự động chia đội
                  </button>
                </div>
              </template>
              <template v-else-if="listActiveTab === 'invite'">
                <div v-if="!listHasInvite.length">
                  <div class="flex items-center justify-center my-8 rounded-full bg-[#FFF5F5] w-20 h-20 mx-auto">
                    <EnvelopeIcon class="w-10 h-10 text-[#D72D36]" />
                  </div>
                  <div class="flex flex-col justify-center items-center gap-6 p-7">
                    <p class="text-gray-700">
                      Mời bạn bè tham gia giải đấu
                    </p>
                    <button @click="showInviteStaffModal = true"
                      class="flex items-center justify-center gap-2 bg-[#D72D36] hover:bg-red-500 text-white font-medium px-4 py-2 rounded-md transition">
                      Mời bạn
                    </button>
                  </div>
                </div>
                <div v-else>
                  <div class="grid grid-cols-1 gap-4">
                    <div v-for="item in listHasInvite" :key="item.id"
                      class="border p-4 flex justify-between items-center gap-4 rounded cursor-pointer hover:shadow-md transition">
                      <div class="flex justify-start items-center gap-4">
                        <UserCard :avatar="item.avatar" :status="item.is_confirmed == 1 ? 'approved' : 'pending'" />
                      <div>
                        <p>{{ item.name }}</p>
                        <p class="flex items-center gap-1 text-sm text-gray-500">
                          <img :src="maleIcon" alt="male icon" class="w-4 h-4" v-if="item.gender == 1" />
                          <img :src="femaleIcon" alt="male icon" class="w-4 h-4" v-else-if="item.gender == 2" />
                          {{ item.gender_text }}
                          <span :class="[
                            'px-2 py-0.5 rounded text-xs font-medium',
                            item.visibility === 'open'
                              ? 'bg-blue-100 text-blue-700'
                              : 'bg-green-100 text-green-700'
                          ]">
                            {{ item.visibility === 'open' ? 'Open' : 'Friend-Only' }}
                          </span>
                        </p>
                      </div>
                      </div>
                      <button v-if="item.is_confirmed== 0 || tournament?.created_by?.id == getUser.id" @click="confirm(item.participant_id)"
                      class="px-6 py-2 bg-[#D72D36] text-white font-medium rounded-lg hover:bg-red-700 transition-colors shadow-md">
                      Duyệt
                    </button>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <div v-else-if="activeTab === 'type'" key="type">
              <template v-if="!tournament.tournament_types || !tournament.tournament_types.length">

                <template v-if="!showFormatType">
                  <div class="flex flex-col justify-center items-center gap-6 p-7">
                    <div class="flex items-center justify-center my-4 rounded-full bg-[#FFF5F5] w-20 h-20 mx-auto">
                      <AdjustmentsVerticalIcon class="w-10 h-10 text-[#D72D36]" />
                    </div>
                    <p class="text-gray-700">
                      Chưa có thể thức thi đấu nào được thiết lập cho giải đấu này.
                    </p>

                    <button @click="startSetup" v-if="tournament?.created_by?.id == getUser.id"
                      class="px-6 py-2 bg-[#D72D36] text-white font-medium rounded-lg hover:bg-red-700 transition-colors shadow-md">
                      Bắt đầu cài đặt thể thức
                    </button>
                  </div>
                </template>

                <template v-else>
                  <FormatType :data="tournament" @update:config="handleConfigUpdate" @submit="handleFormSubmit"
                    @back="showFormatType = false" />
                </template>

              </template>
              <template v-else>
                <div class="flex items-center justify-between border-b border-[#BBBFCC] px-3 py-4 mb-4"
                  v-if="tournament?.created_by?.id == getUser.id">
                  <p class="font-semibold uppercase">Công khai bảng đấu</p>
                  <button @click="togglePublicBranch"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                    :class="publicBracket ? 'bg-[#D72D36]' : 'bg-gray-300'">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                      :class="publicBracket ? 'translate-x-6' : 'translate-x-1'" />
                  </button>
                </div>
                <div v-if="displayFormat"
                  class="border border-[#BBBFCC] flex justify-start items-start p-4 rounded gap-2">
                  <img :src="displayFormat.icon" alt="" class="w-6 h-6"
                    :class="tournament?.tournament_types[0]?.format == 1 ? '' : 'mt-1'"
                    style="filter: invert(17%) sepia(75%) saturate(3884%) hue-rotate(346deg) brightness(99%) contrast(81%);" />
                  <div>
                    <h3 class="font-semibold text-gray-900 mb-1">
                      {{ displayFormat.title }}
                    </h3>
                    <p class="text-gray-700 mb-1 text-xs">
                      {{ displayFormat.description }}
                    </p>
                    <p class="text-xs underline text-[#4392E0] cursor-pointer"
                      v-if="tournament?.created_by?.id == getUser.id">Thay đổi thể thức</p>
                  </div>
                </div>
                <div v-if="publicBracket == true || tournament?.created_by?.id == getUser.id"
                  class="border border-[#BBBFCC] rounded my-4 px-4 py-3 flex justify-between items-center cursor-pointer hover:shadow-md transition">
                  <div class="flex items-center gap-3">
                    <img src="@/assets/images/branch.svg" class="w-5 h-5" alt="">
                    <p>Sơ đồ thi đấu</p>
                  </div>
                  <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 border-b pb-4"
                  :class="publicBracket == true ? 'mt-0' : 'mt-4'">
                  <div
                    class="w-full h-[140px] bg-[#FFF5F5] hover:shadow-md transition rounded-md p-4 flex flex-col space-y-2 items-center justify-center">
                    <p class="font-semibold">2</p>
                    <p class="text-xs">Bảng đấu</p>
                  </div>
                  <div
                    class="w-full h-[140px] bg-[#FFF5F5] hover:shadow-md transition rounded-md p-4 flex flex-col space-y-2 items-center justify-center">
                    <p class="font-semibold">2</p>
                    <p class="text-xs">Đội vào vòng loại mỗi bảng</p>
                  </div>
                  <div
                    class="w-full h-[140px] bg-[#FFF5F5] hover:shadow-md transition rounded-md p-4 flex flex-col space-y-2 items-center justify-center">
                    <p class="font-semibold">1-2</p>
                    <p class="text-xs">Số trận đấu mỗi đội</p>
                  </div>
                  <div
                    class="w-full h-[140px] bg-[#FFF5F5] hover:shadow-md transition rounded-md p-4 flex flex-col space-y-2 items-center justify-center">
                    <p class="font-semibold">2</p>
                    <p class="text-xs">Trận tranh hạng ba</p>
                  </div>
                  <div
                    class="w-full h-[140px] bg-[#FFF5F5] hover:shadow-md transition rounded-md p-4 flex flex-col space-y-2 items-center justify-center">
                    <p class="font-semibold">Thắng - Thua</p>
                    <p class="text-xs">Cách tính xếp hạng</p>
                  </div>
                  <div
                    class="w-full h-[140px] bg-[#FFF5F5] hover:shadow-md transition rounded-md p-4 flex flex-col items-center justify-center">
                    <p class="font-semibold">H2H thắng</p>
                    <p class="font-semibold">Hiệu số</p>
                    <p class="font-semibold">H2H hiệu số</p>
                    <p class="text-xs">Ưu tiên gỡ hoà</p>
                  </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 my-4 pb-4"
                  :class="tournament?.created_by?.id == getUser.id ? 'border-b' : ''">
                  <div>
                    <p class="text-sm font-semibold uppercase">Giá trị điểm</p>
                    <ul class="mt-4 space-y-3">
                      <li class="flex justify-between items-center">
                        <p>Thắng</p>
                        <span class="text-[#4392E0]">3</span>
                      </li>
                      <li class="flex justify-between items-center">
                        <p>Thua</p>
                        <span class="text-[#4392E0]">0</span>
                      </li>
                      <li class="flex justify-between items-center">
                        <p>Hoà</p>
                        <span class="text-[#4392E0]">1</span>
                      </li>
                    </ul>
                  </div>
                  <div>
                    <p class="text-sm font-semibold uppercase">Thông số</p>
                    <ul class="mt-4 space-y-3">
                      <li class="flex justify-between items-center">
                        <p>Tổng số trận đấu</p>
                        <span class="text-[#4392E0]">120</span>
                      </li>
                      <li class="flex justify-between items-center">
                        <p>Thời lượng giải đấu</p>
                        <span class="text-[#4392E0]">120 * 15</span>
                      </li>
                    </ul>
                  </div>
                </div>
                <p class="text-[#D72D36] text-sm cursor-pointer hover:underline"
                  v-if="tournament?.created_by?.id == getUser.id">Chia lại cặp đấu</p>
              </template>
            </div>

            <div v-else-if="activeTab === 'schedule'" key="schedule" class="flex flex-col h-[70vh]">
              <p>Lịch thi đấu sẽ được hiển thị ở đây.</p>
            </div>
            <div v-else-if="activeTab === 'discuss'" key="discuss" class="flex flex-col h-[70vh]">
              <div class="flex-1 overflow-y-auto p-4 mx-10 space-y-4 ">
                <div class="flex items-start gap-3 mb-4">
                  <div class="flex-1 max-w-[60%]">
                    <div class="bg-[#EDEEF2] rounded-md px-4 py-3 grid grid-cols-[auto_1fr] gap-3 items-start">
                      <div class="w-10 h-10 rounded-full overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100" alt="User"
                          class="w-full h-full object-cover" />
                      </div>
                      <p class="text-gray-800 text-sm leading-relaxed">
                        Chào bạn! Mình có thể tham gia trận đấu lúc 17:00 được không?
                      </p>
                    </div>
                  </div>
                </div>

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
                    <div class="bg-[#EDEEF2] rounded-md px-4 py-3 grid grid-cols-[auto_1fr] gap-3 items-start">
                      <div class="w-10 h-10 rounded-full overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100" alt="User"
                          class="w-full h-full object-cover" />
                      </div>
                      <p class="text-gray-800 text-sm leading-relaxed">
                        Chào bạn! Mình có thể tham gia trận đấu lúc 17:00 được không?
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex-shrink-0 border-gray-200 flex justify-between items-center px-4 py-4">
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

      <ShareAction :buttons="[
        { label: 'Gửi link', icon: LinkIcon },
        { label: 'Quét mã QR', icon: QrCodeIcon },
        { label: 'Mời người chơi', icon: UsersIcon },
        { label: 'Mời nhóm', icon: UserMultiple, onClick: () => showInviteModal = true },
        { label: 'Yêu cầu xác nhận KQ', icon: ClipboardDocumentCheckIcon }
      ]" :subtitle="'Hãy chia sẻ thông tin tới bạn bè để cùng tham gia giải đấu'" />
    </div>

    <InviteGroup v-model="showInviteModal" @invite="handleInvite" />
    <CreateMatch v-model="showCreateMatchModal" @create="handleCreateMatch" />
    <DeleteConfirmationModal v-model="showDeleteModal" title="Xác nhận hủy bỏ giải đấu"
      message="Thao tác này không thể hoàn tác." confirmButtonText="Xác nhận" @confirm="removeTournament" />
    <InviteFriendModal v-model="showInviteFriendModal" :data="friendList" @invite="handleInviteFriend"
      @loadMore="loadMoreFriends" :hasMore="hasMoreFriend" @search="handleSearchFriends" title="Mời vận động viên"
      emptyText="Không có vận động viên phù hợp với yêu cầu" />
    <InviteFriendModal v-model="showInviteStaffModal" :data="listUsers" @invite="handleInviteUser"
      @loadMore="loadMoreUsers" :hasMore="hasMoreUsers" @search="handleSearchUsers" title="Mời người tổ chức"
      emptyText="Không có người dùng phù hợp với yêu cầu" />
    <EditTeamModal v-model="isOpenUpdateTeamModal" :data="selectedTeamDetail || {}" :isSaving="isSavingTeam"
      @update-info="handleUpdateInfo" @delete="handleDeleteTeam" />
    <CreateTeamModal v-model="isOpenCreateTeamModal" :isCreating="isLoading" @create-team="handleCreateInfo" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { AdjustmentsVerticalIcon, ArrowUpTrayIcon, ChevronRightIcon, EnvelopeIcon, LinkIcon, LockClosedIcon, LockOpenIcon, PaperAirplaneIcon, PhotoIcon, QrCodeIcon, XMarkIcon } from '@heroicons/vue/24/solid'
import {
  CalendarDaysIcon,
  MapPinIcon,
  CircleStackIcon,
  UserIcon,
  PencilIcon,
  XCircleIcon,
  UserGroupIcon as UserMultiple,
  UsersIcon,
  ClipboardDocumentCheckIcon,
  FaceSmileIcon
} from '@heroicons/vue/24/outline'
import UserCard from '@/components/molecules/UserCard.vue'
import InviteGroup from '@/components/molecules/InviteGroup.vue'
import CreateMatch from '@/components/molecules/CreateMatch.vue'
import InviteFriendModal from '@/components/molecules/InviteFriendModal.vue'
import * as TournamnetService from '@/service/tournament.js'
import * as TournamentTypeService from '@/service/tournamentType.js'
import * as TeamService from '@/service/team.js'
import * as ParticipantService from '@/service/participant.js'
import * as TournamentStaffService from '@/service/tournamentStaff.js'
import { useRoute, useRouter } from 'vue-router'
import ShareAction from '@/components/molecules/ShareAction.vue'
import { toast } from 'vue3-toastify'
import { useFormatDate } from '@/composables/formatDatetime.js'
import { formatEventDate } from '@/composables/formatDatetime.js'
import FormatType from '@/components/organisms/FormatType.vue'
import mixedIcon from '@/assets/images/mixed.svg';
import directIcon from '@/assets/images/direct.svg';
import roundRobinIcon from '@/assets/images/round-robin.svg';
import { TABS, LIST_TABS, BACKGROUND_COLORS } from '@/data/tournament/index.js'
import DeleteConfirmationModal from '@/components/molecules/DeleteConfirmationModal.vue'
import EditTeamModal from '@/components/molecules/EditTeamModal.vue'
import CreateTeamModal from '@/components/molecules/CreateTeamModal.vue'
import debounce from 'lodash.debounce'
import maleIcon from '@/assets/images/male.svg';
import femaleIcon from '@/assets/images/female.svg';
import { useUserStore } from '@/store/auth'
import { storeToRefs } from 'pinia'

const userStore = useUserStore()
const { getUser } = storeToRefs(userStore)
const { formatDateTime } = useFormatDate()
const route = useRoute()
const router = useRouter()
const preview = ref(null);
const isDragging = ref(false);
const fileInput = ref(null);
const fileToUpload = ref(null);
const currentConfig = ref({});
const showFormatType = ref(false);
const tabs = TABS
const listTabs = LIST_TABS
const id = route.params.id
const tournament = ref([])
const activeTab = ref('detail')
const listActiveTab = ref('staffs')
const autoApprove = ref(false)
const publicBracket = ref(false)
const showInviteModal = ref(false)
const showInviteFriendModal = ref(false)
const showInviteStaffModal = ref(false)
const showCreateMatchModal = ref(false)
const showDeleteModal = ref(false);
const isEditingDescription = ref(false);
const descriptionModel = ref('');
const friendList = ref([]);
const listUsers = ref([]);
const meta = ref({})
const searchFriendTerm = ref('')
const searchUserTerm = ref('')
const listTeams = ref([])
const isOpenUpdateTeamModal = ref(false)
const isOpenCreateTeamModal = ref(false)
const selectedTeamDetail = ref(null)
const isSavingTeam = ref(false);
const isLoading = ref(false);
const listHasInvite = ref([])
const isDescriptionChanged = computed(() => {
  return descriptionModel.value !== tournament.value.description;
});
const setupDescription = () => {
  descriptionModel.value = tournament.value.description || '';
  isEditingDescription.value = true;
};

const confirmRemoval = () => {
  showDeleteModal.value = true;
};
const startSetup = () => {
  showFormatType.value = true;
};
const FORMAT_DETAILS = {
  1: {
    icon: mixedIcon,
    title: 'Hỗn hợp',
    description: 'Bao gồm vòng đấu bảng để chọn đội, sau đó đấu loại trực tiếp để tìm ra đội vô địch.'
  },
  2: {
    icon: directIcon,
    title: 'Loại trực tiếp',
    description: 'Đấu loại trực tiếp theo nhánh. Đội thắng sẽ đi tiếp vào vòng trong, đội thua bị loại khỏi nhánh đấu.'
  },
  3: {
    icon: roundRobinIcon,
    title: 'Vòng tròn',
    description: 'Các đội thi đấu với nhau một hoặc nhiều lần. Đội có thành tích tốt nhất sẽ vô địch.'
  }
};

const backgroundClasses = BACKGROUND_COLORS;

const getTeamBgClass = (teamId) => {
  const idNumber = Number(teamId);
  const colorIndex = idNumber % backgroundClasses.length;

  return backgroundClasses[colorIndex];
};

const getRemainingSlots = (members) => {
  const maxPlayers = tournament.value.player_per_team || 0;
  const currentCount = members ? members.length : 0;

  // Trả về số lượng còn thiếu, đảm bảo không âm
  return Math.max(0, maxPlayers - currentCount);
};

const displayFormat = computed(() => {
  const type = tournament.value?.tournament_types?.[0];

  if (!type) {
    return null;
  }

  return FORMAT_DETAILS[type.format] || {
    icon: directIcon,
    title: 'Thể thức không xác định',
    description: 'Vui lòng kiểm tra lại cấu hình thể thức giải đấu.'
  };
});

const handleConfigUpdate = (configData) => {
  currentConfig.value = configData;
};

const handleFormSubmit = async (finalConfig) => {
  const tournamentId = tournament.value.id || null;
  if (!tournamentId) {
    toast.error('ID giải đấu không hợp lệ');
    return;
  }
  const formData = finalConfig;
  formData.append('tournament_id', tournamentId);
  await storeTournamentType(formData);
};

const storeTournamentType = async (payload) => {
  try {
    await TournamentTypeService.createTournamentType(payload);
    toast.success('Thể thức thi đấu đã được lưu thành công!');
    showFormatType.value = false;
    const response = await TournamnetService.getTournamentById(tournament.value.id);
    tournament.value.tournament_types = response.tournament_types;
  } catch (error) {
    console.error('Lỗi khi lưu thể thức thi đấu:', error);
    toast.error('Đã xảy ra lỗi khi lưu thể thức thi đấu.');
  }
};

const handleFileSelect = (file) => {
  if (!file) return;

  if (!file.type.startsWith('image/')) {
    toast.error('Ảnh tải lên không hợp lệ');
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    toast.error('Kích thước ảnh vượt quá 5MB');
    return;
  }

  fileToUpload.value = file;

  const reader = new FileReader();
  reader.onload = (e) => {
    preview.value = e.target.result;
    updateTournament(tournament.value.id, { poster: fileToUpload.value });
  };
  reader.readAsDataURL(file);
};

const handleFileInputChange = (e) => {
  const file = e.target.files[0];
  handleFileSelect(file);
};

const handleDragOver = () => {
  if (tournament.value?.created_by?.id !== getUser.value.id) {
    toast.error('Bạn không có quyền thực hiện thay đổi này')
    return
  }
  isDragging.value = true;
};

const handleDragLeave = () => {
  if (tournament.value?.created_by?.id !== getUser.value.id) {
    toast.error('Bạn không có quyền thực hiện thay đổi này')
    return
  }
  isDragging.value = false;
};

const handleDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer.files[0];
  handleFileSelect(file);
};

const handleClick = () => {
  if (tournament.value?.created_by?.id !== getUser.value.id) {
    toast.error('Bạn không có quyền thực hiện thay đổi này')
    return
  }
  fileInput.value?.click();
};

const handleRemove = () => {
  preview.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const handleInvite = (user) => {
  console.log('Invited user:', user)
}

const handleInviteFriend = async (friend) => {
  await invite(friend.id);
  await detailTournament(id);
}

const handleInviteUser = async (user) => {
  await inviteStaff(user.id);
  await detailTournament(id);
}

const handleCreateMatch = (match) => {
  console.log('Created match:', match)
}

const getUserScore = (user) => {
  if (!user?.sports?.length || !tournament.value?.sport_id) {
    return '0'
  }

  const matchedSport = user.sports.find(s => s.sport_id === tournament.value.sport_id)

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

const detailTournament = async (tournamentId) => {
  try {
    const response = await TournamnetService.getTournamentById(tournamentId)
    tournament.value = response
    autoApprove.value = response.auto_approve
    publicBracket.value = response.is_public_branch
    preview.value = response.poster || null
    if (response.description) {
      isEditingDescription.value = true;
    }
    descriptionModel.value = response.description || '';
  } catch (error) {
    console.error('Error fetching tournament details:', error)
  }
}

const updateTournament = async (id, payload) => {
  try {
    const formData = new FormData()
    for (const key in payload) {
      let value = payload[key]
      if (typeof value === 'boolean') {
        value = value ? '1' : '0'
      }
      formData.append(key, value)
    }
    await TournamnetService.updateTournament(id, formData)
    toast.success('Cập nhật thông tin giải đấu thành công!')
  } catch (error) {
    console.error('Lỗi khi cập nhật thông tin giải đấu:', error)
    toast.error('Đã xảy ra lỗi khi cập nhật thông tin giải đấu.')
  }
}

const toggleAutoApprove = debounce(async () => {
  autoApprove.value = !autoApprove.value
  await updateAutoApprove(autoApprove.value)
}, 300)

const updateAutoApprove = async (value) => {
  try {
    await updateTournament(tournament.value.id, { auto_approve: value })
    autoApprove.value = value
  } catch (error) {
    console.error('Lỗi khi cập nhật chế độ duyệt tự động:', error)
  }
}

const togglePublicBranch = debounce(async () => {
  publicBracket.value = !publicBracket.value
  await updatePublicBranch(publicBracket.value)
}, 300);

const updatePublicBranch = async (value) => {
  try {
    await updateTournament(tournament.value.id, { is_public_branch: value })
    publicBracket.value = value
  } catch (error) {
    console.error('Lỗi khi cập nhật chế độ duyệt tự động:', error)
  }
}

const confirm = async (participantId) => {
  try {
    await ParticipantService.confirmParticipants(participantId)
    toast.success('Xác nhận thành viên thành công!')
    await detailTournament(id);
    await getListHasInvite();
  } catch (error) {
    console.error('Lỗi khi xác nhận thành viên:', error)
    toast.error(error.response?.data?.message || 'Đã xảy ra lỗi khi xác nhận thành viên.')
  }
}

const goToEditPage = () => {
  router.push({
    name: 'edit-tournament',
    params: { id: tournament.value.id }
  });
};

const saveDescription = async () => {
  await updateTournament(tournament.value.id, { description: descriptionModel.value });
  tournament.value.description = descriptionModel.value;
  isEditingDescription.value = false;
};

const publicTournament = async () => {
  const newStatus = tournament.value.status === 1 ? 2 : 1;
  let res = null;

  try {
    res = await updateTournament(tournament.value.id, { status: newStatus });
    if (res && res.status) {
      tournament.value.status = res.status;
    } else {
      tournament.value.status = newStatus;
    }
  } catch (error) {
    console.error("Lỗi khi cập nhật trạng thái:", error);
  }
}

const removeTournament = async () => {
  const id = tournament.value.id
  try {
    await TournamnetService.deleteTournament(id)
    toast.success('Xoá giải đấu thành công!')
    setTimeout(() => {
      router.push('/')
    }, 1500)
  } catch (error) {
    console.error('Lỗi khi xoá giải đấu:', error)
    toast.error('Đã xảy ra lỗi khi xoá giải đấu.')
  }
}

const autoAssign = async () => {
  try {
    const teamsResponse = await TeamService.autoAssignTeams(id)
    const teams = teamsResponse || []
    if (teams.length === 0) {
      toast.info('Không có đội nào để phân chia tự động.')
      await getTeams();
      return
    }
    await getTeams();
    toast.success('Đã phân chia đội tự động thành công!')
  } catch (error) {
    console.error('Lỗi khi phân chia đội tự động:', error)
    toast.error('Đã xảy ra lỗi khi phân chia đội tự động.')
  }
}

const getFriendList = async (page = 1) => {
  try {
    const response = await ParticipantService.inviteFriends(id, {
      page,
      name: searchFriendTerm.value
    })
    if (page === 1) {
      friendList.value = response.invitations || []
    } else {
      friendList.value.push(...response.invitations)
    }
    meta.value = response.meta || {}
  } catch (error) {
    console.error('Lỗi khi lấy danh sách bạn bè:', error)
  }
}

const getUserList = async (page = 1) => {
  try {
    const response = await ParticipantService.inviteStaffs(id, {
      page,
      name: searchUserTerm.value
    })
    if (page === 1) {
      listUsers.value = response.invitations || []
    } else {
      listUsers.value.push(...response.invitations)
    }
    meta.value = response.meta || {}
  } catch (error) {
    console.error('Lỗi khi lấy danh sách người dùng:', error)
  }
}

const invite = async (friendId) => {
  try {
    await ParticipantService.sendInvitation(id, [friendId]);
    toast.success('Đã gửi lời mời thành công!');
  } catch (error) {
    console.error('Lỗi khi gửi lời mời:', error);
    toast.error('Đã xảy ra lỗi khi gửi lời mời.');
  }
};

const inviteStaff = async (userId) => {
  try {
    await TournamentStaffService.addTournamentStaff(id, userId);
    toast.success('Thêm thành công');
  } catch (error) {
    console.error('Lỗi khi thêm:', error);
    toast.error(error.response?.data?.message || 'Đã xảy ra lỗi khi thêm.');
  }
};

const handleSearchFriends = debounce(async (query) => {
  searchFriendTerm.value = query
  await getFriendList(1)
}, 300)

const handleSearchUsers = debounce(async (query) => {
  searchUserTerm.value = query
  await getUserList(1)
}, 300)

const hasMoreFriend = computed(() => {
  return meta.value.current_page < meta.value.last_page
})

const hasMoreUsers = computed(() => {
  return meta.value.current_page < meta.value.last_page
})

const loadMoreFriends = async () => {
  if (hasMoreFriend.value) {
    await getFriendList(meta.value.current_page + 1)
  }
}

const loadMoreUsers = async () => {
  if (hasMoreUsers.value) {
    await getUserList(meta.value.current_page + 1)
  }
}

const getTeams = async () => {
  try {
    const response = await TeamService.getTeamsByTournamentId(id)
    listTeams.value = response.teams || []
  } catch (error) {
    console.error('Lỗi khi lấy danh sách đội:', error)
    return []
  }
}

const openEditTeamModal = (team) => {
  if (tournament.value?.created_by?.id !== getUser.id) {
    return
  }
  isOpenUpdateTeamModal.value = true
  selectedTeamDetail.value = team
}

const openCreateTeamModal = () => {
  isOpenCreateTeamModal.value = true
}

const handleUpdateInfo = async (payload) => {
  isSavingTeam.value = true;
  const formData = new FormData();
  formData.append('name', payload.name);
  if (payload.avatar) {
    formData.append('avatar', payload.avatar);
  }
  try {
    const teamId = selectedTeamDetail.value.id;
    await TeamService.updateTeam(teamId, formData);
    isOpenUpdateTeamModal.value = false;
    toast.success('Cập nhật đội thành công!');
    await getTeams();
  } catch (error) {
    console.error("Lỗi khi cập nhật đội:", error);
    toast.error(error.response?.data?.message || 'Đã xảy ra lỗi khi cập nhật đội.');
  } finally {
    isSavingTeam.value = false;
  }
};

const handleDeleteTeam = async (teamId) => {
  try {
    await TeamService.deleteTeam(teamId);
    toast.success('Xoá đội thành công!');
    isOpenUpdateTeamModal.value = false;
    await getTeams();
  } catch (error) {
    console.error("Lỗi khi xoá đội:", error);
    toast.error(error.response?.data?.message || 'Đã xảy ra lỗi khi xoá đội.');
  } finally {
    isSavingTeam.value = false;
  }
};

const handleCreateInfo = async (payload) => {
  isLoading.value = true;
  const formData = new FormData();
  formData.append('name', payload.name);
  if (payload.avatar) {
    formData.append('avatar', payload.avatar);
  }
  try {
    await TeamService.createTeam(id, formData);
    isOpenCreateTeamModal.value = false;
    toast.success('Tạo đội thành công!');
    await getTeams();
  } catch (error) {
    console.error("Lỗi khi tạo đội:", error);
    toast.error(error.response?.data?.message || 'Đã xảy ra lỗi khi tạo đội.');
  } finally {
    isLoading.value = false;
  }
};

const getListHasInvite = async (page = 1) => {
  try {
    const response = await ParticipantService.listInviteUsers(id, { page: page });
    if (page > 1) {
      listHasInvite.value.push(...(response.invitations || []));
    } else {
      listHasInvite.value = response.invitations || []; 
    }
  } catch (error) {
    console.error('Lỗi khi lấy danh sách đã mời:', error);
  }
};
onMounted(async () => {
  if (id) {
    await detailTournament(id)
    await getFriendList()
    await getUserList()
    await getTeams()
    await getListHasInvite()
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