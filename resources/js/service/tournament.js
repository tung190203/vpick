import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const tournamentEndpoint = API_ENDPOINT.TOURNAMENT;

export const getTournaments = async (params) => {
  return axiosInstance.get(`${tournamentEndpoint}/index`, { params })
    .then((response) => response.data.data);
}

export const getTournamentById = async (id) => {
  return axiosInstance.get(`${tournamentEndpoint}/${id}`)
    .then((response) => response.data.data);
}

export const storeTournament = async (tournamentData) => {
  return axiosInstance.post(`${tournamentEndpoint}/store`, tournamentData)
    .then((response) => response.data.data);
}