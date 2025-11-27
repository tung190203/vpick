import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const matchEndpoint = API_ENDPOINT.MATCHES;

export const detailMatches = async(id) => {
    return axiosInstance.get(`${matchEndpoint}/detail/${id}`).then((response) => response.data.data);
}