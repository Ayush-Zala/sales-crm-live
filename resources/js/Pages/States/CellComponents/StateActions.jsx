import { Link } from "@inertiajs/react";
import { LocationCityRounded } from "@mui/icons-material";
import { IconButton, Tooltip } from "@mui/material";

const StateActions = ({ state }) => {
    return (
        <Tooltip title={`View cities of ${state.name}`} placement="left">
            <IconButton
                aria-label="edit"
                size="small"
                LinkComponent={Link}
                href={route("cities.index", { state_id: state.id })}
            >
                <LocationCityRounded />
            </IconButton>
        </Tooltip>
    );
};

export default StateActions;
