import { Grid, styled } from "@mui/material";

// import { drawer_width } from '@/constants/layout-constant'

const drawer_width = 300;

export const openedMixin = (theme) => ({
    width: drawer_width,
    transition: theme.transitions.create("width", {
        easing: theme.transitions.easing.sharp,
        duration: theme.transitions.duration.enteringScreen,
    }),
    overflowX: "hidden",
});

export const closedMixin = (theme) => ({
    transition: theme.transitions.create("width", {
        easing: theme.transitions.easing.sharp,
        duration: theme.transitions.duration.leavingScreen,
    }),
    overflowX: "hidden",
    width: `calc(${theme.spacing(7)} + 1px)`,
    [theme.breakpoints.up("sm")]: { width: `calc(${theme.spacing(8)} + 1px)` },
});

export const DrawerHeader = styled("div")(({ theme }) => ({
    display: "flex",
    alignItems: "center",
    justifyContent: "space-between",
    padding: theme.spacing(0, 2),
    ...theme.mixins.toolbar,
}));

export const Main = styled("main", {
    shouldForwardProp: (prop) => prop !== "open",
})(({ theme, open }) => ({
    flexGrow: 1,
    overflow: "hidden",
    overflowY: "auto",
    height: "100vh",
    padding: theme.spacing(2),
    transition: theme.transitions.create("margin", {
        easing: theme.transitions.easing.sharp,
        duration: theme.transitions.duration.leavingScreen,
    }),
    ...(open && {
        marginLeft: 0,
        transition: theme.transitions.create("margin", {
            easing: theme.transitions.easing.easeOut,
            duration: theme.transitions.duration.enteringScreen,
        }),
    }),
}));

export const LoginScreenImage = styled(Grid)(() => ({
    backgroundSize: "cover",
    backgroundPosition: "center",
    backgroundRepeat: "no-repeat",
    backgroundImage: "url(./assets/images/office-premises.webp)",
}));

export const LoginForm = styled(Grid)(() => ({
    padding: "2.5rem 1.5rem 2.5rem",
    backgroundColor: "#f3f5f7",
    backgroundImage: "url(./assets/images/login-page-background.webp)",
    backgroundRepeat: "no-repeat",
    backgroundSize: "contain",
    backgroundPosition: "bottom right",
}));
