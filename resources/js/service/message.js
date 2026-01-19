import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const miniTournamentMessageEndpoint = API_ENDPOINT.MESSAGE.MINITOURNAMENT();
const tournamentMessageEndpoint = API_ENDPOINT.MESSAGE.TOURNAMENT();

export const getTournamentMessages = async (tournamentId, params) => {
  return axiosInstance.get(`${tournamentMessageEndpoint}/index/${tournamentId}`, {
    params,
  }).then((response) => response.data);
}

export const sendTournamentMessage = async (tournamentId, data) => {
  return axiosInstance.post(`${tournamentMessageEndpoint}/${tournamentId}`, data)
    .then((response) => response.data);
}

export const getMiniTournamentMessages = async (miniTournamentId, params) => {
  return axiosInstance.get(`${miniTournamentMessageEndpoint}/index/${miniTournamentId}`, {
    params,
  }).then((response) => response.data);
}

export const sendMiniTournamentMessage = async (miniTournamentId, data) => {
    return axiosInstance.post(`${miniTournamentMessageEndpoint}/${miniTournamentId}`, data)
        .then((response) => response.data);
}
