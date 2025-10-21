import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

export const getAllLocations = async () => {
    return axiosInstance.get(API_ENDPOINT.LOCATION).then((response) => response.data.data);
}