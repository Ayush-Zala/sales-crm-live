import { Stack, Tooltip } from "@mui/material";
import Button from "@mui/material/Button";
import { useState } from "react";

import useUpdateSearchParam from "@/hooks/use-update-search-params";

const LeadFilterComponent = ({ filterValue }) => {
    const [value, setValue] = useState(filterValue || "all");

    const options = [
        { value: "all", label: "All", tooltipText: "All Retentions" },
        {
            value: "new_retention",
            label: "NR",
            tooltipText: "New Retentions",
        },
        {
            value: "dialed_retention",
            label: "DR",
            tooltipText: "Dialed Retentions",
        },
    ];

    const handleChange = (value) => {
        setValue(value);

        useUpdateSearchParam(
            value ? { filter: value, page: 1, per_page: 50 } : { filter: null },
            "/retention"
        );
    };

    return (
        <Stack direction="row" gap={0.5} flexWrap="wrap">
            {options.map((option, index) => (
                <Tooltip title={option.tooltipText} placement="top" key={index}>
                    <Button
                        variant={
                            value === option.value ? "contained" : "outlined"
                        }
                        value={option.value}
                        onClick={() => handleChange(option.value)}
                    >
                        {option.label}
                    </Button>
                </Tooltip>
            ))}
        </Stack>
    );
};

export default LeadFilterComponent;
