import { Link } from "@inertiajs/react";

import MuiLink from "@mui/material/Link";
import Avatar from "@mui/material/Avatar";
import Badge from "@mui/material/Badge";
import ListItem from "@mui/material/ListItem";
import ListItemAvatar from "@mui/material/ListItemAvatar";
import ListItemText from "@mui/material/ListItemText";

const UserLinkComponent = ({ userdata }) => {
    return (
        <ListItem disablePadding disableGutters dense>
            <ListItemAvatar>
                <Badge
                    anchorOrigin={{ vertical: "bottom", horizontal: "right" }}
                    color={userdata.isOnline ? "success" : "error"}
                    overlap="circular"
                    variant="dot"
                >
                    <Avatar>{userdata.name.charAt(0).toUpperCase()}</Avatar>
                </Badge>
            </ListItemAvatar>
            <ListItemText
                primary={userdata.name}
                primaryTypographyProps={{
                    fontSize: "inherit",
                    component: (props) => (
                        <MuiLink {...props} component={Link} />
                    ),
                    href: route("user.report", userdata.id),
                    underline: "none",
                }}
                secondary={userdata.email}
                secondaryTypographyProps={{
                    fontSize: "small",
                    color: "text.secondary",
                }}
            />
        </ListItem>
    );
};

export default UserLinkComponent;
