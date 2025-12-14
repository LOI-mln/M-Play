import axios from 'axios';

// Create a basic client instance
// We don't set a baseURL initially because it will vary per login
export const createXcClient = (baseUrl) => {
    return axios.create({
        baseURL: baseUrl,
        timeout: 15000,
    });
};

export const getStreamUrl = (dns, user, pass, streamId, extension = 'ts') => {
    // Ensuring dns has http/https, if not assume http
    let protocol = 'http';
    let host = dns;

    if (dns.startsWith('http://') || dns.startsWith('https://')) {
        const url = new URL(dns);
        protocol = url.protocol.replace(':', '');
        host = url.host; // includes port
    }

    return `${protocol}://${host}/live/${user}/${pass}/${streamId}.${extension}`;
};
