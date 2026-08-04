import { Stack, Tooltip } from "@mui/material";
import Button from "@mui/material/Button";
import { useState } from "react";

import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { usePage } from "@inertiajs/react";

const getFilterOptions = (canSeeAllFilters, roles) => {
    const commonOptions = [
        { value: "all", label: "All", tooltipText: "All accounts" },
        { value: "new_account", label: "NA", tooltipText: "New accounts" },
        {
            value: "dialed_account",
            label: "DA",
            tooltipText: "Dialed accounts",
        },
    ];

    const extraOptions = [
        {
            value: "assign_account",
            label: "AA",
            tooltipText: "Assigned accounts",
        },
        {
            value: "unassign_account",
            label: "UA",
            tooltipText: "Unassigned accounts",
        },
    ];

    if (canSeeAllFilters) {
        if (
            roles.includes("Data Entry Manager") ||
            roles.includes("Admin") ||
            roles.includes("Data Entry")
        ) {
            return [
                {
                    value: "noc",
                    label: "No Contacts",
                    tooltipText: "Accounts with no contacts",
                },
                ...commonOptions,
                ...extraOptions,
            ];
        }
        return [...commonOptions, ...extraOptions];
    }
    return commonOptions;
};

const FilterComponent = ({ canSeeAllFilters, search }) => {
    const [value, setValue] = useState(search || "all");
    const {
        props: { auth },
    } = usePage();
    const { roles } = auth;
    const options = getFilterOptions(canSeeAllFilters, roles);

    const handleChange = (selectedValue) => {
        setValue(selectedValue);
        useUpdateSearchParam(
            selectedValue
                ? { filter: selectedValue, page: 1, per_page: 50 }
                : { filter: null }
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
