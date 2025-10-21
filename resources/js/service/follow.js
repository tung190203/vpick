import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const followEndpoint = API_ENDPOINT.FOLLOW;

export const getFriendList = async (params = {}) => {
    return axiosInstance.get(`${followEndpoint}/list-friends`, { params })
        .then((response) => response.data.data);
}