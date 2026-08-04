import { hasRole } from "@/utils/AccessManager";
import { usePage } from "@inertiajs/react";
import MuiLink from "@mui/material/Link";

const LeadsNameLinkComponent = ({ lead }) => {
    const { auth } = usePage().props;
    const isAdminOrBDM = hasRole(auth, [
        "Admin",
        "Business Development Manager",
    ]);

    const href = isAdminOrBDM
        ? route("retention.edit", lead.id)
        : route("retention.view", lead.id);

    return (
        <MuiLink underline="none" href={href} target="_blank">
            {lead.name}
        </MuiLink>
    );
};

export default LeadsNameLinkComponent;
