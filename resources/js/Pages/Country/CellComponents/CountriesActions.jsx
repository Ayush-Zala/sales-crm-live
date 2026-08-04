import { Link } from "@inertiajs/react";
import { LocationCityRounded } from "@mui/icons-material";
import { IconButton, Tooltip } from "@mui/material";

const CountriesActions = ({ country }) => {
    return (
        <Tooltip title={`View states of ${country.name}`} placement="left">
            <IconButton
                aria-label="edit"
                size="small"
                LinkComponent={Link}
                href={route("states.index", {
                    country_id: country.id,
                })}
            >
                <LocationCityRounded />
            </IconButton>
        </Tooltip>
    );
};

export default CountriesActions;
