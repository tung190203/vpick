// Play Mode: 1=Vui vẻ, 2=Thi đấu, 3=Luyện tập
export const playModes = [
    { id: 1, name: 'Vui vẻ' },
    { id: 2, name: 'Thi đấu' },
    { id: 3, name: 'Luyện tập' },
]

// Format: 1=Đánh đơn, 2=Đánh đôi, 3=Đôi nam, 4=Đôi nữ, 5=Mixed
export const formats = [
    { id: 1, name: 'Đánh đơn' },
    { id: 2, name: 'Đánh đôi' },
    { id: 3, name: 'Đôi nam' },
    { id: 4, name: 'Đôi nữ' },
    { id: 5, name: 'Mixed' },
]

// Mapping từ match_type cũ sang play_mode và format
// match_type: 1=Giao hữu, 2=Đánh đơn, 3=Đánh đôi, 4=Tập luyện
export const matchTypeToPlayModeAndFormat = {
    1: { play_mode: 1, format: null },        // Giao hữu → Vui vẻ
    2: { play_mode: 2, format: 1 },          // Đánh đơn → Thi đấu + Đánh đơn
    3: { play_mode: 2, format: 2 },          // Đánh đôi → Thi đấu + Đánh đôi
    4: { play_mode: 3, format: null },       // Tập luyện → Luyện tập
}
