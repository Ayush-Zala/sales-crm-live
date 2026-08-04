import { useState, useRef } from "react";

import CloseRounded from "@mui/icons-material/CloseRounded";
import IconButton from "@mui/material/IconButton";
import InputAdornment from "@mui/material/InputAdornment";
import TextField from "@mui/material/TextField";

import useUpdateSearchParam from "@/hooks/use-update-search-params";

const ZoomMeetingLogDataSearch = ({ search }) => {
    const [inputValue, setInputValue] = useState(search || ""); // For the search input
    const timeoutRef = useRef(null); // To keep track of the timeout

    const handleChange = (event) => {
        const value = event.target.value;
        setInputValue(value);

        // Clear the previous timeout if it exists
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // Set a new timeout to update the search parameter after 1 second
        timeoutRef.current = setTimeout(() => {
            useUpdateSearchParam(
                value.length > 0 || value === ""
                    ? { search: value, page: 1, per_page: 50 }
                    : { search: "", page: 1, per_page: 50 },
                "/zoom-meetings"
            );
        }, 1000); // 1000ms delay
    };

    const handleClear = () => {
        setInputValue(""); // Clear input value
        useUpdateSearchParam(
            { search: "", page: 1, per_page: 50 },
            "/zoom-meetings"
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

export default ZoomMeetingLogDataSearch;
