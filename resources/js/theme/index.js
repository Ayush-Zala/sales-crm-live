import { createTheme } from "@mui/material/styles";
import { closedMixin, openedMixin } from "./styles";

const theme = createTheme({
    typography: {
        fontFamily: "'Inter Variable', sans-serif",
        fontWeightLight: 400,
        fontWeightRegular: 500,
        fontWeightMedium: 600,
        fontWeightBold: 700,
    },
    components: {
        MuiAppBar: {
            defaultProps: { elevation: 0 },
            styleOverrides: {
                root: ({ theme, ownerState }) => {
                    if (ownerState["id"] === "main-appbar") {
                        return {
                            zIndex: theme.zIndex.drawer + 1,
                            transition: theme.transitions.create(
                                ["width", "margin"],
                                {
                                    easing: theme.transitions.easing.sharp,
                                    duration:
                                        theme.transitions.duration
                                            .leavingScreen,
                                }
                            ),
                            ...(ownerState.open && {
                                marginLeft: 300,
                                width: `calc(100% - 300px)`,
                                transition: theme.transitions.create(
                                    ["width", "margin"],
                                    {
                                        easing: theme.transitions.easing.sharp,
                                        duration:
                                            theme.transitions.duration
                                                .enteringScreen,
                                    }
                                ),
                            }),
                        };
                    }
                },
            },
        },
        MuiDrawer: {
            styleOverrides: {
                root: ({ theme, ownerState }) => {
                    if (ownerState["id"] === "main-siderbar") {
                        return {
                            width: 300,
                            flexShrink: 0,
                            whiteSpace: "nowrap",
                            boxSizing: "border-box",
                            ...(ownerState.open
                                ? {
                                      ...openedMixin(theme),
                                      "& .MuiDrawer-paper": openedMixin(theme),
                                  }
                                : {
                                      ...closedMixin(theme),
                                      "& .MuiDrawer-paper": closedMixin(theme),
                                  }),
                        };
                    }
                },
            },
        },
        MuiButton: {
            defaultProps: {
                size: "small",
                disableElevation: true,
                color: "primary",
            },
            styleOverrides: {
                root: ({ theme }) => ({
                    borderRadius: "50px",
                    paddingLeft: "20px",
                    paddingRight: "20px",
                    [theme.breakpoints.down("sm")]: { width: "100%" },
                }),
            },
        },
        MuiPaper: { defaultProps: { variant: "outlined", elevation: 0 } },
        MuiMenu: {
            defaultProps: {
                keepMounted: false,
                PaperProps: {
                    // elevation: 0,
                    sx: {
                        mt: 1.5,
                        width: 200,
                        maxWidth: 250,
                        overflow: "visible",
                        filter: "drop-shadow(0px 2px 8px rgba(0,0,0,0.20))",
                        "& .MuiAvatar-root": {
                            mr: 1,
                            ml: -0.5,
                            width: 32,
                            height: 32,
                        },
                        "&:before": {
                            top: 0,
                            right: 14,
                            width: 10,
                            zIndex: 0,
                            height: 10,
                            content: '""',
                            display: "block",
                            position: "absolute",
                            bgcolor: "background.paper",
                            transform: "translateY(-50%) rotate(45deg)",
                        },
                    },
                },
                transformOrigin: { horizontal: "right", vertical: "top" },
                anchorOrigin: { horizontal: "right", vertical: "bottom" },
            },
        },
        MuiMenuItem: { defaultProps: { dense: true, divider: true } },
        MuiTable: { defaultProps: { size: "small" } },
        MuiTableCell: {
            defaultProps: { size: "small", sx: { whiteSpace: "nowrap" } },
        },
        MuiPopover: {
            defaultProps: {
                anchorOrigin: { vertical: "bottom", horizontal: "right" },
                transformOrigin: { vertical: "top", horizontal: "right" },
            },
        },
        MuiTextField: {
            defaultProps: {
                fullWidth: true,
                size: "small",
                variant: "outlined",
            },
        },
    },
});

export default theme;
