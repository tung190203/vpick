export const getRoleName = (role) => {
    switch (role) {
        case 'admin':
            return 'Quản trị viên'
        case 'manager':
            return 'Quản lý'
        case 'secretary':
            return 'Thư ký'
        case 'treasurer':
            return 'Thủ quỹ'
        default:
            return 'Thành viên'
    }
}