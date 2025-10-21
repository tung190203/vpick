import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

export const getAllSports = async () => {
    return axiosInstance.get(`${API_ENDPOINT.SPORT}/index`).then((response) => response.data.data);
}