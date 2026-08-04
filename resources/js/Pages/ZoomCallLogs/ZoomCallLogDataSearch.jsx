import { useState } from "react";

import CloseRounded from "@mui/icons-material/CloseRounded";
import IconButton from "@mui/material/IconButton";
import InputAdornment from "@mui/material/InputAdornment";
import TextField from "@mui/material/TextField";

import useUpdateSearchParam from "@/hooks/use-update-search-params";

const ZoomCallLogDataSearch = ({ search }) => {
    const [inputValue, setValue] = useState(search || ""); // For the search input
    const [timeoutId, setTimeoutId] = useState(null); // To keep track of the timeout

    const handleChange = (event) => {
        const value = event.target.value;
        setValue(value);

        // Clear the previous timeout if it exists
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        // Set a new timeout to update the search parameter after 1 second
        const newTimeoutId = setTimeout(() => {
            useUpdateSearchParam(
                value.length > 1 || value === ""
                    ? { search: value, page: 1, per_page: 50 }
                    : { search: "", page: 1, per_page: 50 },
                "/zoom-calllogs"
            );
        }, 1000); // 1000ms delay

        // Save the new timeout ID
        setTimeoutId(newTimeoutId);
    };

    const handleClear = () => {
        setValue(""); // Clear input value
        useUpdateSearchParam(
            { search: "", page: 1, per_page: 50 },
            "zoom-calllogs"
        ); // Clear the search param
    };

    return (
        <TextField
            label="Search"
            value={inputValue}
            onChange={handleChange}
            InputProps={{
                endAdornment: inputValue && (
                    <InputAdornment position="end">
                        <IconButton
                            size="small"
                            color="error"
                            onClick={handleClear}
                        >
                            <CloseRounded />
                        </IconButton>
                    </InputAdornment>
                ),
            }}
        />
    );
};

export default ZoomCallLogDataSearch;
