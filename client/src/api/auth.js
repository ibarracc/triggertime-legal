import api from './index';

export const authApi = {
    login(email, password) {
        return api.post('/web/auth/login', { email, password });
    },

    register(email, password, firstName, lastName, language, marketingOptin = false, upgradeToken = null) {
        const payload = { email, password, first_name: firstName, last_name: lastName, language, marketing_optin: marketingOptin }
        if (upgradeToken) {
            payload.upgrade_token = upgradeToken
        }
        return api.post('/web/auth/register', payload)
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
    },

    socialLogin(provider, idToken, firstName, lastName, marketingOptin = false) {
        return api.post('/web/auth/social-login', {
            provider,
            id_token: idToken,
            first_name: firstName,
            last_name: lastName,
            marketing_optin: marketingOptin,
        });
    },

    connectSocial(provider, idToken) {
        return api.post('/web/me/social-connect', { provider, id_token: idToken });
    },

    disconnectSocial(provider) {
        return api.post('/web/me/social-disconnect', { provider });
    },

    deleteAccount(email) {
        return api.delete('/web/me', { data: { email } });
    },

    resendVerification() {
        return api.post('/web/auth/resend-verification');
    },

    verifyEmail(uid, exp, sig) {
        return api.get('/web/auth/verify-email', { params: { uid, exp, sig } });
    },
};
