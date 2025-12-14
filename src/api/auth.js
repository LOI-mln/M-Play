import { createXcClient } from './xcClient';

export const loginUser = async (dns, username, password) => {
    try {
        // Ensure DNS has protocol
        const baseUrl = dns.startsWith('http') ? dns : `http://${dns}`;

        const client = createXcClient(baseUrl);

        const response = await client.get('/player_api.php', {
            params: {
                username,
                password
            }
        });

        // XC API returns 200 OK even on auth failure but with specific JSON structure
        if (response.data && response.data.user_info && response.data.user_info.auth === 1) {
            return {
                success: true,
                data: response.data
            };
        } else {
            return {
                success: false,
                error: 'Authentication failed. Please check credentials.'
            };
        }
    } catch (error) {
        return {
            success: false,
            error: error.message || 'Connection error'
        };
    }
};
