export const extractUrlParams = (url) => {
    if (!url.includes("?")) return {}; // Return empty object if no query parameters

    const params = new URLSearchParams(url.split("?")[1]); // Extract query string
    const paramObject = {};

    params.forEach((value, key) => {
        paramObject[key] = value;
    });

    return paramObject;
};
