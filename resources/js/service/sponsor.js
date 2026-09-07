import http, { get, post } from '@/utils/httpRequest';

export const getAdminSponsors = async (params = {}) => {
  return get('/admin/sponsors', { params });
};

export const createAdminSponsor = async (data) => {
  return post('/admin/sponsors', data);
};

export const updateAdminSponsor = async (id, data) => {
  return post(`/admin/sponsors/${id}`, data);
};

export const toggleAdminSponsorStatus = async (id) => {
  return http.patch(`/admin/sponsors/${id}/toggle-status`);
};

export const reorderAdminSponsors = async (orders) => {
  return post('/admin/sponsors/reorder', { orders });
};

export const deleteAdminSponsor = async (id) => {
  return http.delete(`/admin/sponsors/${id}`);
};

export const getPublicSponsors = async () => {
  return get('/sponsors');
};
