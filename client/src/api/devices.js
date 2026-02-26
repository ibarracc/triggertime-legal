import api from './index';

export const devicesApi = {
    getDevices() {
        return api.get('/web/devices');
    },

    linkDevice(linkCode) {
        return api.post('/web/devices/link', { link_code: linkCode });
    },

    unlinkDevice(deviceUuid) {
        return api.post(`/web/devices/${deviceUuid}/unlink`);
    },

    updateDevice(deviceUuid, data) {
        return api.put(`/web/devices/${deviceUuid}`, data);
    }
};
