import axiosInstance from "@/utils/httpRequest.js";
import {API_ENDPOINT} from "@/constants/index.js";

const competitionLocationEndpoint = API_ENDPOINT.COMPETITION_LOCATION;

export const getAllCompetitionLocations = async (keyword = '') => {
  const payload = {
    keyword: keyword
  };
  return axiosInstance.post(`${competitionLocationEndpoint}/index`, payload)
    .then((response) => {
      return response.data.data.competition_locations;
    });
}