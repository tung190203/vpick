import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

export const getAllClubs = async () => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}/index`).then((response) => response.data.data.clubs);
}

export const joinClub = async (clubId) => {
    return axiosInstance.post(`${API_ENDPOINT.CLUB}/join/${clubId}`).then((response) => response.data);
}

export const myClubs = async () => {
    return axiosInstance.get(`${API_ENDPOINT.CLUB}/my-clubs`).then((response) => response.data.data);
}