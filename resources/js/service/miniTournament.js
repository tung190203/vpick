import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const miniTournamentEndpoint = API_ENDPOINT.MINI_TOURNAMENT;

export const storeMiniTournament = async (data) => {
  return axiosInstance.post(`${miniTournamentEndpoint}/store`, data)
    .then((response) => response.data.data);
}