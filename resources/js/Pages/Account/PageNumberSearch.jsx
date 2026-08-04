import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { TextField } from "@mui/material";
import React from "react";
import { useEffect } from "react";
import { useState } from "react";

const PageNumberSearch = ({ current_page, last_page, url }) => {
    const [inputValue, setValue] = useState(current_page || ""); // For the search input
    const [timeoutId, setTimeoutId] = useState(null); // To keep track of the timeout

    useEffect(() => {
        setValue(current_page);
    }, [current_page]);

    const handleChange = (event) => {
        const value = event.target.value;

        // Clear the previous timeout if it exists
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        setValue(value >= 1 ? value : 1);

        // Set a new timeout to update the search parameter after 1 second
        const newTimeoutId = setTimeout(() => {
            useUpdateSearchParam(
                value >= 1 ? { page: value } : { page: "" },
                url
            );
        }, 1000); // 1000ms delay

        // Save the new timeout ID
        setTimeoutId(newTimeoutId);
    };

    return (
        <TextField
            type="number"
            label="Jump to Page"
            value={inputValue}
            helperText={`Page: ${current_page} of ${last_page}`}
            onChange={(e) => handleChange(e)}
            size="small"
            sx={{ width: "180px" }}
            inputProps={{
                min: 1,
                step: 1,
                max: last_page,
            }}
        />
    );
};

export default PageNumberSearch;
