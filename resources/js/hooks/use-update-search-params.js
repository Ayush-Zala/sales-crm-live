import { router } from "@inertiajs/react"; // Adjust the import path as necessary

const useUpdateSearchParam = (paramsToUpdate, baseUrl = "/account", options = {}) => {
    const params = new URLSearchParams(window.location.search);

    // Update or remove keys based on the provided paramsToUpdate object
    Object.entries(paramsToUpdate).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== "") {
            params.set(key, value);
        } else {
            params.delete(key);
        }
    });
    // Maintain the current query parameters and navigate using Inertia
    router.get(baseUrl, Object.fromEntries(params.entries()), {
        preserveState: true,
        preserveScroll: true,
        ...options,
    });
};

export default useUpdateSearchParam;
