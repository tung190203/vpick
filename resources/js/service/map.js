import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const mapEndpoint = API_ENDPOINT.MAP;
const competitionLocationEndpoint = API_ENDPOINT.COMPETITION_LOCATION;

export const getCourtData = async (params = {}) => {
    return axiosInstance.get(`${competitionLocationEndpoint}/index`, { params }).then((response) => response.data);
}

export const getMatchesData = async (params = {}) => {
    return axiosInstance.get(`${mapEndpoint}/match`, { params }).then((response) => response.data.data);
}