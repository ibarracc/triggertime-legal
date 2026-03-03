import api from './index'

export const adminApi = {
    // Users
    getUsers: () => api.get('/admin/users'),
    impersonateUser: (id) => api.post(`/admin/users/${id}/impersonate`),
    createUser: (data) => api.post('/admin/users', data),
    updateUser: (id, data) => api.put(`/admin/users/${id}`, data),
    deleteUser: (id) => api.delete(`/admin/users/${id}`),

    // Licenses
    getLicenses: () => api.get('/admin/licenses'),
    importLicenses: (formData) => api.post('/admin/licenses/import', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    }),
    toggleLicenseAccess: (id) => api.post(`/admin/licenses/${id}/toggle-active`),
    updateLicense: (id, data) => api.put(`/admin/licenses/${id}`, data),

    // Devices
    getDevices: () => api.get('/admin/devices'),
    createDevice: (data) => api.post('/admin/devices', data),
    updateDevice: (id, data) => api.put(`/admin/devices/${id}`, data),
    deleteDevice: (id) => api.delete(`/admin/devices/${id}`),

    // Instances
    getInstances: () => api.get('/admin/instances'),
    createInstance: (data) => api.post('/admin/instances', data),
    updateInstance: (id, data) => api.put(`/admin/instances/${id}`, data),
    deleteInstance: (id) => api.delete(`/admin/instances/${id}`),

    // Subscriptions
    getSubscriptions: () => api.get('/admin/subscriptions'),

    // Remote Configs
    getRemoteConfigs: () => api.get('/admin/remote-config'),
    getRemoteConfig: (id) => api.get(`/admin/remote-config/${id}`),
    createRemoteConfig: (data) => api.post('/admin/remote-config', data),
    updateRemoteConfig: (id, data) => api.put(`/admin/remote-config/${id}`, data),

    // Versions
    getVersions: () => api.get('/admin/versions'),
    createVersion: (data) => api.post('/admin/versions', data),
    updateVersion: (id, data) => api.put(`/admin/versions/${id}`, data),
    deleteVersion: (id) => api.delete(`/admin/versions/${id}`),
}
