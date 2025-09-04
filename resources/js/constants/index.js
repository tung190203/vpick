export const LOCAL_STORAGE_KEY = {
    LOGIN_TOKEN: "access_token",
    REFRESH_TOKEN: 'refresh_token',
};

export const LOCAL_STORAGE_USER = {
  USER: "user",
};

export const ROLE = {
  PLAYER: 'player',
  REFEREE: 'referee',
  ADMIN: 'admin',
};

export const API_ENDPOINT = {
  AUTH: '/auth',
  USER: '/user',
  TOURNAMENT: '/tournaments',
};

export const TOURNAMENT_STATUS = {
  UPCOMING: 'upcoming',
  ONGOING: 'ongoing',
  FINISHED: 'finished',
};

export const TOURNAMENT_STATUS_LABEL = {
  UPCOMING: 'Sắp diễn ra',
  ONGOING: 'Đang diễn ra',
  FINISHED: 'Đã kết thúc',
};

export const MATCH_STATUS = {
  PENDING: 'pending',
  COMPLETED: 'completed',
  DISPUTED: 'disputed',
};

export const MATCH_STATUS_LABEL = {
  PENDING: 'Chờ đấu',
  COMPLETED: 'Đã hoàn thành',
  DISPUTED: 'Tranh chấp',
};

export const TYPE_OF_TOURNAMENT = {
  SINGLE: 'single',
  DOUBLE: 'double',
  MIXED: 'mixed',
};

export const TYPE_OF_TOURNAMENT_LABEL = {
  SINGLE: 'Đơn',
  DOUBLE: 'Đôi',
  MIXED: 'Hỗn hợp',
};