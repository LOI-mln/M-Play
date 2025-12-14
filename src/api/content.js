import { createXcClient } from './xcClient';

// --- LIVE TV ---
export const getLiveCategories = async (dns, username, password) => {
    try {
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;
        const client = createXcClient(baseUrl);
        const response = await client.get('/player_api.php', {
            params: { username, password, action: 'get_live_categories' }
        });
        return response.data;
    } catch (error) {
        console.error("Error fetching live categories:", error);
        return [];
    }
};

export const getLiveStreams = async (dns, username, password, categoryId = null) => {
    try {
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;
        const client = createXcClient(baseUrl);
        const params = { username, password, action: 'get_live_streams' };
        if (categoryId) params.category_id = categoryId;
        const response = await client.get('/player_api.php', { params });
        return response.data;
    } catch (error) {
        console.error("Error fetching live streams:", error);
        return [];
    }
};

// --- MOVIES (VOD) ---
export const getVodCategories = async (dns, username, password) => {
    try {
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;
        const client = createXcClient(baseUrl);
        const response = await client.get('/player_api.php', {
            params: { username, password, action: 'get_vod_categories' }
        });
        return response.data;
    } catch (error) {
        console.error("Error fetching VOD categories:", error);
        return [];
    }
};

export const getVodStreams = async (dns, username, password, categoryId = null) => {
    try {
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;
        const client = createXcClient(baseUrl);
        const params = { username, password, action: 'get_vod_streams' };
        if (categoryId) params.category_id = categoryId;
        const response = await client.get('/player_api.php', { params });
        return response.data;
    } catch (error) {
        console.error("Error fetching VOD streams:", error);
        return [];
    }
};

// --- SERIES ---
export const getSeriesCategories = async (dns, username, password) => {
    try {
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;
        const client = createXcClient(baseUrl);
        const response = await client.get('/player_api.php', {
            params: { username, password, action: 'get_series_categories' }
        });
        return response.data;
    } catch (error) {
        console.error("Error fetching series categories:", error);
        return [];
    }
};

export const getSeries = async (dns, username, password, categoryId = null) => {
    try {
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;
        const client = createXcClient(baseUrl);
        const params = { username, password, action: 'get_series' };
        if (categoryId) params.category_id = categoryId;
        const response = await client.get('/player_api.php', { params });
        return response.data;
    } catch (error) {
        console.error("Error fetching series:", error);
        return [];
    }
};

export const getSeriesInfo = async (dns, username, password, seriesId) => {
    try {
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;
        const client = createXcClient(baseUrl);
        const response = await client.get('/player_api.php', {
            params: { username, password, action: 'get_series_info', series_id: seriesId }
        });
        return response.data; // Returns { episodes: {}, info: {} }
    } catch (error) {
        console.error("Error fetching series info:", error);
        return null;
    }
};
