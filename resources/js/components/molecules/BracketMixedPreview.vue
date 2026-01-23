<template>
    <div ref="bracketContainer"
        class="fixed inset-0 z-[9999] bg-image-container overflow-hidden flex items-center justify-center">
        <!-- Control Buttons -->
        <div class="fixed top-6 right-6 z-[10000] flex gap-4">
            <!-- Fullscreen Toggle -->
            <button @click="toggleFullscreen"
                class="p-3 bg-white/10 hover:bg-white/20 backdrop-blur-md rounded-full border border-white/20 transition-all group shadow-xl"
                :title="isFullscreen ? 'Thoát toàn màn hình' : 'Toàn màn hình'">
                <svg v-if="!isFullscreen" xmlns="http://www.w3.org/2000/svg"
                    class="w-6 h-6 text-white group-hover:scale-110 transition-transform" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg"
                    class="w-6 h-6 text-white group-hover:scale-110 transition-transform" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Close Button -->
            <button v-if="!isFullscreen" @click="emit('close')"
                class="p-3 bg-red-500/20 hover:bg-red-500/40 backdrop-blur-md rounded-full border border-red-500/20 transition-all group shadow-xl"
                title="Đóng">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-6 h-6 text-white group-hover:rotate-90 transition-transform" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="w-full mb-8 flex justify-center absolute top-16 left-0">
                    <img
                        :src="bannerImage"
                        alt="Tournament Banner"
                        class="max-w-[692px] w-full h-auto object-contain"
                        style="max-height: 300px;"
                    />
                </div>
        <div ref="bracketContent"
            class="flex flex-nowrap justify-center items-stretch gap-20 lg:gap-5 min-w-max min-h-[950px] py-10 px-10 transition-transform duration-300 origin-center"
            :style="{
                transform: `scale(${scale})`,
            }">
            <!-- Left Side -->
            <div class="flex gap-12 lg:gap-20 min-w-[200px] items-stretch">
                <div v-for="(round, rIdx) in leftRounds" :key="`left-${rIdx}`"
                    class="flex flex-col h-full justify-around">
                    <div
                        class="flex flex-col items-center justify-center opacity-70 bg-white/90 backdrop-blur-sm rounded-xl p-4 border border-white/20 shadow-sm mb-8 w-full">
                        <span class="font-black text-gray-800 text-sm uppercase tracking-wider">{{ round.round_name
                            }}</span>
                        <span class="text-[10px] text-gray-500 font-medium mt-1">
                            {{
                                round.matches?.[0]?.scheduled_at
                                    ? formatDateTime(
                                        round.matches[0].scheduled_at,
                                    )
                                    : "Chưa xếp lịch"
                            }}
                        </span>
                    </div>

                    <div class="flex flex-col flex-1 justify-around w-full min-h-[200px]">
                        <div v-for="(pair, pIdx) in chunkMatches(round.matches)" :key="`left-pair-${pIdx}`"
                            class="flex flex-col justify-around relative" :class="pair.length === 2 ? 'flex-1' : ''">
                            <template v-if="pair.length === 2">
                                <div class="relative flex items-center z-10">
                                    <MatchCard :match="pair[0]" side="left" :round-type="getRoundType(round.round_name)
                                        " :match-index="pIdx * 2" :previous-matches="getPreviousMatches(
                                            round,
                                            leftRounds,
                                            rIdx,
                                        )
                                            " :round-name="round.round_name" :is-right-side="false" />
                                    <div class="connector-h-right"></div>
                                </div>

                                <div class="relative flex items-center z-10">
                                    <MatchCard :match="pair[1]" side="left" :round-type="getRoundType(round.round_name)
                                        " :match-index="pIdx * 2 + 1" :previous-matches="getPreviousMatches(
                                            round,
                                            leftRounds,
                                            rIdx,
                                        )
                                            " :round-name="round.round_name" :is-right-side="false" />
                                    <div class="connector-h-right"></div>
                                </div>

                                <div class="connector-vertical-right"></div>
                                <div class="connector-next-right"></div>
                            </template>

                            <template v-else>
                                <div class="relative flex items-center z-10">
                                    <MatchCard :match="pair[0]" side="left" :round-type="getRoundType(round.round_name)
                                        " :match-index="pIdx" :previous-matches="getPreviousMatches(
                                            round,
                                            leftRounds,
                                            rIdx,
                                        )
                                            " :round-name="round.round_name" :is-right-side="false" />
                                    <div class="connector-line-right-full"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Center (Finals) -->
            <div class="flex flex-col items-center px-4 relative self-stretch min-w-[320px] z-50">
                <div class="w-full h-[72px] mb-8"></div>

                <div class="flex-1 flex flex-col justify-around w-full relative">
                    <div class="flex flex-col items-center relative">
                        <div class="relative">
                            <div class="absolute -top-10 left-0 right-0 flex flex-col items-center whitespace-nowrap">
                                <span class="text-yellow-400 text-xl font-bold uppercase tracking-wider">Chung
                                    Kết</span>
                            </div>

                            <MatchCard :match="finalMatch" :is-final="true" round-type="final" :match-index="0"
                                :previous-matches="[
                                    ...(leftRounds[leftRounds.length - 1]
                                        ?.matches || []),
                                    ...(rightRounds[rightRounds.length - 1]
                                        ?.matches || []),
                                ]" round-name="Chung kết" :is-right-side="false" />
                        </div>
                    </div>
                </div>

                <div v-if="hasThirdPlaceMatch"
                    class="absolute bottom-28 left-0 right-0 flex flex-col items-center pb-4 opacity-90 scale-95">
                    <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">
                        Tranh hạng 3
                    </div>
                    <MatchCard :match="thirdPlaceMatch" round-type="third_place" :match-index="0"
                        round-name="Tranh hạng 3" />
                </div>
            </div>

            <!-- Right Side -->
            <div class="flex gap-12 lg:gap-20 flex-row-reverse min-w-[200px] items-stretch">
                <div v-for="(round, rIdx) in rightRounds" :key="`right-${rIdx}`"
                    class="flex flex-col h-full justify-around">
                    <div
                        class="flex flex-col items-center justify-center opacity-70 bg-white/90 backdrop-blur-sm rounded-xl p-4 border border-white/20 shadow-sm mb-8 w-full">
                        <span class="font-black text-gray-800 text-sm uppercase tracking-wider">
                            {{ round.round_name || round.title }}
                        </span>
                        <span class="text-[10px] text-gray-500 font-medium mt-1">
                            {{
                                round.matches?.[0]?.scheduled_at
                                    ? formatDateTime(
                                        round.matches[0].scheduled_at,
                                    )
                                    : "Chưa xếp lịch"
                            }}
                        </span>
                    </div>

                    <div class="flex flex-col flex-1 justify-around w-full min-h-[200px]">
                        <div v-for="(pair, pIdx) in chunkMatches(round.matches)" :key="`right-pair-${pIdx}`"
                            class="flex flex-col justify-around relative" :class="pair.length === 2 ? 'flex-1' : ''">
                            <template v-if="pair.length === 2">
                                <div class="relative flex items-center flex-row-reverse z-10">
                                    <MatchCard :match="pair[0]" side="right" :round-type="getRoundType(round.round_name)
                                        " :match-index="pIdx * 2" :previous-matches="getPreviousMatches(
                                            round,
                                            rightRounds,
                                            rIdx,
                                        )
                                            " :round-name="round.round_name" :is-right-side="true" />
                                    <div class="connector-h-left"></div>
                                </div>

                                <div class="relative flex items-center flex-row-reverse z-10">
                                    <MatchCard :match="pair[1]" side="right" :round-type="getRoundType(round.round_name)
                                        " :match-index="pIdx * 2 + 1" :previous-matches="getPreviousMatches(
                                            round,
                                            rightRounds,
                                            rIdx,
                                        )
                                            " :round-name="round.round_name" :is-right-side="true" />
                                    <div class="connector-h-left"></div>
                                </div>

                                <div class="connector-vertical-left"></div>
                                <div class="connector-next-left"></div>
                            </template>

                            <template v-else>
                                <div class="relative flex items-center flex-row-reverse z-10">
                                    <MatchCard :match="pair[0]" side="right" :round-type="getRoundType(round.round_name)
                                        " :match-index="pIdx" :previous-matches="getPreviousMatches(
                                            round,
                                            rightRounds,
                                            rIdx,
                                        )
                                            " :round-name="round.round_name" :is-right-side="true" />
                                    <div class="connector-line-left-full"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    computed,
    defineComponent,
    h,
    ref,
    onMounted,
    onUnmounted,
    defineEmits,
} from "vue";
import { VideoCameraIcon } from "@heroicons/vue/24/solid";
import * as TournamentService from "@/service/tournament.js";
import { toast } from "vue3-toastify";
import bannerImage from "@/assets/images/bracket_banner.png";
const emit = defineEmits(["close"]);

