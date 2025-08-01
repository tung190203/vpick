import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const verifyEndpoint = API_ENDPOINT.VERIFICATION;

export const createVerification = async (data) => {
    return axiosInstance.post(`${verifyEndpoint}/create`, data).then((response) => response.data);
}

export const showVerification = async () => {
    return axiosInstance.get(`${verifyEndpoint}/show`).then((response) => response.data);
}