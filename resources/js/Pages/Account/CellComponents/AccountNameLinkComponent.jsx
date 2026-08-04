import { Link } from "@inertiajs/react";
import MuiLink from "@mui/material/Link";

const AccountNameLinkComponent = ({ account }) => (
    <MuiLink
        component={Link}
        underline="none"
        href={route("account.view", account.id)}
        color={account.blacklisted ? "error.main" : "primary.main"}
        sx={{
            textDecoration: account.blacklisted ? "line-through" : "none",
        }}
    >
        {account.name}
    </MuiLink>
);

export default AccountNameLinkComponent;