const bracketContainer = ref(null);
const bracketContent = ref(null);
const isFullscreen = ref(false);
const scale = ref(1);

const updateScale = () => {
    if (!bracketContainer.value || !bracketContent.value) return;

    const padding = 20;
    const containerWidth = bracketContainer.value.clientWidth - padding;
    const containerHeight = bracketContainer.value.clientHeight - padding;

    const contentWidth = bracketContent.value.offsetWidth;
    const contentHeight = bracketContent.value.offsetHeight;

    const scaleX = containerWidth / contentWidth;
    const scaleY = containerHeight / contentHeight;
    scale.value = Math.min(scaleX, scaleY);
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        bracketContainer.value?.requestFullscreen().catch((err) => {
            console.error(
                `Error attempting to enable full-screen mode: ${err.message}`,
            );
        });
    } else {
        document.exitFullscreen();
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
    setTimeout(updateScale, 100);
};

let resizeObserver = null;

const handleKeyDown = (e) => {
    if (e.key === "Escape") {
        emit("close");
    }
};

onMounted(() => {
    document.addEventListener("fullscreenchange", handleFullscreenChange);
    window.addEventListener("resize", updateScale);
    window.addEventListener("keydown", handleKeyDown);

    resizeObserver = new ResizeObserver(() => {
        updateScale();
    });
    if (bracketContent.value) {
        resizeObserver.observe(bracketContent.value);
    }

    setTimeout(updateScale, 500);
});

