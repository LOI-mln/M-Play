import { createXcClient } from './xcClient';

export const getLiveCategories = async (dns, username, password) => {
    try {
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;
        const client = createXcClient(baseUrl);

        const response = await client.get('/player_api.php', {
            params: {
                username,
                password,
                action: 'get_live_categories'
            }
        });

        return response.data;
    } catch (error) {
        console.error("Error fetching categories:", error);
        return [];
    }
};

export const getLiveStreams = async (dns, username, password, categoryId = null) => {
    try {
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;
        const client = createXcClient(baseUrl);

        const params = {
            username,
            password,
            action: 'get_live_streams'
        };

        if (categoryId) {
            params.category_id = categoryId;
        }

        const response = await client.get('/player_api.php', { params });

        return response.data;
    } catch (error) {
        console.error("Error fetching streams:", error);
        return [];
    }
};
