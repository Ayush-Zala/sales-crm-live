import Avatar from "@mui/material/Avatar";
import ListItem from "@mui/material/ListItem";
import ListItemAvatar from "@mui/material/ListItemAvatar";
import ListItemText from "@mui/material/ListItemText";

const ZoomUserNameComponent = ({ name, email }) => {
    return (
        <ListItem disablePadding disableGutters dense>
            <ListItemAvatar>
                <Avatar sx={{ height: 30, width: 30 }}>
                    {name.charAt(0).toUpperCase()}
                </Avatar>
            </ListItemAvatar>
            <ListItemText
                primary={name}
                primaryTypographyProps={{
                    fontSize: "inherit",
                    underline: "none",
                }}
                secondary={email}
                secondaryTypographyProps={{
                    fontSize: "small",
                    color: "text.secondary",
                }}
            />
        </ListItem>
    );
};

export default ZoomUserNameComponent;
