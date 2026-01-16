<template src="./CreateMiniMatch.html"></template>

<script>
import {ref, computed, watch} from 'vue'
import {MinusIcon, PlusIcon, XMarkIcon, CheckBadgeIcon} from '@heroicons/vue/24/solid'
import {ClipboardIcon, CalendarDaysIcon, MapPinIcon} from '@heroicons/vue/24/outline'
import { vi } from 'date-fns/locale'
import QrcodeVue from 'qrcode.vue'
import {toast} from 'vue3-toastify'
import * as MiniMatchService from '@/service/miniMatch.js';
import UserCard from '@/components/molecules/UserCard.vue'
import VueDatePicker from "@vuepic/vue-datepicker";
import {ChevronDownIcon} from "@heroicons/vue/24/solid/index.js";
import { useFormattedDate } from '@/composables/formatedDate'
import InviteUserParticipant from '@/components/molecules/InviteUserParticipant.vue'
import { format } from 'date-fns'

export default {
    name: 'CreateMiniMatch',
    components: {
        ChevronDownIcon,
        VueDatePicker,
        MinusIcon,
        PlusIcon,
        XMarkIcon,
        CheckBadgeIcon,
        ClipboardIcon,
        CalendarDaysIcon,
        MapPinIcon,
        QrcodeVue,
        UserCard,
        InviteUserParticipant
    },
    props: {
        modelValue: Boolean,
        miniTournament: { type: Object, required: true },
        isCreator: Boolean
    },

    setup(props, {emit}) {
        const isSaving = ref(false)
        const miniMatchName = ref('')
        const yardNumber = ref(1)
        const date = ref(null)
        const text_team1 = ref('Team 1')
        const text_team2 = ref('Team 2')
        const team1Users = ref([])
        const team2Users = ref([])
        const showUserModal = ref(false)
        const selectingTeam = ref(null)
        const MATCH_TYPE_SINGLE = 2
        const openDate = ref(false)
        const { formattedDate } = useFormattedDate(date)


        const isOpen = computed({
            get: () => props.modelValue,
            set: val => emit('update:modelValue', val)
        })

        const closeModal = () => {
            if (!isSaving.value) isOpen.value = false
        }

        const incrementYard = () => yardNumber.value++
        const decrementYard = () => {
            if (yardNumber.value > 1) yardNumber.value--
        }

        const closeOtherDropdowns = (exceptRef) => {
            if (exceptRef !== openDate) openDate.value = false
        }

        const toggleOpenDate = () => {
            const currentState = openDate.value
            closeOtherDropdowns(openDate)
            openDate.value = !currentState
        }

        const playersPerTeam = computed(() => {
            return props.miniTournament.match_type === MATCH_TYPE_SINGLE ? 1 : 2
        })

        const confirmedUsers = computed(() =>
            props.miniTournament?.participants
                ?.filter(p => p.is_confirmed)
                .map(p => p.user) || []
        )

        const selectableUsers = computed(() => {
            const selectedIds = [
                ...team1Users.value.map(u => u.id),
                ...team2Users.value.map(u => u.id),
            ]

            return confirmedUsers.value.filter(
                u => !selectedIds.includes(u.id)
            )
        })

        const emptySlots = (team) => {
            const totalSlots = playersPerTeam.value

            const members =
                team === 'team1'
                    ? team1Users.value.length
                    : team2Users.value.length

            const slots = totalSlots - members

            return slots > 0
                ? Array.from({ length: slots }, (_, i) => i + 1)
                : []
        }

        const openInviteModalDefault = (team) => {
            selectingTeam.value = team
            showUserModal.value = true
        }

        const selectUserToTeam = (user) => {
            if (selectingTeam.value === 'team1') {
                team1Users.value.push(user)
            } else if (selectingTeam.value === 'team2') {
                team2Users.value.push(user)
            }

            showUserModal.value = false
        }

        const resetForm = () => {
            miniMatchName.value = ''
            yardNumber.value = 1
            date.value = null
            team1Users.value = []
            team2Users.value = []
            text_team1.value = 'Team 1'
            text_team2.value = 'Team 2'
        }

        const scheduledAt = computed(() => {
            if (!date.value) return null
            return format(new Date(date.value), 'yyyy-MM-dd HH:mm:ss')
        })

        const saveMiniMatch = async () => {
            if (isSaving.value) return

            /* ========= VALIDATE ========= */
            if (!miniMatchName.value.trim()) {
                toast.error('Vui lòng nhập tên trận đấu')
                return
            }

            if (!date.value) {
                toast.error('Vui lòng chọn ngày & giờ')
                return
            }

            if (text_team1.value.trim() === text_team2.value.trim()) {
                toast.error('Vui lòng nhập không trùng tên đội')
                return
            }

            if (team1Users.value.length === 0 || team2Users.value.length === 0) {
                toast.error('Mỗi đội phải có ít nhất 1 người chơi')
                return
            }
            const payload = {
                name_of_match: miniMatchName.value,
                yard_number: String(yardNumber.value),
                scheduled_at: scheduledAt.value,
                team1_name: text_team1.value,
                team2_name: text_team2.value,
                team1: team1Users.value.map(u => u.id),
                team2: team2Users.value.map(u => u.id),
                mini_tournament_id: props.miniTournament.id
            }

            isSaving.value = true

            try {
                const res = await MiniMatchService.createMiniMatch(props.miniTournament.id, payload)
                toast.success('Thêm mới trận đấu thành công!')
                isOpen.value = false
                emit('created', res.data)
                resetForm()
                closeModal()

            } catch (error) {
                toast.error(error.response?.data?.message || 'Tạo trận đấu thất bại')
            } finally {
                isSaving.value = false
            }
        }

        watch(
            () => props.miniTournament,
            (val) => {
                if (!val) return

                confirmedUsers.value = (val.participants || [])
                    .filter(p => p.is_confirmed)
                    .map(p => p.user)

                const team1Ids = team1Users.value.map(u => u.id)
                const team2Ids = team2Users.value.map(u => u.id)

                selectableUsers.value = confirmedUsers.value.filter(
                    user =>
                        !team1Ids.includes(user.id) &&
                        !team2Ids.includes(user.id)
                )
            },
            {
                immediate: true,
                deep: true
            }
        )


        return {
            CalendarDaysIcon,
            isOpen,
            closeModal,
            props,
            incrementYard,
            decrementYard,
            yardNumber,
            toggleOpenDate,
            formattedDate,
            openDate,
            vi,
            date,
            emptySlots,
            isSaving,
            openInviteModalDefault,
            confirmedUsers,
            selectUserToTeam,
            text_team1,
            text_team2,
            team1Users,
            team2Users,
            InviteUserParticipant,
            selectableUsers,
            showUserModal,
            saveMiniMatch,
            miniMatchName
        }
    }
}
</script>
