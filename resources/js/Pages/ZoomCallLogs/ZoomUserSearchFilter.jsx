import Autocomplete from "@mui/material/Autocomplete";
import TextField from "@mui/material/TextField";

import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { useState } from "react";

const ZoomUserSearchFilter = ({ users, search }) => {
    const initialValue = users.find((user) => user.id == search) || null;

    const [value, setValue] = useState(initialValue || null);
    const [inputValue, setInputValue] = useState("");

    const handleChange = (value) => {
        setValue(value);

        useUpdateSearchParam(
            value ? { user: value.id, page: 1, per_page: 50 } : { user: null },
            "/zoom-calllogs"
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
            id="users"
            options={users}
            renderInput={(params) => (
                <TextField {...params} label="Filter by user" />
            )}
        />
    );
};

export default ZoomUserSearchFilter;
