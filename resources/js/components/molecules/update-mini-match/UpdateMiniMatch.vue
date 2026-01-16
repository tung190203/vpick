<template src="./UpdateMiniMatch.html"></template>

<script>
import { ref, computed, watch } from 'vue'
import { MinusIcon, PlusIcon, XMarkIcon, CheckBadgeIcon } from '@heroicons/vue/24/solid'
import { ClipboardIcon, CalendarDaysIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import { formatEventDate } from '@/composables/formatDatetime.js'
import QrcodeVue from 'qrcode.vue'
import { toast } from 'vue3-toastify'
import * as MiniMatchService from '@/service/miniMatch.js';
import UserCard from '@/components/molecules/UserCard.vue'

export default {
    name: 'UpdateMiniMatch',
    components: {
        MinusIcon,
        PlusIcon,
        XMarkIcon,
        CheckBadgeIcon,
        ClipboardIcon,
        CalendarDaysIcon,
        MapPinIcon,
        QrcodeVue,
        UserCard
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false
        },
        data: {
            type: Object,
            default: () => ({})
        },
        miniTournament: {
            type: Object,
            required: true,
            default: () => ({ player_per_team: 0 })
        },
        isCreator: {
            type: Boolean,
            default: false
        },
    },

    emit: ['update:modelValue', 'updated'],

    setup(props, { emit }) {
        const yardNumber = ref(1)
        const scores = ref([])
        const isSaving = ref(false)

        const incrementYard = () => yardNumber.value++
        const decrementYard = () => {
            if (yardNumber.value > 1) yardNumber.value--
        }

        const incrementScore = (idx, team) => {
            const maxPoints =
                props.miniTournament?.max_points || 11
            if (team === '1' && scores.value[idx].team1 < maxPoints) scores.value[idx].team1++
            if (team === '2' && scores.value[idx].team2 < maxPoints) scores.value[idx].team2++
        }

        const decrementScore = (idx, team) => {
            if (team === '1' && scores.value[idx].team1 > 0) scores.value[idx].team1--
            if (team === '2' && scores.value[idx].team2 > 0) scores.value[idx].team2--
        }

        const isOpen = computed({
            get: () => props.modelValue,
            set: val => emit('update:modelValue', val)
        })

        const currentMiniMatch = computed(() => {
            return props.data || props.data
        })

        const qrCodeUrl = computed(() => {
            if (!currentMiniMatch.value?.id) return ''
            return `${window.location.origin}/mini-match/${currentMiniMatch.value.id}/verify`
        })

        const addSet = () => {
            // if (!isMaxSets.value) scores.value.push({ team1: 0, team2: 0 })
            scores.value.push({ team1: 0, team2: 0 })
        }

        const removeSet = (idx) => {
            if (scores.value.length > 1) scores.value.splice(idx, 1)
        }

        const formatResultsForAPI = () => {
            return scores.value.map((score, idx) => ({
                set_number: idx + 1,
                results: [
                    {
                        team_id: props.data.team1.id,
                        score: score.team1
                    },
                    {
                        team_id: props.data.team2.id,
                        score: score.team2
                    }
                ]
            }))
        }
        const initializeScores = () => {
            if (currentMiniMatch.value?.results_by_sets) {
                const r = currentMiniMatch.value?.results_by_sets
                if (!r) return []

                const team1Id = props.data.team1?.id
                const team2Id = props.data.team2?.id
                const sets = []

                Object.keys(r).forEach((key) => {
                    const arr = r[key]
                    if (!Array.isArray(arr)) return

                    let team1Score = '0'
                    let team2Score = '0'

                    arr.forEach(item => {
                        if (item.team?.id === team1Id) {
                            team1Score = String(item.score)
                        }
                        if (item.team?.id === team2Id) {
                            team2Score = String(item.score)
                        }
                    })

                    sets.push({
                        team1: team1Score,
                        team2: team2Score
                    })
                })

                return sets;
            }
            return [{ team1: 0, team2: 0 }]
        }

        const saveMiniMatch = async () => {
            if (isSaving.value) return
            try {
                isSaving.value = true

                const payload = {
                    yard_number: yardNumber.value,
                    sets: formatResultsForAPI()
                }

                const res = await MiniMatchService.updateOrCreateSetMiniMatches(currentMiniMatch.value.id, payload)
                toast.success('Cập nhật kết quả thành công!')
                emit('updated', res.data)
                isOpen.value = false
            } catch (err) {
                toast.error(err.response?.data?.message || 'Lỗi khi cập nhật')
            } finally {
                isSaving.value = false
            }
        }
        const canConfirmMiniMatch = computed(() =>
            scores.value.some(s => s.team1 > 0 || s.team2 > 0)
        )

        const confirmMiniMatchResult = async () => {
            if (isSaving.value || !canConfirmMiniMatch.value) return
            try {
                isSaving.value = true
                const res = await MiniMatchService.confirmResults(currentMiniMatch.value.id)
                toast.success('Xác nhận kết quả thành công!')
                emit('updated', res)
                isOpen.value = false
            } catch (err) {
                toast.error(err.response?.data?.message || 'Lỗi xác nhận')
            } finally {
                isSaving.value = false
            }
        }

        /* ===================== UI HELPERS ===================== */
        const closeModal = () => {
            if (!isSaving.value) isOpen.value = false
        }

        const emptySlots = (team) => {
            const members =
                team === 'team1'
                    ? props.data.team1?.members?.length || 0
                    : props.data.team2?.members?.length || 0

            const slots = props.miniTournament.player_per_team - members
            return slots > 0 ? Array.from({ length: slots }, (_, i) => i + 1) : []
        }

        watch(
            () => props.data,
            () => {
                scores.value = initializeScores()
                yardNumber.value = currentMiniMatch.value?.yard_number || 1
            },
            { deep: true }
        )

        return {
            MinusIcon,
            PlusIcon,
            XMarkIcon,
            CheckBadgeIcon,
            ClipboardIcon,
            CalendarDaysIcon,
            MapPinIcon,
            QrcodeVue,
            UserCard,
            formatEventDate,
            isOpen,
            isSaving,
            currentMiniMatch,
            yardNumber,
            incrementYard,
            decrementYard,
            scores,
            initializeScores,
            qrCodeUrl,
            incrementScore,
            decrementScore,
            addSet,
            removeSet,
            formatResultsForAPI,
            saveMiniMatch,
            canConfirmMiniMatch,
            confirmMiniMatchResult,
            closeModal,
            emptySlots
        }
    }
}
</script>