onUnmounted(() => {
    document.removeEventListener("fullscreenchange", handleFullscreenChange);
    window.removeEventListener("resize", updateScale);
    window.removeEventListener("keydown", handleKeyDown);
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});

const getRound16Label = (matchIndex, isRightSide) => {
    const matchNum = Math.floor(matchIndex / 2);
    const isHome = matchIndex % 2 === 0;
    const allGroupLetters = ["A", "B", "C", "D", "E", "F", "G", "H"];

    if (isRightSide) {
        const rightMapping = [
            [1, 0],
            [3, 2],
            [5, 4],
            [7, 6],
        ];
        const [firstIdx, secondIdx] = rightMapping[matchNum] || [1, 0];
        return isHome ? `Nhất bảng ${allGroupLetters[firstIdx]}` : `Nhì bảng ${allGroupLetters[secondIdx]}`;
    } else {
        const leftMapping = [
            [0, 1],
            [2, 3],
            [4, 5],
            [6, 7],
        ];
        const [firstIdx, secondIdx] = leftMapping[matchNum] || [0, 1];
        return isHome ? `Nhất bảng ${allGroupLetters[firstIdx]}` : `Nhì bảng ${allGroupLetters[secondIdx]}`;
    }
};

const getQuarterLabel = (matchIndex, isRightSide) => {
    const matchNum = Math.floor(matchIndex / 2);
    const isHome = matchIndex % 2 === 0;

    if (groupCount.value === 4) {
        const groupLetters = isRightSide ? ["C", "D"] : ["A", "B"];
        const g1 = groupLetters[matchNum === 0 ? 0 : 1];
        const g2 = groupLetters[matchNum === 0 ? 1 : 0];
        return isHome ? `Nhất bảng ${g1}` : `Nhì bảng ${g2}`;
    }

    const baseMatch = isRightSide ? 5 : 1;
    const matchNumber = baseMatch + matchNum * 2;
    return isHome ? `Thắng Trận ${matchNumber}` : `Thắng Trận ${matchNumber + 1}`;
};

const getSemiLabel = (matchIndex, isRightSide) => {
    const isHome = matchIndex % 2 === 0;

    if (groupCount.value === 2) {
        if (isRightSide) {
            return isHome ? `Nhất bảng B` : `Nhì bảng A`;
        } else {
            return isHome ? `Nhất bảng A` : `Nhì bảng B`;
        }
    }

    const baseQF = isRightSide ? 3 : 1;
    return isHome ? `Thắng Tứ kết ${baseQF}` : `Thắng Tứ kết ${baseQF + 1}`;
};

