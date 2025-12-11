import axiosInstance from "@/utils/httpRequest.js";
import { API_ENDPOINT } from "@/constants/index.js";

const notificationEndpoint = API_ENDPOINT.NOTIFICATION;

export const getNotifications = async (params) => {
    return axiosInstance.get(`${notificationEndpoint}/index`, { params }).then((res) => res.data);
};

export const markAsRead = async (notification_id = null) => {
    return axiosInstance.post(`${notificationEndpoint}/mark-as-read`, { notification_id }).then((res) => res.data);
};

export const deleteNotification = async (notification_id = null) => {
    return axiosInstance.post(`${notificationEndpoint}/delete`, { notification_id }).then((res) => res.data);
};
