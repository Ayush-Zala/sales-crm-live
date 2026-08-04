import { Stack, Tooltip } from "@mui/material";
import Button from "@mui/material/Button";
import { useState } from "react";

import useUpdateSearchParam from "@/hooks/use-update-search-params";

const capitalizeAndFormat = (value) => {
    return value
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};

const LeadFilterComponent = () => {
    const [value, setValue] = useState(null);

    const options = [
        { value: "all", label: "All", tooltipText: "All Leads" },
        {
            value: "new_lead",
            label: "NL",
            tooltipText: "New leads",
        },
        {
            value: "dialed_lead",
            label: "DL",
            tooltipText: "Dialed leads",
        },
    ];

    const handleChange = (value) => {
        setValue(value);

        useUpdateSearchParam(
            value ? { filter: value, page: 1, per_page: 50 } : { filter: null },
            "/lead"
        );
    };

    return (
        <Stack direction="row" gap={0.5} flexWrap="wrap">
            {options.map((option, index) => (
                <Tooltip
                    title={capitalizeAndFormat(option.value)}
                    placement="top"
                    key={index}
                >
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
