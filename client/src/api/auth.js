import api from './index';

export const authApi = {
    login(email, password) {
        return api.post('/web/auth/login', { email, password });
    },

    register(email, password, firstName, lastName, language) {
        return api.post('/web/auth/register', { email, password, first_name: firstName, last_name: lastName, language });
    },

    forgotPassword(email) {
        return api.post('/web/auth/forgot-password', { email });
    },

    resetPassword(token, password) {
        return api.post('/web/auth/reset-password', { token, password });
    },
    getMe() {
        return api.get('/web/me');
    },

    updateProfile(data) {
        return api.post('/web/me/profile', data);
    },

    updatePassword(currentPassword, newPassword) {
        return api.post('/web/me/password', { current: currentPassword, new: newPassword });
    }
};
