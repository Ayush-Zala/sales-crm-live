import { useState } from "react";
import TextField from "@mui/material/TextField";
import Autocomplete from "@mui/material/Autocomplete";

import { timeZone } from "@/Constant/constants";
import useUpdateSearchParam from "@/hooks/use-update-search-params";

const TimeZoneFilterComponent = ({ search }) => {
    const initialValue = timeZone.find((tz) => tz.id === search) || null;

    const [value, setValue] = useState(initialValue);
    const [inputValue, setInputValue] = useState("");

    const handleChange = (value) => {
        setValue(value);

        useUpdateSearchParam(
            value
                ? { timezone: value.id, page: 1, per_page: 50 }
                : { timezone: null }
        );
    };

    return (
        <Autocomplete
            getOptionLabel={(option) => option.label}
            isOptionEqualToValue={(option, value) => option.id === value.id}
            value={value}
            onChange={(event, newValue) => {
                handleChange(newValue);
            }}
            inputValue={inputValue}
            onInputChange={(event, newInputValue) => {
                setInputValue(newInputValue);
            }}
            id="timezones"
            options={timeZone}
            renderInput={(params) => (
                <TextField {...params} label="Filter by timezone" />
            )}
        />
    );
};

export default TimeZoneFilterComponent;
