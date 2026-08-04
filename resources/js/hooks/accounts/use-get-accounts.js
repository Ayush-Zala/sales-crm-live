import { useForm } from "@inertiajs/react";
import { useQuery } from "@tanstack/react-query";

export const useGetAccounts = ({ page, perPage, search }) => {
    return useQuery({
        queryKey: ["accounts", { page, perPage, search }],
        queryFn: async () => {
            return await fetch("/account", { search, page, perPage })
                .then((res) => res.json())
                .then((data) => data)
                .catch((error) => {
                    throw new Error("Error fetching accounts");
                });
        },
    });
};