const getFinalLabel = (matchIndex) => {
    return matchIndex % 2 === 0 ? "Thắng Bán kết 1" : "Thắng Bán kết 2";
};

const getThirdPlaceLabel = (matchIndex) => {
    return matchIndex === 0 ? "Thua Bán kết 1" : "Thua Bán kết 2";
};

const getMatchLabel = (
    team,
    roundType,
    matchIndex,
    isRightSide,
    previousMatches,
    roundName,
) => {
    if (
        team &&
        (team.name || team.team_name) &&
        team.name !== "TBD" &&
        team.team_name !== "TBD"
    ) {
        return team.name || team.team_name;
    }

    const name = roundName?.toLowerCase() || "";
    if (
        roundType === "round16" ||
        name.includes("16") ||
        name.includes("1/8")
    ) {
        return getRound16Label(matchIndex, isRightSide);
    }

    if (roundType === "quarter" || name.includes("tứ kết")) {
        return getQuarterLabel(matchIndex, isRightSide);
    }

    if (roundType === "semi" || name.includes("bán kết")) {
        return getSemiLabel(matchIndex, isRightSide);
    }

    if (roundType === "final" || name.includes("chung kết")) {
        return getFinalLabel(matchIndex);
    }

    if (roundType === "third_place" || name.includes("tranh hạng 3")) {
        return getThirdPlaceLabel(matchIndex);
    }

    return "Chưa xác định";
};

