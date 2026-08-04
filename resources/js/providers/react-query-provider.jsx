import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ReactQueryDevtools } from "@tanstack/react-query-devtools";
import PropTypes from "prop-types";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
            refetchOnWindowFocus: false,
            cacheTime: 0,
        },
    },
});

export const ReactQueryProvider = ({ children }) => {
    return (
        <QueryClientProvider client={queryClient}>
            {children}
            <ReactQueryDevtools
                buttonPosition="bottom-right"
                initialIsOpen={false}
            />
        </QueryClientProvider>
    );
};

ReactQueryProvider.propTypes = { children: PropTypes.node.isRequired };
