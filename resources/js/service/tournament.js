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

export const updateTournament = async (id, tournamentData) => {
  return axiosInstance.post(`${tournamentEndpoint}/update/${id}`, tournamentData)
    .then((response) => response.data.data);
}

export const deleteTournament = async (id) => {
  return axiosInstance.post(`${tournamentEndpoint}/delete`, { id })
    .then((response) => response.data.data);
}

export const getBracketByTournamentId = async (tournamentId) => {
  return axiosInstance.get(`${tournamentEndpoint}/${tournamentId}/bracket`)
    .then((response) => response.data.data);
}