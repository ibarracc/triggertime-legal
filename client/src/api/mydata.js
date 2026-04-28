import api from './index'

export const mydataApi = {
    getSyncData(type) {
        return api.get('/web/sync-data', { params: { type } })
    },
    updateSyncData(uuid, data) {
        return api.put(`/web/sync-data/${uuid}`, data)
    },
    deleteSyncData(uuid, type) {
        return api.delete(`/web/sync-data/${uuid}`, { params: { type } })
    },
}
