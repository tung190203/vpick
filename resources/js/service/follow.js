import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const followEndpoint = API_ENDPOINT.FOLLOW;

export const getFriendList = async (params = {}) => {
    return axiosInstance.get(`${followEndpoint}/list-friends`, { params })
        .then((response) => response.data.data);
}

export const followUser = async (params) => {
    return axiosInstance.post(`${followEndpoint}/store`, params).then((response) => response.data)
}

export const unFollowUser = async (params) => {
    return axiosInstance.post(`${followEndpoint}/delete`, params).then((response) => response.data)
}