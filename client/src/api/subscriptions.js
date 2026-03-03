import api from './index';

export default {
    createCheckout(payload = {}) {
        return api.post('/web/subscriptions/checkout', payload);
    },
    getPortalUrl() {
        return api.post('/web/subscriptions/portal');
    }
};
