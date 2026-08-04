import { Button, Grid, Stack, Typography } from "@mui/material";
import PropTypes from "prop-types";

import { PageTitle } from "./page-title";
import { Link } from "@inertiajs/react";

export const MainContentTemplate = ({
    loading = false,
    title = "",
    subtitle = "",
    refresh,
    children = null,
    button,
    href,
    onClick,
    secondButton,
    secondButtonHref,
    onSecondButtonClick,
    buttonComponent,
    tempButton,
}) => {
    return (
        <Grid container columns={12} spacing={2}>
            <Grid container item columns={12} spacing={2} alignItems="center">
                <Grid item xs={12} sm={6}>
                    <Stack direction="column" spacing={0}>
                        <PageTitle
                            loading={loading}
                            title={title}
                            refresh={refresh}
                        />
                        <Typography
                            color="text.secondary"
                            component="p"
                            variant="subtitle2"
                        >
                            {subtitle}
                        </Typography>
                    </Stack>
                </Grid>

                <Grid item xs={12} sm={6}>
                    <Stack
                        direction={{ xs: "column", sm: "row" }}
                        alignItems="center"
                        justifyContent="flex-end"
                        spacing={0.5}
                    >
                        {secondButton && onSecondButtonClick && (
                            <Button
                                variant="contained"
                                onClick={onSecondButtonClick}
                            >
                                {secondButton}
                            </Button>
                        )}
                        {secondButton && secondButtonHref && (
                            <Button
                                variant="contained"
                                LinkComponent={Link}
                                href={secondButtonHref}
                            >
                                {secondButton}
                            </Button>
                        )}
                        {buttonComponent && buttonComponent}
                        {button && onClick && (
                            <Button variant="contained" onClick={onClick}>
                                {button}
                            </Button>
                        )}
                        {button && href && (
                            <Button
                                variant="contained"
                                LinkComponent={Link}
                                href={href}
                            >
                                {button}
                            </Button>
                        )}
                        {tempButton}
                    </Stack>
                </Grid>
            </Grid>
            <Grid container item columns={12} spacing={2}>
                {children}
            </Grid>
        </Grid>
    );
};

MainContentTemplate.propTypes = {
    loading: PropTypes.bool,
    children: PropTypes.node.isRequired,
    title: PropTypes.string.isRequired,
    subtitle: PropTypes.string.isRequired,
    refresh: PropTypes.func,
};
