import "../css/app.css";
import "react-date-range/dist/styles.css"; // main css file
import "react-date-range/dist/theme/default.css"; // theme css file

import CssBaseline from "@mui/material/CssBaseline";
import { ThemeProvider } from "@mui/material/styles";
import { LocalizationProvider } from "@mui/x-date-pickers";
import { AdapterDateFns } from "@mui/x-date-pickers/AdapterDateFns";

import theme from "./theme";
import { ReactQueryProvider } from "./providers/react-query-provider";

export const Root = ({ children }) => {
    return (
        <ThemeProvider theme={theme}>
            <CssBaseline />
            <LocalizationProvider dateAdapter={AdapterDateFns}>
                <ReactQueryProvider>{children}</ReactQueryProvider>
            </LocalizationProvider>
        </ThemeProvider>
    );
};