const MatchCard = defineComponent({
    props: [
        "match",
        "side",
        "isFinal",
        "roundType",
        "matchIndex",
        "previousMatches",
        "roundName",
        "isRightSide",
    ],
    setup(props, { emit }) {
        const getMatchLabelLocal = (
            team,
            roundType,
            matchIndex,
            isRightSide,
            previousMatches,
            roundName,
        ) => {
            if (
                team &&
                team.name &&
                team.name !== "TBD" &&
                team.team_name &&
                team.team_name !== "TBD"
            ) {
                return team.name || team.team_name;
            }
            return getMatchLabel(
                team,
                roundType,
                matchIndex,
                isRightSide,
                previousMatches,
                roundName,
            );
        };

        const getTeamName = (team, position) => {
            const hasValidTeamName =
                team &&
                (team.name || team.team_name) &&
                team.name !== "TBD" &&
                team.team_name !== "TBD" &&
                team.placeholder !== "TBD";

            if (hasValidTeamName) {
                return team.name || team.team_name;
            }

            const roundType = props.roundType || (props.isFinal ? "final" : "");
            const matchIdx = props.matchIndex || 0;
            const isRight = props.isRightSide || false;
            const prevMatches = props.previousMatches || null;
            const rName = props.roundName || "";

            const finalMatchIndex =
                matchIdx * 2 + (position === "home" ? 0 : 1);

            return getMatchLabelLocal(
                null,
                roundType,
                finalMatchIndex,
                isRight,
                prevMatches,
                rName,
            );
        };
        const getTeamAvatar = (team) => {
            if (team?.team_avatar) return team.team_avatar;
            const name = team?.name || team?.placeholder || "T";
            return `https://placehold.co/40x40/BBBFCC/3E414C?text=${name.charAt(0)}`;
        };
        const isWinner = (teamId) =>
            props.match.status === "completed" &&
            props.match.winner_team_id === teamId;

        const formatTime = (scheduledAt) => {
            if (!scheduledAt) return "Chưa xếp lịch";
            try {
                const date = new Date(scheduledAt);
                return date.toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit",
                });
            } catch (e) {
                console.error("Error formatting time:", e);
                return "Chưa xếp lịch";
            }
        };

        return () =>
            h(
                "div",
                {
                    class: [
                        "bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden z-10",
                        props.isFinal
                            ? "w-80 border-yellow-400 ring-4 ring-yellow-100 shadow-xl py-2"
                            : "w-64",
                    ],
                },
                [
                    h(
                        "div",
                        {
                            class: "flex justify-between items-center px-3 py-1.5 bg-gray-50 border-b border-gray-100 text-[10px] text-gray-500 uppercase font-bold",
                        },
                        [
                            h(
                                "span",
                                { class: props.isFinal ? "text-xs" : "" },
                                `Sân ${props.match.court || 1}`,
                            ),
                            props.match.status === "in_progress"
                                ? h(
                                    "span",
                                    {
                                        class: "text-red-500 flex items-center gap-1",
                                    },
                                    [
                                        h(VideoCameraIcon, {
                                            class: "w-3 h-3",
                                        }),
                                        "Live",
                                    ],
                                )
                                : h(
                                    "span",
                                    { class: props.isFinal ? "text-xs" : "" },
                                    formatTime(props.match.scheduled_at),
                                ),
                        ],
                    ),
                    h("div", { class: "flex flex-col" }, [
                        h(
                            "div",
                            {
                                class: [
                                    "flex justify-between items-center px-3 py-2 border-b border-gray-50",
                                    isWinner(props.match.home_team?.id)
                                        ? "bg-green-50"
                                        : "",
                                ],
                            },
                            [
                                h(
                                    "div",
                                    {
                                        class: "flex items-center gap-2 overflow-hidden",
                                    },
                                    [
                                        h("img", {
                                            src: getTeamAvatar(
                                                props.match.home_team,
                                            ),
                                            class: [
                                                "rounded-full object-cover bg-gray-200 flex-shrink-0",
                                                props.isFinal
                                                    ? "w-10 h-10"
                                                    : "w-6 h-6",
                                            ],
                                        }),
                                        h(
                                            "span",
                                            {
                                                class: [
                                                    "font-medium truncate",
                                                    props.isFinal
                                                        ? "text-sm max-w-[180px]"
                                                        : "text-xs max-w-[140px]",
                                                    isWinner(
                                                        props.match.home_team
                                                            ?.id,
                                                    )
                                                        ? "text-gray-900 font-bold"
                                                        : "text-gray-600",
                                                ],
                                            },
                                            getTeamName(
                                                props.match.home_team,
                                                "home",
                                            ),
                                        ),
                                    ],
                                ),
                                h(
                                    "span",
                                    {
                                        class: [
                                            "font-bold",
                                            props.isFinal
                                                ? "text-lg"
                                                : "text-sm",
                                            isWinner(props.match.home_team?.id)
                                                ? "text-green-600"
                                                : "text-gray-400",
                                        ],
                                    },
                                    props.match.home_score ?? "-",
                                ),
                            ],
                        ),
                        h(
                            "div",
                            {
                                class: [
                                    "flex justify-between items-center px-3 py-2",
                                    isWinner(props.match.away_team?.id)
                                        ? "bg-green-50"
                                        : "",
                                ],
                            },
                            [
                                h(
                                    "div",
                                    {
                                        class: "flex items-center gap-2 overflow-hidden",
                                    },
                                    [
                                        h("img", {
                                            src: getTeamAvatar(
                                                props.match.away_team,
                                            ),
                                            class: [
                                                "rounded-full object-cover bg-gray-200 flex-shrink-0",
                                                props.isFinal
                                                    ? "w-10 h-10"
                                                    : "w-6 h-6",
                                            ],
                                        }),
                                        h(
                                            "span",
                                            {
                                                class: [
                                                    "font-medium truncate",
                                                    props.isFinal
                                                        ? "text-sm max-w-[180px]"
                                                        : "text-xs max-w-[140px]",
                                                    isWinner(
                                                        props.match.away_team
                                                            ?.id,
                                                    )
                                                        ? "text-gray-900 font-bold"
                                                        : "text-gray-600",
                                                ],
                                            },
                                            getTeamName(
                                                props.match.away_team,
                                                "away",
                                            ),
                                        ),
                                    ],
                                ),
                                h(
                                    "span",
                                    {
                                        class: [
                                            "font-bold",
                                            props.isFinal
                                                ? "text-lg"
                                                : "text-sm",
                                            isWinner(props.match.away_team?.id)
                                                ? "text-green-600"
                                                : "text-gray-400",
                                        ],
                                    },
                                    props.match.away_score ?? "-",
                                ),
                            ],
                        ),
                    ]),
                ],
            );
    },
});

const props = defineProps({
    tournamentId: {
        type: [Number, String],
        default: null,
    },
    tournamentTypeId: {
        type: [Number, String],
        default: null,
    },
    bracketData: {
        type: Object,
        default: () => ({}),
    },
    rankData: {
        type: Object,
        default: () => ({}),
    },
});

