import GroupUserIcon from '@/assets/images/groups.svg';
import BarChart from '@/assets/images/bar_chart.svg';
import MoneyTization from '@/assets/images/monetization_on.svg';
import MessageIcon from "@/assets/images/message.svg";
import CalendarIcon from "@/assets/images/calendar.svg";
import FundIcon from "@/assets/images/fund.svg";
import NotificationsIcon from "@/assets/images/notifications.svg";

export const CLUB_STATS = [
    {
        icon: GroupUserIcon,
        alt: 'Group User Icon',
        value: '42',
        label: 'Thành viên'
    },
    {
        icon: BarChart,
        alt: 'Bar Chart Icon',
        value: '2.5 - 3.5',
        label: 'Trình độ'
    },
    {
        icon: MoneyTization,
        alt: 'Money Icon',
        value: '50K',
        label: 'Vãng lai/Buổi'
    }
]

export const CLUB_MODULES = [
    {
        icon: FundIcon,
        alt: 'Fund Icon',
        label: 'Quỹ CLB'
    },
    {
        icon: CalendarIcon,
        alt: 'Calendar Icon',
        label: 'Tạo lịch'
    },
    {
        icon: NotificationsIcon,
        alt: 'Notifications Icon',
        label: 'Thông báo'
    },
    {
        icon: MessageIcon,
        alt: 'Message Icon',
        label: 'Nhóm chat'
    }
]