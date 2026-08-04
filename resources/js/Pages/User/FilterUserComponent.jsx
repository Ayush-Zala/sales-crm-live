import Button from "@mui/material/Button";
import { useState } from "react";
import { Stack, Tooltip } from "@mui/material";

import useUpdateSearchParam from "@/hooks/use-update-search-params";

const capitalizeAndFormat = (value) => {
    return value
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};

const options = [
    { value: "all", label: "All" },
    { value: "active", label: "Active" },
    { value: "inactive", label: "Inactive" },
    { value: "online_users_only", label: "Online Users Only" },
];

const FilterUserComponent = ({ search }) => {
    const [value, setValue] = useState(search || "active");

    const handleChange = (value) => {
        setValue(value);

        useUpdateSearchParam(
            value ? { filter: value, page: 1, per_page: 50 } : { filter: null },
            "/user"
        );
    };

    return (
        <Stack
            direction="row"
            gap={0.5}
            flexWrap="wrap"
            justifyContent="flex-end"
        >
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

export default FilterUserComponent;
