import { RefreshRounded } from "@mui/icons-material";
import { IconButton, Stack, Typography } from "@mui/material";
import PropTypes from "prop-types";

export const PageTitle = ({ loading, title, refresh }) => {
    return (
        <Stack
            direction="row"
            spacing={0.5}
            alignItems="center"
            justifyContent="flex-start"
        >
            <Typography
                noWrap
                component="h1"
                variant="h5"
                fontWeight="700"
                lineHeight={1}
            >
                {title}
            </Typography>
            {refresh ? (
                <IconButton disabled={loading} onClick={refresh}>
                    <RefreshRounded
                        fontSize="small"
                        aria-disabled={loading ? "true" : "false"}
                        data-animation={loading ? "spin" : "none"}
                    />
                </IconButton>
            ) : null}
        </Stack>
    );
};

PageTitle.propTypes = {
    loading: PropTypes.bool,
    title: PropTypes.string.isRequired,
    refresh: PropTypes.func,
};
