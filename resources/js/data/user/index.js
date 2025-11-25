/**
 * Định nghĩa các hằng số liên quan đến giới tính
 */
const GENDER = {
    MALE: 1,
    FEMALE: 2,
    OTHER: 0,
    NO_PUBLIC: 3
};

/**
 * Danh sách tùy chọn giới tính (dùng cho form)
 */
export const genderOptions = [
    { value: GENDER.MALE, label: "Nam" },
    { value: GENDER.FEMALE, label: "Nữ" },
    { value: GENDER.OTHER, label: "Khác" },
    { value: GENDER.NO_PUBLIC, label: "Không công khai" }
];