const bracket = ref({});

const getTeamName = (team) => team?.name || "Chưa xác định";
const getTeamAvatar = (team) =>
    team?.team_avatar ||
    `https://placehold.co/40x40/BBBFCC/3E414C?text=${(team?.name || "T").charAt(0)}`;

const groupCount = computed(() => {
    if (!props.rankData) return 0;

    if (
        props.rankData.group_rankings &&
        Array.isArray(props.rankData.group_rankings)
    ) {
        return props.rankData.group_rankings.length;
    }

    if (props.rankData.rankings && Array.isArray(props.rankData.rankings)) {
        return 1;
    }

    return 0;
});

const createPlaceholderRounds = (isRightSide) => {
    const count = groupCount.value;
    const rounds = [];
    const baseId = isRightSide ? 100 : 1;

    if (count > 4) {
        rounds.push(createPlaceholderRound("Vòng 1/8", 4, baseId));
        rounds.push(createPlaceholderRound("Tứ kết", 2, baseId + 10));
        rounds.push(createPlaceholderRound("Bán kết", 1, baseId + 20));
    } else if (count > 2) {
        rounds.push(createPlaceholderRound("Tứ kết", 2, baseId));
        rounds.push(createPlaceholderRound("Bán kết", 1, baseId + 10));
    } else {
        rounds.push(createPlaceholderRound("Bán kết", 1, baseId));
    }

    return rounds;
};

const createPlaceholderMatch = (id) => ({
    match_id: `placeholder-${id}`,
    status: "pending",
    scheduled_at: null,
    home_team: null,
    away_team: null,
    home_score: 0,
    away_score: 0,
});

const createPlaceholderRound = (name, matchCount, startId) => ({
    round_name: name,
    matches: Array.from({ length: matchCount }, (_, i) =>
        createPlaceholderMatch(startId + i),
    ),
});

const formatDateTime = (scheduledAt) => {
    if (!scheduledAt) return "Chưa xếp lịch";
    try {
        const date = new Date(scheduledAt);
        if (Number.isNaN(date.getTime())) {
            return "Chưa xếp lịch";
        }
        const day = date.getDate();
        const monthNames = [
            "Th1",
            "Th2",
            "Th3",
            "Th4",
            "Th5",
            "Th6",
            "Th7",
            "Th8",
            "Th9",
            "Th10",
            "Th11",
            "Th12",
        ];
        const month = monthNames[date.getMonth()];
        const hours = date.getHours().toString().padStart(2, "0");
        const minutes = date.getMinutes().toString().padStart(2, "0");
        return `${day} ${month} - ${hours}:${minutes}`;
    } catch (e) {
        console.error("Error formatting date time:", e);
        return "Chưa xếp lịch";
    }
};

const chunkMatches = (matches) => {
    const pairs = [];
    if (!matches) return pairs;
    for (let i = 0; i < matches.length; i += 2) {
        pairs.push(matches.slice(i, i + 2));
    }
    return pairs;
};

const getRoundType = (roundName) => {
    if (!roundName) return "";
    const name = roundName.toLowerCase();
    if (name.includes("16") || name.includes("1/8")) return "round16";
    if (name.includes("tứ kết") || name.includes("quarter")) return "quarter";
    if (name.includes("bán kết") || name.includes("semi")) return "semi";
    if (name.includes("chung kết") || name.includes("final")) return "final";
    return "";
};

const getPreviousMatches = (currentRound, allRounds, currentRoundIndex) => {
    if (currentRoundIndex === 0) return null;
    const previousRound = allRounds[currentRoundIndex - 1];
    return previousRound?.matches || null;
};

const fetchBracketData = async () => {
    try {
        if (!props.tournamentId) {
            toast.error("Thiếu tournament ID");
            return;
        }

        const response = await TournamentService.getBracketByTournamentId(
            props.tournamentId,
        );

        bracket.value = {
            poolStage: response.poolStage || [],
            leftSide: response.leftSide || [],
            rightSide: response.rightSide || [],
            finalMatch: response.finalMatch || null,
            thirdPlaceMatch: response.thirdPlaceMatch || null,
            has_third_place_match: response.has_third_place_match,
        };
    } catch (error) {
        toast.error(
            error.response?.data?.message || "Lấy dữ liệu bracket thất bại",
        );
    }
};

