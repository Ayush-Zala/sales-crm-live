import { LinkRounded } from "@mui/icons-material";
import { IconButton, Link, Tooltip } from "@mui/material";

const LinkedInUrl = ({ url, name }) => {
    return (
        <Tooltip title={`${name}'s Linkedin`} placement="right">
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

export default LinkedInUrl;
