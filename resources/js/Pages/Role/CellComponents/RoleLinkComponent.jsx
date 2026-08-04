import { Link } from "@inertiajs/react";
import MuiLink from "@mui/material/Link";

const RoleLinkComponent = ({ role }) => {
    return (
        <MuiLink
            underline="none"
            component={Link}
            href={route("role.edit", { id: role.id })}
        >
            {role.name}
        </MuiLink>
    );
};

export default RoleLinkComponent;
