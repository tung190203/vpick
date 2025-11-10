import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const teamEndPoint = API_ENDPOINT.TEAMS;

export const autoAssignTeams = async (tournamentId) => {
  return axiosInstance.post(`${teamEndPoint}/auto-assign/${tournamentId}`)
    .then((response) => response?.data?.data || null);
}

export const getTeamsByTournamentId = async (tournamentId) => {
  return axiosInstance.get(`${teamEndPoint}/index/${tournamentId}`)
    .then((response) => response?.data?.data || []);
}

export const updateTeam = async (teamId, teamData) => {
  return axiosInstance.post(`${teamEndPoint}/update/${teamId}`, teamData)
    .then((response) => response?.data?.data || null);
}

export const createTeam = async (tournamentId, teamData) => {
  return axiosInstance.post(`${teamEndPoint}/create/${tournamentId}`, teamData)
    .then((response) => response?.data?.data || null);
}

export const deleteTeam = async (teamId) => {
  return axiosInstance.delete(`${teamEndPoint}/delete/${teamId}`)
    .then((response) => response?.data?.data || null);
}