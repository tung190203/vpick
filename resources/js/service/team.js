import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const teamEndPoint = API_ENDPOINT.TEAMS;

export const autoAssignTeams = async (tournamentId) => {
  return axiosInstance.post(`${teamEndPoint}/auto-assign/${tournamentId}`)
    .then((response) => response?.data?.data || null);
}