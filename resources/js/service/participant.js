import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const participantEndpoint = API_ENDPOINT.PARTICIPANT;

export const inviteFriends = async (tournamentId) => {
  return axiosInstance.post(`${participantEndpoint}/invite-friend/${tournamentId}`)
    .then((response) => response.data.data);
}

export const sendInvitation = async (tournamentId, userIds) => {
  return axiosInstance.post(`${participantEndpoint}/invite-user/${tournamentId}`, {
    user_ids: userIds,
  }).then((response) => response.data.data)
};

export const inviteStaffs = async (tournamentId, userId) => {
  return axiosInstance.post(`${participantEndpoint}/invite-staff/${tournamentId}`, {
    user_ids: [userId],
  }).then((response) => response.data.data)
}

export const listInviteUsers = async (tournamentId, params) => {
  return axiosInstance.post(`${participantEndpoint}/list-invite/${tournamentId}`, {
    params,
  }).then((response) => response.data.data)
}

export const confirmParticipants = async (participantId) => {
  return axiosInstance.post(`${participantEndpoint}/confirm/${participantId}`)
    .then((response) => response.data.data);
}

export const getParticipantsNonTeam = async(tournamentId) => {
  return axiosInstance.post(`${participantEndpoint}/list-member/${tournamentId}`)
  .then((response) => response?.data?.data);
}