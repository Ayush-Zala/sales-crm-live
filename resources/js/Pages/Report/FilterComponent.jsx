import { Stack, Tooltip } from "@mui/material";
import Button from "@mui/material/Button";
import { useState } from "react";

import useUpdateSearchParam from "@/hooks/use-update-search-params";

const getFilterOptions = () => {
    const options = [
        { value: "all", label: "All", tooltipText: "All accounts" },
        {
            value: "active",
            label: "Active",
            tooltipText: "Active accounts",
        },
        {
            value: "inactive",
            label: "Inactive",
            tooltipText: "inactive accounts",
        },
    ];

    return options;
};

const FilterComponent = ({ search }) => {
    const [value, setValue] = useState(search || "active");
    const options = getFilterOptions();

    const handleChange = (selectedValue) => {
        setValue(selectedValue);
        useUpdateSearchParam(
            selectedValue
                ? { companyAssignStatus: selectedValue, page: 1, per_page: 50 }
                : { companyAssignStatus: null },
            "/report"
        );
    };

    return (
        <Stack direction="row" gap={0.5} flexWrap="wrap">
            {options.map(({ value: optionValue, label, tooltipText }) => (
                <Tooltip title={tooltipText} placement="top" key={optionValue}>
                    <Button
                        variant={
                            value === optionValue ? "contained" : "outlined"
                        }
                        onClick={() => handleChange(optionValue)}
                    >
                        {label}
                    </Button>
                </Tooltip>
            ))}
        </Stack>
    );
};

export default FilterComponent;