onMounted(() => {
    if (props.bracketData && Object.keys(props.bracketData).length > 0) {
        bracket.value = {
            poolStage: props.bracketData.poolStage || [],
            leftSide: props.bracketData.leftSide || [],
            rightSide: props.bracketData.rightSide || [],
            finalMatch: props.bracketData.finalMatch || null,
            thirdPlaceMatch: props.bracketData.thirdPlaceMatch || null,
            has_third_place_match: props.bracketData.has_third_place_match,
        };
    } else if (props.tournamentId) {
        fetchBracketData();
    }
});

const leftRounds = computed(() => {
    const data = (bracket.value.leftSide || []).sort(
        (a, b) => a.round - b.round,
    );
    if (data.length > 0) return data;
    return createPlaceholderRounds(false);
});

const rightRounds = computed(() => {
    const data = (bracket.value.rightSide || [])
        .filter(
            (r) =>
                !r.matches.some(
                    (m) => m.is_third_place === true || m.is_third_place === 1,
                ),
        )
        .sort((a, b) => a.round - b.round);

    if (data.length > 0) return data;
    return createPlaceholderRounds(true);
});

const finalMatch = computed(() => {
    if (bracket.value.finalMatch) return bracket.value.finalMatch;
    return createPlaceholderMatch(999);
});

const thirdPlaceMatch = computed(() => {
    const rightSide = bracket.value.rightSide || [];
    for (const round of rightSide) {
        const match = round.matches.find(
            (m) => m.is_third_place === true || m.is_third_place === 1,
        );
        if (match) return match;
    }
    return bracket.value.thirdPlaceMatch || createPlaceholderMatch("3rd");
});

const hasThirdPlaceMatch = computed(() => {
    const hasThirdPlace = props.bracketData?.has_third_place_match ||
        bracket.value?.has_third_place_match;

    if (hasThirdPlace === "false" || hasThirdPlace === false) {
        return false;
    }

    if (hasThirdPlace === "true" || hasThirdPlace === true) {
        return true;
    }

    const match = thirdPlaceMatch.value;
    return match && match.match_id && !match.match_id.includes('placeholder');
});
</script>

<style scoped>
.bg-image-container {
    background-image: url("@/assets/images/bracket-bg.png");
    background-size: 100% 100%;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

.bracket-scroll-container {
    overflow-x: scroll !important;
    overflow-y: auto !important;
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 #f1f1f1;
}

.bracket-scroll-container::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.bracket-scroll-container::-webkit-scrollbar:horizontal {
    height: 10px;
}

.bracket-scroll-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.bracket-scroll-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 5px;
}

.bracket-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.bracket-scroll-container::-webkit-scrollbar-corner {
    background: transparent;
}

.connector-h-right,
.connector-h-left,
.connector-vertical-right,
.connector-vertical-left,
.connector-next-right,
.connector-next-left,
.connector-line-right-full,
.connector-line-left-full,
.connector-line-in-left,
.connector-line-in-right {
    background-color: rgba(255, 255, 255, 0.6);
    position: absolute;
    z-index: 0;
}

.connector-h-right {
    right: -40px;
    top: 50%;
    width: 40px;
    height: 2px;
}

.connector-vertical-right {
    right: -40px;
    top: 25%;
    bottom: 25%;
    width: 2px;
}

.connector-next-right {
    right: -80px;
    top: 50%;
    width: 40px;
    height: 2px;
}

.connector-line-right-full {
    right: -80px;
    top: 50%;
    width: 80px;
    height: 2px;
}

.connector-h-left {
    left: -40px;
    top: 50%;
    width: 40px;
    height: 2px;
}

.connector-vertical-left {
    left: -40px;
    top: 25%;
    bottom: 25%;
    width: 2px;
}

.connector-next-left {
    left: -80px;
    top: 50%;
    width: 40px;
    height: 2px;
}

.connector-line-left-full {
    left: -80px;
    top: 50%;
    width: 80px;
    height: 2px;
}

.connector-line-in-left {
    left: -40px;
    top: 50%;
    width: 40px;
    height: 2px;
}

.connector-line-in-right {
    right: -40px;
    top: 50%;
    width: 40px;
    height: 2px;
}
</style>
