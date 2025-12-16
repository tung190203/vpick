export const getVisibilityText = (visibility) => {
    switch (visibility) {
        case 'open':
            return 'Công khai';
        case 'friend-only':
            return 'Bạn bè';
        case 'private':
            return 'Riêng tư';
        default:
            return 'Không xác định';
    }
};