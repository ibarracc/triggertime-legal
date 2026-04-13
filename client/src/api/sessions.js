import api from './index';

export const sessionsApi = {
    getSessions(params = {}) {
        return api.get('/web/sessions', { params });
    },

    getSession(uuid) {
        return api.get(`/web/sessions/${uuid}`);
    },
};
