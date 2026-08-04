import Autocomplete from "@mui/material/Autocomplete";
import TextField from "@mui/material/TextField";

import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { useState } from "react";

const LeadSourceFilterSearch = ({ leadSources }) => {
    // Determine initial value from URL if any
    const searchParams = new URLSearchParams(window.location.search);
    const initialSource = searchParams.get("leadSourceFilter");
    const [value, setValue] = useState(initialSource ? { name: initialSource } : null);
    const [inputValue, setInputValue] = useState("");

    const handleChange = (value) => {
        setValue(value);

        useUpdateSearchParam(
            value
                ? { leadSourceFilter: value.name, page: 1, per_page: 50 }
                : { leadSourceFilter: null },
            "/lead"
        );
    };

    return (
        <Autocomplete
            getOptionLabel={(option) => option.name}
            isOptionEqualToValue={(option, value) => option.name === value.name}
            value={value}
            onChange={(event, newValue) => {
                handleChange(newValue);
            }}
            inputValue={inputValue}
            onInputChange={(event, newInputValue) => {
                setInputValue(newInputValue);
            }}
            id="lead-sources"
            options={leadSources}
            renderInput={(params) => (
                <TextField {...params} label="Filter by lead source" size="small" />
            )}
        />
    );
};

export default LeadSourceFilterSearch;
