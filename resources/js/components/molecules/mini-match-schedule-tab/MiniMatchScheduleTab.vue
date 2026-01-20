<template src="./MiniMatchScheduleTab.html"></template>

<script>
import { ref, watch, computed } from 'vue'
import MiniMatchCard from '@/components/molecules/MiniMatchCard.vue';
import { TABS, SUB_TABS } from '@/data/mini/index.js';
import CreateMiniMatch from '@/components/molecules/create-mini-match/CreateMiniMatch.vue';
import UpdateMiniMatch from '@/components/molecules/update-mini-match/UpdateMiniMatch.vue';
import {toast} from "vue3-toastify";
import * as MiniMatchService from '@/service/miniMatch.js';
import {ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/24/solid/index.js";
import DeleteConfirmationModal from '@/components/molecules/DeleteConfirmationModal.vue'


export default {
    name: 'MiniTournamentDetail',
    components: {
        ChevronLeftIcon,
        ChevronRightIcon,
        MiniMatchCard,
        CreateMiniMatch,
        UpdateMiniMatch,
        DeleteConfirmationModal
    },
    props: {
        isCreator: {
            type: Boolean,
            default: false
        },
        data: {
            type: Object,
            required: true
        },
        sportId: {
            type: Number,
            required: false
        }
    },

    emits: [],
    setup(props, { emit }) {
        const tabs = TABS
        const subtabs = SUB_TABS
        const activeTab = ref('matches')
        const subActiveTab = ref('match')
        const showCreateMiniMatchModal = ref(false)
        const showUpdateMiniMatchModal = ref(false)
        const miniMatches = ref([])
        const scheduledMyMiniMatches = ref([])
        const countMiniMatches = ref(0)
        const countMyMiniMatches = ref(0)
        const selectedMiniMatches = ref([])
        const showDeleteModal = ref(false)
        const detailData = ref({});

        const confirmRemoval = () => {
            showDeleteModal.value = true;
        };

        const getMiniMatches = async (miniTournamentId) => {
            try {
                const response = await MiniMatchService.getListMiniMatches(miniTournamentId)
                miniMatches.value = response.data.matches;
                countMiniMatches.value = response.meta.total;
            } catch (error) {
                toast.error(error.response?.data?.message || 'Lấy trận thi đấu thất bại');
            }
        }

        const getUserRatingBySport = (user, sportId) => {
            if (!user?.sports || !sportId) return 0

            const sport = user.sports.find(s => s.sport_id === sportId)
            return Number(sport?.scores?.vndupr_score).toFixed(1) ?? 0
        }

        const getMyMiniMatches = async (miniTournamentId) => {
            try {
                const response = await MiniMatchService.getListMiniMatches(miniTournamentId, "?filter=my_matches");
                scheduledMyMiniMatches.value = response.data.matches;
                countMyMiniMatches.value = response.meta.total;
            } catch (error) {
                toast.error(error.response?.data?.message || 'Lấy trận thi đấu thất bại');
            }
        }

        const totalDuration = computed(() => {
            if (!Array.isArray(miniMatches.value)) return 0

            return miniMatches.value.reduce((sum, match) => {
                if (!match.started_at || !match.finished_at) return sum

                const start = new Date(match.started_at)
                const end = new Date(match.finished_at)

                const diffHours = (end - start) / (1000 * 60 * 60)

                return sum + diffHours
            }, 0)
        })

        const formatDate = (dateString) => {
            if (!dateString) return 'Chưa xác định'
            const date = new Date(dateString)
            const year = date.getFullYear() % 100
            const day = date.getDate()
            const month = date.getMonth() + 1
            const hours = date.getHours().toString().padStart(2, '0')
            const minutes = date.getMinutes().toString().padStart(2, '0')
            return `${hours}:${minutes} - ${day}/${month}/${year}`
        }

        const buildSets = (match) => {
            const r = match?.results_by_sets
            if (!r) return []

            const team1Id = match.team1?.id
            const team2Id = match.team2?.id

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

            return sets
        }

        const toggleSelectMiniMatch = (miniMatchId, value) => {
            if (value) {
                if (selectedMiniMatches.value.length >= countMiniMatches) return
                if (!selectedMiniMatches.value.includes(miniMatchId)) {
                    selectedMiniMatches.value.push(miniMatchId)
                }
            } else {
                selectedMiniMatches.value = selectedMiniMatches.value.filter(id => id !== miniMatchId)
            }
        }

        const totalTimeMiniMatches = (countMatches) => {
            return Number.isInteger((countMatches * 15) / 60)
                ? (countMatches * 15) / 60
                : ((countMatches * 15) / 60).toFixed(2)
        }

        const cancelSelectedMiniMatches = async () => {
            if (selectedMiniMatches.value.length === 0) return

            try {
                const data = {
                    ids: selectedMiniMatches.value,
                }

                await MiniMatchService.deleteMiniMatches(data)
                selectedMiniMatches.value = []

                if (!props.data?.id) return
                try {
                    await getMiniMatches(props.data.id)
                    await getMyMiniMatches(props.data.id)
                } catch (e) {
                    console.error(e)
                }
                toast.success('Đã huỷ kèo đấu thành công');
            } catch (error) {
                toast.error(error.response?.data?.message || 'Huỷ kèo đấu thất bại');
            }
        }

        const showMiniMatchDetail = async (id) => {
            try {
                const res = await MiniMatchService.detailMiniMatches(id);
                if(res) {
                    detailData.value = res
                    showUpdateMiniMatchModal.value = true;
                }
            } catch (error) {
                toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi thực hiện thao tác này');
            }
        }

        const onMiniMatchCreated = (newMatch) => {
            showUpdateMiniMatchModal.value = false
            showCreateMiniMatchModal.value = false

            if (!props.data?.id) return
            try {
                getMiniMatches(props.data.id)
                getMyMiniMatches(props.data.id)
            } catch (e) {
                console.error(e)
            }
        }

        watch(
            () => props.data?.id,
            async (miniTournamentId) => {
                if (miniTournamentId) {
                    await getMiniMatches(miniTournamentId);
                    await getMyMiniMatches(miniTournamentId);
                }
            },

            { immediate: true, deep: true }
        );

        return {
            MiniMatchCard,
            activeTab,
            subActiveTab,
            tabs,
            subtabs,
            showUpdateMiniMatchModal,
            showCreateMiniMatchModal,
            CreateMiniMatch,
            miniMatches,
            countMiniMatches,
            scheduledMyMiniMatches,
            countMyMiniMatches,
            totalDuration,
            buildSets,
            formatDate,
            selectedMiniMatches,
            toggleSelectMiniMatch,
            cancelSelectedMiniMatches,
            totalTimeMiniMatches,
            DeleteConfirmationModal,
            showDeleteModal,
            confirmRemoval,
            showMiniMatchDetail,
            detailData,
            props,
            onMiniMatchCreated,
            getUserRatingBySport
        }
    }
}

</script>
