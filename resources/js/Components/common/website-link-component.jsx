import { LinkRounded } from "@mui/icons-material";
import { IconButton, Link, Tooltip } from "@mui/material";

const WebsiteLinkComponent = ({ url, name }) => {
    return (
        <Tooltip title={`${name}'s Website`} placement="right">
            <IconButton
                size="small"
                LinkComponent={Link}
                href={url}
                target="_blank"
            >
                <LinkRounded fontSize="small" color="warning" />
            </IconButton>
        </Tooltip>
    );
};

export default WebsiteLinkComponent;
