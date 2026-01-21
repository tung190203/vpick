<template>
    <div
        class="w-full h-full bg-image-container p-8 overflow-x-scroll overflow-y-auto scroll-smooth bracket-scroll-container"
    >
        <div
            class="flex justify-between items-stretch min-w-max gap-8 lg:gap-12 mx-auto flex-wrap lg:flex-nowrap"
        >
            <div class="flex flex-col gap-6 w-[300px] flex-shrink-0 pt-12">
                <div
                    class="flex items-center justify-between bg-gray-100 rounded-lg p-3 border border-gray-200"
                >
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-700 text-sm uppercase"
                            >Bảng xếp hạng</span
                        >
                    </div>
                </div>

                <div
                    v-for="group in leftPools"
                    :key="group.group_id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                >
                    <div
                        class="bg-gray-100 px-4 py-2 font-bold text-gray-700 text-sm border-b border-gray-200"
                    >
                        {{ group.group_name }}
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="(standing, idx) in group.rankings ||
                            group.standings"
                            :key="idx"
                            class="flex items-center justify-between px-3 py-2 text-sm"
                        >
                            <div
                                class="flex items-center gap-2 overflow-hidden"
                            >
                                <span
                                    class="w-4 text-center text-gray-400 text-xs font-bold"
                                    >{{ idx + 1 }}</span
                                >
                                <img
                                    :src="
                                        getTeamAvatar(
                                            standing.team || {
                                                name: standing.team_name,
                                                team_avatar:
                                                    standing.team_avatar,
                                            },
                                        )
                                    "
                                    :alt="getTeamName(standing.team || { name: standing.team_name })"
                                    class="w-6 h-6 rounded-full bg-gray-200 object-cover"
                                />
                                <span
                                    class="truncate font-medium text-gray-700 max-w-[120px]"
                                    :title="
                                        getTeamName(
                                            standing.team || {
                                                name: standing.team_name,
                                            },
                                        )
                                    "
                                >
                                    {{
                                        getTeamName(
                                            standing.team || {
                                                name: standing.team_name,
                                            },
                                        )
                                    }}
                                </span>
                            </div>
                            <span class="font-bold text-blue-600">{{
                                standing.points
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-12 min-w-[200px] items-start">
                <div
                    v-for="(round, rIdx) in leftRounds"
                    :key="rIdx"
                    class="flex flex-col h-full justify-around"
                >
                    <div
                        class="flex items-center justify-between bg-gray-100 rounded-lg p-3 border border-gray-200 mb-4"
                    >
                        <div class="flex flex-col">
                            <span
                                class="font-bold text-gray-700 text-sm uppercase"
                                >{{ round.round_name }}</span
                            >
                            <span class="text-xs text-gray-500">{{ round.matches?.[0]?.scheduled_at ? formatDateTime(round.matches[0].scheduled_at) : "Chưa xếp lịch" }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 justify-around w-full min-h-[200px]">
                        <div
                            v-for="(pair, pIdx) in chunkMatches(round.matches)"
                            :key="pIdx"
                            class="flex flex-col justify-around relative"
                            :class="pair.length === 2 ? 'flex-1' : ''"
                        >
                            <template v-if="pair.length === 2">
                                <div class="relative flex items-center z-10">
                                    <MatchCard
                                        :match="pair[0]"
                                        side="left"
                                        :round-type="getRoundType(round.round_name)"
                                        :match-index="pIdx * 2"
                                        :previous-matches="getPreviousMatches(round, leftRounds, rIdx)"
                                        :round-name="round.round_name"
                                        :is-right-side="false"
                                    />
                                    <div class="connector-h-right"></div>
                                </div>

                                <div class="relative flex items-center z-10">
                                    <MatchCard
                                        :match="pair[1]"
                                        side="left"
                                        :round-type="getRoundType(round.round_name)"
                                        :match-index="pIdx * 2 + 1"
                                        :previous-matches="getPreviousMatches(round, leftRounds, rIdx)"
                                        :round-name="round.round_name"
                                        :is-right-side="false"
                                    />
                                    <div class="connector-h-right"></div>
                                </div>

                                <div class="connector-vertical-right"></div>
                                <div class="connector-next-right"></div>
                            </template>

                            <template v-else>
                                <div class="relative flex items-center z-10">
                                    <MatchCard
                                        :match="pair[0]"
                                        side="left"
                                        :round-type="getRoundType(round.round_name)"
                                        :match-index="pIdx"
                                        :previous-matches="getPreviousMatches(round, leftRounds, rIdx)"
                                        :round-name="round.round_name"
                                        :is-right-side="false"
                                    />
                                    <div
                                        class="connector-line-right-full"
                                    ></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col justify-center items-center gap-4 px-1 relative -mt-8"
            >
                <div
                    class="w-full flex items-center justify-between bg-gray-100 rounded-lg p-3 border border-gray-200 mb-4"
                >
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-700 text-sm uppercase"
                            >CHUNG KẾT</span
                        >
                        <span class="text-xs text-gray-500">{{ finalMatch?.scheduled_at ? formatDateTime(finalMatch.scheduled_at) : "Chưa xếp lịch" }}</span>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-2 relative">

                    <div class="relative">
                        <MatchCard
                            :match="finalMatch"
                            :is-final="true"
                            round-type="final"
                            :match-index="0"
                            :previous-matches="[...(leftRounds[leftRounds.length - 1]?.matches || []), ...(rightRounds[rightRounds.length - 1]?.matches || [])]"
                            round-name="Chung kết"
                            :is-right-side="false"
                        />
                    </div>
                </div>

                <div
                    v-if="thirdPlaceMatch"
                    class="flex flex-col items-center gap-2 mt-8 opacity-90 scale-90"
                >
                    <div
                        class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1"
                    >
                        Tranh hạng 3
                    </div>
                    <MatchCard
                        :match="thirdPlaceMatch"
                    />
                </div>
            </div>

            <div class="flex gap-12 flex-row-reverse min-w-[200px]">
                <div
                    v-for="(round, rIdx) in rightRounds"
                    :key="rIdx"
                    class="flex flex-col h-full justify-around"
                >
                    <div
                        class="flex items-center justify-between bg-gray-100 rounded-lg p-3 border border-gray-200 mb-4"
                    >
                        <div class="flex flex-col">
                            <span
                                class="font-bold text-gray-700 text-sm uppercase"
                                >{{ round.round_name || round.title }}</span
                            >
                            <span class="text-xs text-gray-500">{{ round.matches?.[0]?.scheduled_at ? formatDateTime(round.matches[0].scheduled_at) : "Chưa xếp lịch" }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 justify-around w-full min-h-[200px]">
                        <div
                            v-for="(pair, pIdx) in chunkMatches(round.matches)"
                            :key="pIdx"
                            class="flex flex-col justify-around relative"
                            :class="pair.length === 2 ? 'flex-1' : ''"
                        >
                            <template v-if="pair.length === 2">
                                <div
                                    class="relative flex items-center flex-row-reverse z-10"
                                >
                                    <MatchCard
                                        :match="pair[0]"
                                        side="right"
                                        :round-type="getRoundType(round.round_name)"
                                        :match-index="pIdx * 2"
                                        :previous-matches="getPreviousMatches(round, rightRounds, rIdx)"
                                        :round-name="round.round_name"
                                        :is-right-side="true"
                                    />
                                    <div class="connector-h-left"></div>
                                </div>

                                <div
                                    class="relative flex items-center flex-row-reverse z-10"
                                >
                                    <MatchCard
                                        :match="pair[1]"
                                        side="right"
                                        :round-type="getRoundType(round.round_name)"
                                        :match-index="pIdx * 2 + 1"
                                        :previous-matches="getPreviousMatches(round, rightRounds, rIdx)"
                                        :round-name="round.round_name"
                                        :is-right-side="true"
                                    />
                                    <div class="connector-h-left"></div>
                                </div>

                                <div class="connector-vertical-left"></div>
                                <div class="connector-next-left"></div>
                            </template>

                            <template v-else>
                                <div
                                    class="relative flex items-center flex-row-reverse z-10"
                                >
                                    <MatchCard
                                        :match="pair[0]"
                                        side="right"
                                        :round-type="getRoundType(round.round_name)"
                                        :match-index="pIdx"
                                        :previous-matches="getPreviousMatches(round, rightRounds, rIdx)"
                                        :round-name="round.round_name"
                                        :is-right-side="true"
                                    />
                                    <div class="connector-line-left-full"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-6 w-[300px] flex-shrink-0 pt-12">
                <div
                    class="flex items-center justify-between bg-gray-100 rounded-lg p-3 border border-gray-200"
                >
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-700 text-sm uppercase"
                            >Bảng xếp hạng</span
                        >
                    </div>
                </div>

                <div
                    v-for="group in rightPools"
                    :key="group.group_id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                >
                    <div
                        class="bg-gray-100 px-4 py-2 font-bold text-gray-700 text-sm border-b border-gray-200"
                    >
                        {{ group.group_name }}
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="(standing, idx) in group.rankings ||
                            group.standings"
                            :key="idx"
                            class="flex items-center justify-between px-3 py-2 text-sm"
                        >
                            <div
                                class="flex items-center gap-2 overflow-hidden"
                            >
                                <span
                                    class="w-4 text-center text-gray-400 text-xs font-bold"
                                    >{{ idx + 1 }}</span
                                >
                                <img
                                    :src="
                                        getTeamAvatar(
                                            standing.team || {
                                                name: standing.team_name,
                                                team_avatar:
                                                    standing.team_avatar,
                                            },
                                        )
                                    "
                                    :alt="getTeamName(standing.team || { name: standing.team_name })"
                                    class="w-6 h-6 rounded-full bg-gray-200 object-cover"
                                />
                                <span
                                    class="truncate font-medium text-gray-700 max-w-[120px]"
                                    :title="
                                        getTeamName(
                                            standing.team || {
                                                name: standing.team_name,
                                            },
                                        )
                                    "
                                >
                                    {{
                                        getTeamName(
                                            standing.team || {
                                                name: standing.team_name,
                                            },
                                        )
                                    }}
                                </span>
                            </div>
                            <span class="font-bold text-blue-600">{{
                                standing.points
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
/**
 * BracketMixedPreview Component
 *
 * Hiển thị sơ đồ thi đấu dạng bracket với cấu trúc:
 * - Bảng xếp hạng 2 bên (trái: bảng A-D, phải: bảng E-H)
 * - Nhánh đấu loại trực tiếp ở giữa (leftSide, rightSide)
 * - Trận chung kết ở trung tâm
 *
 * Layout: Butterfly structure (đối xứng)
 * - Nhánh trái: từ trái sang giữa
 * - Nhánh phải: từ phải sang giữa (sử dụng flex-row-reverse)
 *
 * Data source: API api/tournament-types/{id}/bracket-new
 */
import { computed, defineComponent, h, ref, onMounted, watch } from "vue";
import { VideoCameraIcon } from "@heroicons/vue/24/solid";
import * as TournamentTypeService from '@/service/tournamentType.js'
import { toast } from 'vue3-toastify'

/**
 * Tạo label cho vòng 16 đội dựa trên matchIndex và nhánh đấu
 * Ví dụ: Trận 1 nhánh trái = "Nhất bảng A" vs "Nhì bảng B"
 */
const getRound16Label = (matchIndex, isRightSide) => {
    const groupLetters = isRightSide ? ['E', 'F', 'G', 'H'] : ['A', 'B', 'C', 'D']
    const baseIndex = isRightSide ? 4 : 0
    const actualIndex = matchIndex + baseIndex
    const matchNum = Math.floor(actualIndex / 2)
    const isHome = (actualIndex % 2) === 0
    const groupIndex = matchNum % groupLetters.length

    if (isHome) {
        return `Nhất bảng ${groupLetters[groupIndex]}`
    }
    const nextGroupIndex = (groupIndex + 1) % groupLetters.length
    return `Nhì bảng ${groupLetters[nextGroupIndex]}`
}

const getQuarterLabel = (matchIndex, isRightSide) => {
    return `Thắng Trận ${matchIndex + (isRightSide ? 5 : 1)}`
}

const getSemiLabel = (matchIndex, isRightSide) => {
    const baseSemiNum = isRightSide ? 2 : 1
    return `Thắng Tứ kết ${Math.floor(matchIndex / 2) + baseSemiNum}`
}

const getFinalLabel = (matchIndex) => {
    return matchIndex === 0 ? 'Thắng Bán kết 1' : 'Thắng Bán kết 2'
}

/**
 * Tạo label tham chiếu cho đội dựa trên context của trận đấu
 * Nếu có team name thì trả về tên, nếu không thì tạo label tham chiếu
 */
const getMatchLabel = (team, roundType, matchIndex, isRightSide, previousMatches, roundName) => {
    if (team && (team.name || team.team_name)) {
        return team.name || team.team_name
    }

    if (roundType === 'round16' || roundName?.includes('16') || roundName?.includes('Vòng 16')) {
        return getRound16Label(matchIndex, isRightSide)
    }

    if (roundType === 'quarter' || roundName?.includes('Tứ kết')) {
        return getQuarterLabel(matchIndex, isRightSide)
    }

    if (roundType === 'semi' || roundName?.includes('Bán kết')) {
        return getSemiLabel(matchIndex, isRightSide)
    }

    if (roundType === 'final' || roundName?.includes('Chung kết')) {
        return getFinalLabel(matchIndex)
    }

    return 'Chưa xác định'
}

const MatchCard = defineComponent({
    props: ["match", "side", "isFinal", "roundType", "matchIndex", "previousMatches", "roundName", "isRightSide"],
    setup(props, { emit }) {
        const getMatchLabelLocal = (team, roundType, matchIndex, isRightSide, previousMatches, roundName) => {
            if (team && team.name && team.name !== 'TBD' && team.team_name && team.team_name !== 'TBD') {
                return team.name || team.team_name
            }
            return getMatchLabel(team, roundType, matchIndex, isRightSide, previousMatches, roundName)
        }

        /**
         * Lấy tên đội hoặc label tham chiếu
         * Nếu không có team name hợp lệ, tạo label dựa trên vị trí trong match (home/away)
         */
        const getTeamName = (team, position) => {
            const hasValidTeamName = team &&
                (team.name || team.team_name) &&
                team.name !== 'TBD' &&
                team.team_name !== 'TBD' &&
                team.placeholder !== 'TBD';

            if (hasValidTeamName) {
                return team.name || team.team_name;
            }

            const roundType = props.roundType || (props.isFinal ? 'final' : '')
            const matchIdx = props.matchIndex || 0
            const isRight = props.isRightSide || false
            const prevMatches = props.previousMatches || null
            const rName = props.roundName || ''

            // Mỗi match có 2 đội: home (index chẵn) và away (index lẻ)
            const finalMatchIndex = matchIdx * 2 + (position === 'home' ? 0 : 1)

            return getMatchLabelLocal(null, roundType, finalMatchIndex, isRight, prevMatches, rName);
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
                console.error('Error formatting time:', e)
                return "Chưa xếp lịch";
            }
        };

        return () =>
            h(
                "div",
                {
                    class: [
                        "w-64 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden z-10",
                        props.isFinal
                            ? "border-yellow-400 ring-2 ring-yellow-100"
                            : "",
                    ],
                },
                [
                    h(
                        "div",
                        {
                            class: "flex justify-between items-center px-3 py-1.5 bg-gray-50 border-b border-gray-100 text-[10px] text-gray-500 uppercase font-bold",
                        },
                        [
                            h("span", `Sân ${props.match.court || 1}`),
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
                                            class: "w-6 h-6 rounded-full object-cover bg-gray-200 flex-shrink-0",
                                        }),
                                        h(
                                            "span",
                                            {
                                                class: [
                                                    "text-xs font-medium truncate max-w-[140px]",
                                                    isWinner(
                                                        props.match.home_team
                                                            ?.id,
                                                    )
                                                        ? "text-gray-900 font-bold"
                                                        : "text-gray-600",
                                                ],
                                            },
                                            getTeamName(props.match.home_team, 'home'),
                                        ),
                                    ],
                                ),
                                h(
                                    "span",
                                    {
                                        class: [
                                            "text-sm font-bold",
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
                                            class: "w-6 h-6 rounded-full object-cover bg-gray-200 flex-shrink-0",
                                        }),
                                        h(
                                            "span",
                                            {
                                                class: [
                                                    "text-xs font-medium truncate max-w-[140px]",
                                                    isWinner(
                                                        props.match.away_team
                                                            ?.id,
                                                    )
                                                        ? "text-gray-900 font-bold"
                                                        : "text-gray-600",
                                                ],
                                            },
                                            getTeamName(props.match.away_team, 'away'),
                                        ),
                                    ],
                                ),
                                h(
                                    "span",
                                    {
                                        class: [
                                            "text-sm font-bold",
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
    tournamentTypeId: {
        type: [Number, String],
        default: null
    },
    bracketData: {
        type: Object,
        default: () => ({})
    },
    rankData: {
        type: Object,
        default: () => ({})
    }
})

const bracket = ref({})
const rank = ref({})

const getTeamName = (team) => team?.name || "Chưa xác định";
const getTeamAvatar = (team) =>
    team?.team_avatar ||
    `https://placehold.co/40x40/BBBFCC/3E414C?text=${(team?.name || "T").charAt(0)}`;

/**
 * Tạo match placeholder khi chưa có dữ liệu
 * home_team và away_team = null để MatchCard tự tạo label tham chiếu
 */
const createPlaceholderMatch = (id) => ({
    match_id: `placeholder-${id}`,
    status: 'pending',
    scheduled_at: null,
    home_team: null,
    away_team: null,
    home_score: 0,
    away_score: 0
});

const createPlaceholderRound = (name, matchCount, startId) => ({
    round_name: name,
    matches: Array.from({ length: matchCount }, (_, i) => createPlaceholderMatch(startId + i))
});

/**
 * Format ngày giờ theo định dạng: "DD ThM - HH:MM"
 */
const formatDateTime = (scheduledAt) => {
    if (!scheduledAt) return "Chưa xếp lịch";
    try {
        const date = new Date(scheduledAt);
        if (Number.isNaN(date.getTime())) {
            return "Chưa xếp lịch";
        }
        const day = date.getDate();
        const monthNames = ["Th1", "Th2", "Th3", "Th4", "Th5", "Th6", "Th7", "Th8", "Th9", "Th10", "Th11", "Th12"];
        const month = monthNames[date.getMonth()];
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${day} ${month} - ${hours}:${minutes}`;
    } catch (e) {
        console.error('Error formatting date time:', e)
        return "Chưa xếp lịch";
    }
};

/**
 * Chia matches thành các cặp (2 matches = 1 pair) để vẽ connector dọc
 */
const chunkMatches = (matches) => {
    const pairs = [];
    if (!matches) return pairs;
    for (let i = 0; i < matches.length; i += 2) {
        pairs.push(matches.slice(i, i + 2));
    }
    return pairs;
};

const getRoundType = (roundName) => {
    if (!roundName) return '';
    const name = roundName.toLowerCase();
    if (name.includes('16') || name.includes('vòng 16')) return 'round16';
    if (name.includes('tứ kết') || name.includes('quarter')) return 'quarter';
    if (name.includes('bán kết') || name.includes('semi')) return 'semi';
    if (name.includes('chung kết') || name.includes('final')) return 'final';
    return '';
};

const getPreviousMatches = (currentRound, allRounds, currentRoundIndex) => {
    if (currentRoundIndex === 0) return null;
    const previousRound = allRounds[currentRoundIndex - 1];
    return previousRound?.matches || null;
};

/**
 * Merge standings từ rankData vào poolStage để hiển thị bảng xếp hạng
 */
const mergeStandingsIntoPoolStage = () => {
    if (!props.rankData || !props.rankData.group_rankings) return

    const groupRankings = props.rankData.group_rankings

    if (bracket.value.poolStage && bracket.value.poolStage.length > 0) {
        bracket.value.poolStage = bracket.value.poolStage.map(group => {
            const rankGroup = groupRankings.find(rg => rg.group_id === group.group_id)
            if (rankGroup && rankGroup.rankings) {
                return {
                    ...group,
                    standings: rankGroup.rankings.map(team => ({
                        team_id: team.team_id,
                        team_name: team.team_name,
                        team_avatar: team.team_avatar,
                        points: team.points || 0,
                        point_diff: team.point_diff || 0
                    })),
                    rankings: rankGroup.rankings.map(team => ({
                        team_id: team.team_id,
                        team_name: team.team_name,
                        team_avatar: team.team_avatar,
                        points: team.points || 0,
                        point_diff: team.point_diff || 0
                    }))
                }
            }
            return group
        })
    } else {
        bracket.value.poolStage = groupRankings.map(group => ({
            group_id: group.group_id,
            group_name: group.group_name,
            standings: group.rankings || [],
            rankings: group.rankings || []
        }))
    }
}

const fetchBracketData = async () => {
    try {
        const response = await TournamentTypeService.getBracketNewByTournamentTypeId(props.tournamentTypeId)
        bracket.value = {
            poolStage: response.poolStage || [],
            leftSide: response.leftSide || [],
            rightSide: response.rightSide || [],
            finalMatch: response.finalMatch || null,
            thirdPlaceMatch: response.thirdPlaceMatch || null
        }
        mergeStandingsIntoPoolStage()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Lấy dữ liệu bracket thất bại')
    }
}

onMounted(() => {
    if (props.bracketData && Object.keys(props.bracketData).length > 0) {
        bracket.value = {
            poolStage: props.bracketData.poolStage || [],
            leftSide: props.bracketData.leftSide || [],
            rightSide: props.bracketData.rightSide || [],
            finalMatch: props.bracketData.finalMatch || null,
            thirdPlaceMatch: props.bracketData.thirdPlaceMatch || null
        }
        mergeStandingsIntoPoolStage()
    } else if (props.tournamentTypeId) {
        fetchBracketData()
    } else {
        mergeStandingsIntoPoolStage()
    }
})

watch(() => props.rankData, () => {
    mergeStandingsIntoPoolStage()
}, { deep: true })

/**
 * Chia poolStage thành 2 nhánh: trái (A-D) và phải (E-H)
 */
const leftPools = computed(() => {
    const pools = bracket.value.poolStage || [];
    if (pools.length === 0) return [];
    const mid = Math.ceil(pools.length / 2);
    return pools.slice(0, mid);
});

const rightPools = computed(() => {
    const pools = bracket.value.poolStage || [];
    if (pools.length === 0) return [];
    const mid = Math.ceil(pools.length / 2);
    return pools.slice(mid);
});

/**
 * Nhánh trái của bracket (từ trái sang giữa)
 * Nếu không có dữ liệu, tạo placeholder structure
 */
const leftRounds = computed(() => {
    const data = (bracket.value.leftSide || []).sort((a, b) => a.round - b.round);
    if (data.length > 0) return data;

    return [
        createPlaceholderRound('Tứ kết', 2, 100),
        createPlaceholderRound('Bán kết', 1, 102)
    ];
});

/**
 * Nhánh phải của bracket (từ phải sang giữa)
 * Lọc bỏ trận tranh hạng 3 khỏi luồng chính
 */
const rightRounds = computed(() => {
    const data = (bracket.value.rightSide || [])
        .filter((r) => !r.matches.some((m) => m.is_third_place === true || m.is_third_place === 1))
        .sort((a, b) => a.round - b.round);

    if (data.length > 0) return data;

    return [
        createPlaceholderRound('Tứ kết', 2, 200),
        createPlaceholderRound('Bán kết', 1, 202)
    ];
});

const finalMatch = computed(() => {
    if (bracket.value.finalMatch) return bracket.value.finalMatch;
    return createPlaceholderMatch(999);
});

/**
 * Tìm trận tranh hạng 3 trong rightSide hoặc thirdPlaceMatch
 */
const thirdPlaceMatch = computed(() => {
    const rightSide = bracket.value.rightSide || [];
    for (const round of rightSide) {
        const match = round.matches.find((m) => m.is_third_place === true || m.is_third_place === 1);
        if (match) return match;
    }
    return bracket.value.thirdPlaceMatch;
});

</script>

<style scoped>
/**
 * Background image cho bracket container
 * Thay đổi đường dẫn image theo nhu cầu
 */
.bg-image-container {
    background-image: url('@/assets/images/bracket-bg.png'); /* Thay đổi đường dẫn tại đây */
    background-size: 100% 90%;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed; /* Giữ background cố định khi scroll */
}

/**
 * Scrollbar styling: Luôn hiển thị thanh cuộn ngang
 */
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

/**
 * Connector lines: Vẽ đường nối giữa các trận đấu trong bracket
 * Cấu trúc: Match -> Horizontal -> Vertical (nếu có pair) -> Next Round
 */
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
    background-color: #cbd5e1;
    position: absolute;
    z-index: 0;
}

/* Nhánh trái: từ trái sang giữa */
.connector-h-right {
    right: -24px;
    top: 50%;
    width: 24px;
    height: 2px;
}

.connector-vertical-right {
    right: -24px;
    top: 25%;
    bottom: 25%;
    width: 2px;
}

.connector-next-right {
    right: -48px;
    top: 50%;
    width: 24px;
    height: 2px;
}

.connector-line-right-full {
    right: -48px;
    top: 50%;
    width: 48px;
    height: 2px;
}

/* Nhánh phải: từ phải sang giữa (sử dụng flex-row-reverse) */
.connector-h-left {
    left: -24px;
    top: 50%;
    width: 24px;
    height: 2px;
}

.connector-vertical-left {
    left: -24px;
    top: 25%;
    bottom: 25%;
    width: 2px;
}

.connector-next-left {
    left: -48px;
    top: 50%;
    width: 24px;
    height: 2px;
}

.connector-line-left-full {
    left: -48px;
    top: 50%;
    width: 48px;
    height: 2px;
}

/* Connector cho trận chung kết (từ 2 nhánh vào) */
.connector-line-in-left {
    left: -48px;
    top: 50%;
    width: 48px;
    height: 2px;
}

.connector-line-in-right {
    right: -48px;
    top: 50%;
    width: 48px;
    height: 2px;
}
</style>
