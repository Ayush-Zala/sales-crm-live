import Autocomplete from "@mui/material/Autocomplete";
import TextField from "@mui/material/TextField";

import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { useState } from "react";

const LeadDispositionFilterSearch = ({ dispositions }) => {
    const [value, setValue] = useState(null);
    const [inputValue, setInputValue] = useState("");

    const handleChange = (value) => {
        setValue(value);

        useUpdateSearchParam(
            value
                ? { disposition: value.id, page: 1, per_page: 50 }
                : { disposition: null },
            "/lead"
        );
    };

    return (
        <Autocomplete
            getOptionLabel={(option) => option.name}
            isOptionEqualToValue={(option, value) => option.id === value.id}
            value={value}
            onChange={(event, newValue) => {
                handleChange(newValue);
            }}
            inputValue={inputValue}
            onInputChange={(event, newInputValue) => {
                setInputValue(newInputValue);
            }}
            id="dispositions"
            options={dispositions}
            renderInput={(params) => (
                <TextField {...params} label="Filter by disposition" />
            )}
        />
    );
};

export default LeadDispositionFilterSearch;
