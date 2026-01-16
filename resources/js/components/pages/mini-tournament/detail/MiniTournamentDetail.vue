<template src="./MiniTournamentDetail.html"></template>

<script>
import {computed, onMounted, ref} from 'vue'
import { ArrowTrendingUpIcon, ChevronRightIcon, LinkIcon, LockClosedIcon, LockOpenIcon, PaperAirplaneIcon, PhotoIcon, QrCodeIcon } from '@heroicons/vue/24/solid'
import {
    CalendarDaysIcon,
    MapPinIcon,
    CircleStackIcon,
    UserIcon,
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
import * as MiniTournamnetService from '@/service/miniTournament.js'
import * as MiniParticipantService from '@/service/miniParticipant.js'
import * as ClubService from '@/service/club.js'
import { useRoute, useRouter } from 'vue-router'
import ShareAction from '@/components/molecules/ShareAction.vue'
import { formatEventDate } from '@/composables/formatDatetime.js'
import { TABS } from '@/data/mini/index.js'
import debounce from "lodash.debounce";
import {toast} from "vue3-toastify";
import {storeToRefs} from "pinia";
import { useUserStore } from '@/store/auth';
import * as MiniTournamentStaffService from '@/service/miniTournamentStaff.js';
import QRcodeModal from '@/components/molecules/QRcodeModal.vue';
import DeleteConfirmationModal from '@/components/molecules/DeleteConfirmationModal.vue';
import MiniMatchScheduleTab from '@/components/molecules/mini-match-schedule-tab/MiniMatchScheduleTab.vue'

export default {
    name: 'MiniTournamentDetail',
    components: {
        ArrowTrendingUpIcon,
        ChevronRightIcon,
        LinkIcon,
        LockClosedIcon,
        LockOpenIcon,
        PaperAirplaneIcon,
        PhotoIcon,
        QrCodeIcon,
        CalendarDaysIcon,
        MapPinIcon,
        CircleStackIcon,
        PencilIcon,
        XCircleIcon,
        UserMultiple,
        UserIcon,
        UsersIcon,
        CreditCardIcon,
        ClipboardDocumentCheckIcon,
        FaceSmileIcon,
        MiniMatchScheduleTab,
        QRcodeModal,
        UserCard,
        InviteGroup,
        ShareAction,
        DeleteConfirmationModal
    },

    setup() {
        const route = useRoute()
        const router = useRouter()
        const userStore = useUserStore()
        const { getUser } = storeToRefs(userStore)
        const id = route.params.id
        const mini = ref([])
        const activeTab = ref('detail')
        const subActiveTab = ref('ranking')
        const autoApprove = ref(false)
        const showInviteModal = ref(false)
        const showCreateMatchModal = ref(false)
        const tabs = TABS
        const searchQuery = ref('')
        const inviteType = ref('participant')
        const activeScope = ref('all');
        const inviteGroupData = ref([]);
        const selectedClub = ref(null);
        const clubs = ref([])
        const currentRadius = ref(10);
        const showQRCodeModal = ref(false);
        const miniTournamentLink = window.location.href;
        const descriptionModel = ref('');
        const isEditingDescription = ref(false);
        const showDeleteModal = ref(false);
        const currentParticipant = ref(null)
        const isUserConfirmed = ref(false)
        const showDelineMiniParticipantModal = ref(false)

        const isDescriptionChanged = computed(() => {
            return descriptionModel.value !== mini.value.description;
        });

        const setupDescription = () => {
            descriptionModel.value = mini.value.description || '';
            isEditingDescription.value = true;
        };

        const handleInvite = async (user) => {
            if (inviteType.value === 'staff') {
                await inviteStaff(user.id);
            } else {
                await invite(user.id);
            }
            await detailMiniTournament(id);
        }

        const copyLink = () => {
            if (navigator.share) {
                navigator.share({
                    title: 'Hãy tham gia kèo đấu ' + mini.value.name + ' của tôi!',
                    url: miniTournamentLink
                }).then(() => {
                    toast.success('Đã sao chép link kèo đấu vào clipboard!');
                }).catch(console.error);
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(miniTournamentLink).then(() => {
                    toast.success('Đã sao chép link kèo đấu vào clipboard!');
                }).catch(console.error);
            } else {
                alert(`Link kèo đấu: ${miniTournamentLink}`);
            }
        }

        const onRadiusChange = debounce(async (radius) => {
            currentRadius.value = radius;
            await getInviteGroupData();
        }, 300);

        const goToEditPage = () => {
            router.push({
                name: 'edit-mini-tournament',
                params: { id: mini.value.id }
            });
        };

        const getUserScore = (user) => {
            if (!user?.sports?.length || !mini.value?.sport?.id) {
                return '0'
            }

            const matchedSport = user.sports.find(s => s.sport_id === mini.value.sport.id)

            if (!matchedSport?.scores) {
                return '0'
            }

            const scores = matchedSport.scores
            if (scores.vndupr_score) {
                return parseFloat(scores.vndupr_score).toFixed(1)
            }

            if (scores.personal_score) {
                return parseFloat(scores.personal_score).toFixed(1)
            }

            return '0'
        }

        const detailMiniTournament = async (id) => {
            try {
                const response = await MiniTournamnetService.getMiniTournamentById(id)
                mini.value = response
                autoApprove.value = response.auto_approve

                if (response.description) {
                    isEditingDescription.value = true;
                }
                descriptionModel.value = response.description || '';
            } catch (error) {
                console.error('Error fetching mini tournament:', error)
            }
        }

        const updateMiniTournament = async (id, payload) => {
            try {
                const formData = new FormData()
                for (const key in payload) {
                    let value = payload[key]
                    if (typeof value === 'boolean') {
                        value = value ? '1' : '0'
                    }
                    formData.append(key, value)
                }
                await MiniTournamnetService.updateMiniTournament(id, formData)
                toast.success('Cập nhật thông tin kèo đấu thành công!')
            } catch (error) {
                toast.error(error.response?.data?.message || 'Đã xảy ra lỗi khi cập nhật thông tin giải đấu.')
            }
        }

        const toggleAutoApprove = debounce(async () => {
            autoApprove.value = !autoApprove.value
            await updateAutoApprove(autoApprove.value)
        }, 300)

        const updateAutoApprove = async (value) => {
            try {
                const update = await baseSetColumnUpdateMiniTournament()
                update.auto_approve = value
                await updateMiniTournament(mini.value.id, update)
                autoApprove.value = value
            } catch (error) {
            }
        }

        const isCreator = computed(() => {
            return mini.value?.staff?.organizer?.some(
                staff => staff.role === 1 && staff.user?.id === getUser.value.id
            )
        })

        const openInviteModalDefault = async () => {
            inviteType.value = 'staff'
            activeScope.value = 'all'
            await getInviteGroupData()
            showInviteModal.value = true
        }

        const openInviteModalWithFriends = async () => {
            inviteType.value = 'participant'
            activeScope.value = 'friends'
            await getInviteGroupData()
            showInviteModal.value = true
        }

        const getInviteGroupData = async () => {
            if (activeScope.value === 'club' && !selectedClub.value) {
                inviteGroupData.value = [];
                return;
            }

            const payload = {
                scope: activeScope.value,
                per_page: 50,
                ...(activeScope.value === 'club' ? { club_id: selectedClub.value } : {}),
                ...(searchQuery.value ? { search: searchQuery.value } : {})
            };
            if (activeScope.value === 'area') {
                payload.lat = mini.value.competition_location.latitude
                payload.lng = mini.value.competition_location.longitude
                payload.radius = currentRadius.value
            }
            try {
                const resp = await MiniParticipantService.getMiniTournamentInviteGroups(id, payload);
                inviteGroupData.value = resp || [];
            } catch (e) {
                inviteGroupData.value = [];
            }
        };

        const getMyClubs = async () => {
            try {
                const response = await ClubService.myClubs();
                clubs.value = response || [];

                if (clubs.value.length === 0) {
                    selectedClub.value = null;
                } else {
                    selectedClub.value = clubs.value[0].id;
                }
            } catch (e) {
                clubs.value = [];
                selectedClub.value = null;
            }
        };

        const onSearchChange = debounce(async (query) => {
            searchQuery.value = query;
            await getInviteGroupData();
        }, 300);

        const onScopeChange = async (scope) => {
            activeScope.value = scope;
            await getInviteGroupData();
        };

        const onClubChange = async (clubId) => {
            selectedClub.value = clubId;
            await getInviteGroupData();
        };

        const invite = async (friendId) => {
            try {
                await MiniParticipantService.sendInvitation(id, friendId);
                toast.success('Đã gửi lời mời thành công!');
            } catch (error) {
                toast.error(error.response?.data?.message || 'Đã xảy ra lỗi khi gửi lời mời.');
            }
        };

        const inviteStaff = async (userId) => {
            try {
                await MiniTournamentStaffService.addMiniTournamentStaff(id, userId);
                toast.success('Thêm thành công');
            } catch (error) {
                toast.error(error.response?.data?.message || 'Đã xảy ra lỗi khi thêm.');
            }
        };

        const showQRCode = () => {
            showQRCodeModal.value = true;
        }

        const handleRemoveStaff = async (staffId) => {
            try {
                await MiniParticipantService.deleteStaff(staffId);
                toast.success('Đã xóa người tổ chức khỏi kèo đấu');
                await detailMiniTournament(id);
            } catch (error) {
                toast.error(error.response?.data?.message || 'Xóa người tổ chức thất bại');
            }
        };

        const handleRemoveUser = async (miniParticipantId) => {
            try {
                await MiniParticipantService.deleteMiniParticipant(miniParticipantId);
                toast.success('Đã xóa người chơi khỏi kèo đấu');
                await detailMiniTournament(id);
            } catch (error) {
                toast.error(error.response?.data?.message || 'Xóa người chơi thất bại');
            }
        };

        const saveDescription = async () => {
            const update = await baseSetColumnUpdateMiniTournament()
            update.description = descriptionModel.value

            await updateMiniTournament(mini.value.id, update);
            mini.value.description = descriptionModel.value;
            isEditingDescription.value = false;
        };

        const baseSetColumnUpdateMiniTournament = async () => {
            return {
                age_group: mini.value.age_group,
                court_switch_points: mini.value.court_switch_points,
                games_per_set: mini.value.games_per_set,
                gender_policy: mini.value.gender_policy,
                lock_cancellation: mini.value.lock_cancellation,
                match_type: mini.value.match_type,
                max_points: mini.value.max_points,
                name: mini.value.name,
                points_difference: mini.value.points_difference,
                repeat_type: mini.value.repeat_type,
                role_type: mini.value.role_type,
                set_number: mini.value.set_number,
                sport_id: mini.value.sport?.id,
                status: mini.value.status,
            }
        };

        const confirmRemoval = () => {
            showDeleteModal.value = true;
        };

        const removeMiniTournament = async () => {
            const id = mini.value.id
            try {
                await MiniTournamnetService.deleteMiniTournament(id)
                toast.success('Xoá kèo đấu thành công!')
                setTimeout(() => {
                    router.push('/')
                }, 1500)
            } catch (error) {
                toast.error(error.response?.data?.message || 'Đã xảy ra lỗi khi xoá giải đấu.')
            }
        }


        const publicMiniTournament = async () => {
            const newStatus = mini.value.status === 1 ? 2 : 1;
            let res = null;

            try {
                const update = await baseSetColumnUpdateMiniTournament()
                update.status = newStatus

                res = await updateMiniTournament(mini.value.id, update);
                if (res && res.status) {
                    mini.value.status = res.status;
                } else {
                    mini.value.status = newStatus;
                }
            } catch (error) {
            }
        }

        const joinerMiniTournament = async () => {
            const miniTournamentId = mini.value.id;
            try {
                const res = await MiniParticipantService.joinMiniTournament(miniTournamentId);
                if (res) {
                    toast.success('Tham gia kèo đấu thành công, Bạn có thể cần chờ xác nhận trước khi được bổ nhiệm vào 1 đội')
                }
            } catch (error) {
                toast.error(error.response?.data?.message || 'Lỗi khi thực hiện yêu cầu này')
            }
        }

        const confirmMiniTournament = async () => {
            const miniParticipantId =
                mini.value?.participants?.find(
                    p => p.user.id === getUser.value.id
                )?.id ?? null;
            try {
                const res = await MiniParticipantService.acceptInviteMiniTournament(miniParticipantId)
                if (res) {
                    await detailMiniTournament(id)
                    isUserConfirmed.value = true
                    toast.success('Xác nhận tham gia kèo đấu thành công')
                }
            } catch (error) {
                toast.error(error.response?.data?.message || 'Lỗi khi thực hiện yêu cầu này')
            }
        }

        const confirmDelineMiniParticipant = () => {
            showDelineMiniParticipantModal.value = true;
        };

        const declineMiniTournament = async () => {
            const miniParticipantId =
                mini.value?.participants?.find(
                    p => p.user.id === getUser.value.id
                )?.id ?? null;
            try {
                const res = await MiniParticipantService.declineMiniTournament(miniParticipantId)
                if (res.status) {
                    await detailMiniTournament(id)
                    toast.success('Từ Chối tham gia kèo đấu thành công')
                }
            } catch (error) {
                toast.error(error.response?.data?.message || 'Lỗi khi thực hiện yêu cầu này')
            }
        }

        onMounted(async () => {
            if (id) {
                await detailMiniTournament(id)
            }
            if (mini.value && mini.value.participants) {
                currentParticipant.value = mini.value.participants.find(
                    p => Number(p.user?.id) === Number(getUser.value.id)
                )

                if (currentParticipant.value) {
                    isUserConfirmed.value = currentParticipant.value.is_confirmed === true
                }
            }
            await getMyClubs();
            await getInviteGroupData();
        })

        return {
            ArrowTrendingUpIcon,
            ChevronRightIcon,
            LinkIcon,
            LockClosedIcon,
            LockOpenIcon,
            PaperAirplaneIcon,
            PhotoIcon,
            QrCodeIcon,
            CalendarDaysIcon,
            MapPinIcon,
            CircleStackIcon,
            PencilIcon,
            XCircleIcon,
            UserMultiple,
            UserIcon,
            UsersIcon,
            CreditCardIcon,
            ClipboardDocumentCheckIcon,
            FaceSmileIcon,
            DeleteConfirmationModal,
            QRcodeModal,
            UserCard,
            InviteGroup,
            activeTab,
            ShareAction,
            tabs,
            formatEventDate,
            mini,
            autoApprove,
            subActiveTab,
            showInviteModal,
            showCreateMatchModal,
            toggleAutoApprove,
            isCreator,
            inviteGroupData,
            clubs,
            activeScope,
            selectedClub,
            searchQuery,
            onSearchChange,
            showQRCodeModal,
            miniTournamentLink,
            descriptionModel,
            isEditingDescription,
            isDescriptionChanged,
            showDeleteModal,
            MiniMatchScheduleTab,
            isUserConfirmed,
            currentParticipant,
            handleInvite,
            getUserScore,
            copyLink,
            goToEditPage,
            openInviteModalDefault,
            onScopeChange,
            onClubChange,
            openInviteModalWithFriends,
            showQRCode,
            handleRemoveStaff,
            handleRemoveUser,
            setupDescription,
            saveDescription,
            confirmRemoval,
            removeMiniTournament,
            publicMiniTournament,
            joinerMiniTournament,
            confirmMiniTournament,
            confirmDelineMiniParticipant,
            declineMiniTournament,
            showDelineMiniParticipantModal,
            onRadiusChange
        }
    }
}


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
