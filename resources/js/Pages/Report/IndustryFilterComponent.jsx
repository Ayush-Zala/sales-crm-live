import useUpdateSearchParam from "@/hooks/use-update-search-params";

import { Autocomplete, TextField } from "@mui/material";
import React, { useState } from "react";

const IndustryFilterComponent = ({ industries = [], search }) => {
    const initialValue = industries.find((industry) => industry.name === search)
        ? {
              id: industries.findIndex((industry) => industry.name === search),
              label: search,
          }
        : null;

    const [value, setValue] = useState(initialValue);
    const [inputValue, setInputValue] = useState("");

    const modifiedIndustries = [
        { id: -1, label: "No Industry" }, // Adding a default option
        ...(industries?.map((industry, index) => ({
            id: index,
            label: industry.name, // Ensuring label exists
        })) || []),
    ];

    const handleChange = (value) => {
        setValue(value);

        useUpdateSearchParam(
            value
                ? {
                      industry: encodeURIComponent(value.label),
                      page: 1,
                      per_page: 50,
                  }
                : { industry: null },
            "/report"
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
            id="industry"
            options={modifiedIndustries}
            renderInput={(params) => (
                <TextField {...params} label="Filter by industry" />
            )}
        />
    );
};

export default IndustryFilterComponent;
