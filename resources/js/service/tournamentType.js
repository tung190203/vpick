import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const tournamentTypeEndpoint = API_ENDPOINT.TOURNAMENT_TYPE;

export const createTournamentType = async (tournamentData) => {
  return axiosInstance.post(`${tournamentTypeEndpoint}/store`, tournamentData)
    .then((response) => response.data.data);
}