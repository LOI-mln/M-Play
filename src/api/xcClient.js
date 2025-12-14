import axios from 'axios';

// Create a basic client instance
export const createXcClient = (baseUrl) => {
    return axios.create({
        baseURL: baseUrl,
        timeout: 15000,
    });
};

export const getStreamUrl = (dns, user, pass, streamId, extension = 'ts', type = 'live') => {
    let protocol = 'http';
    let host = dns;

    if (dns.startsWith('http://') || dns.startsWith('https://')) {
        const url = new URL(dns);
        protocol = url.protocol.replace(':', '');
        host = url.host;
    }

    // Live: /live/user/pass/id.ts
    // Movie: /movie/user/pass/id.mp4 (usually)
    // Series: /series/user/pass/id.mp4 (usually)

    let prefix = 'live';
    if (type === 'movie') prefix = 'movie';
    if (type === 'series') prefix = 'series';

    // For HLS in browser, usually we only use m3u8 for Live. 
    // VOD/Series are often directly seekable .mp4 or .mkv files served by XC.
    // If we request .m3u8 for VOD on some XC servers, it might work if transcoding is enabled, but often .mp4 is safer for VOD if the browser supports it.
    // However, the prompt asked to handle .ts.
    // For Safety in this React App:
    // - Live: use .m3u8
    // - Movie/Series: use .mp4 or original extension if known. 
    //   If we force 'm3u8' on VOD it might fail. 
    //   Let's default VOD/Series to the requested extension, but often it's mp4.

    return `${protocol}://${host}/${prefix}/${user}/${pass}/${streamId}.${extension}`;
};
