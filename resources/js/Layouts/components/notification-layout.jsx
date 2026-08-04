import { CloseRounded, DoneAllRounded } from "@mui/icons-material";
import {
    Avatar,
    Box,
    Drawer,
    Grid,
    IconButton,
    List,
    ListItem,
    ListItemAvatar,
    ListItemText,
    Stack,
    Tooltip,
    Typography,
    styled,
} from "@mui/material";
import Lottie from "lottie-react";
import { Fragment } from "react";

import EmptyNotification from "@/assets/lottie/empty-notification.json";
import { useNotificationStore } from "@/store/notification-store";

const NotificationLayout = ({ open, toggle }) => {
    return (
        <Drawer open={open} anchor="right" onClose={toggle}>
            <Box sx={{ width: { xs: 250, sm: 300, md: 400 }, mt: 10 }}>
                <NotificationList onClose={toggle} />
            </Box>
        </Drawer>
    );
};

export default NotificationLayout;

const NotificationList = ({ onClose }) => {
    const {
        notifications,
        setNotifications: setData,
        setNotificationCount,
    } = useNotificationStore();

    const colors = [
        "slateblue",
        "lightcoral",
        "lightseagreen",
        "tomato",
        "mediumseagreen",
        "orange",
        "steelblue",
    ];
    const handleReadAll = () => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("notification.updateNotification"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                notifications: notifications.map(
                    (notification) => notification.id
                ),
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                setData(
                    notifications.filter(
                        (notification) =>
                            !data.notifications.some(
                                (updated) => updated.id === notification.id
                            )
                    )
                );
                setNotificationCount(0);
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    };

    const handleRead = (id) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("notification.updateNotification"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                notifications: [id],
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                setData(
                    notifications.filter(
                        (notification) =>
                            !data.notifications.some(
                                (updated) => updated.id === notification.id
                            )
                    )
                );
                setNotificationCount(notifications.length - 1);
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    };

    return (
        <List sx={{ width: "100%", px: 1 }}>
            <Grid>
                <Typography variant="button" sx={{ ml: 2, fontWeight: 700 }}>
                    Notifications
                </Typography>
                {notifications && notifications.length > 0 ? (
                    <Fragment>
                        <Tooltip title="Close">
                            <IconButton
                                sx={{ float: "right", right: 0 }}
                                onClick={onClose}
                            >
                                <CloseRounded style={{ color: "red" }} />
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Mark all as read">
                            <IconButton
                                sx={{ float: "right" }}
                                onClick={handleReadAll}
                            >
                                <DoneAllRounded style={{ color: "GrayText" }} />
                            </IconButton>
                        </Tooltip>
                    </Fragment>
                ) : (
                    ""
                )}
            </Grid>
            {notifications && notifications.length > 0 ? (
                notifications.map((notification, index) => (
                    <Fragment key={notification.id}>
                        <Grid
                            sx={{
                                my: 1,
                                display: "flex",

                                width: "100%",
                                backgroundColor: "#FFFFFF",

                                borderColor: "#E2E2E2",
                                ":hover": {
                                    backgroundColor: "#edeceb",
                                    boxShadow: "2",
                                },
                            }}
                        >
                            <ListItem
                                sx={{ pr: 0 }}
                                selected={notification.isRead}
                            >
                                <ListItemAvatar>
                                    <Avatar
                                        alt={`${notification.name}'s Photo`}
                                        sx={{
                                            backgroundColor:
                                                colors[index % colors.length],
                                            boxShadow: "2",
                                        }}
                                    >
                                        {(notification.name &&
                                            notification.name[0]) ||
                                            "N"}
                                    </Avatar>
                                </ListItemAvatar>
                                <ListItemText
                                    primary={
                                        <Typography
                                            component="span"
                                            variant="subtitle2"
                                        >
                                            {notification.title}
                                        </Typography>
                                    }
                                    secondary={
                                        <Fragment>
                                            <Typography
                                                component="span"
                                                variant="subtitle2"
                                                color="primary"
                                            >
                                                {`${notification.name} - `}
                                            </Typography>
                                            {notification.description}
                                        </Fragment>
                                    }
                                />
                                <Tooltip title="Mark as read">
                                    <IconButton
                                        sx={{ float: "right" }}
                                        onClick={() =>
                                            handleRead(notification.id)
                                        }
                                    >
                                        <DoneAllRounded
                                            style={{
                                                color: notification.isRead
                                                    ? "#0095ff"
                                                    : "GrayText",
                                                height: 22,
                                            }}
                                        />
                                    </IconButton>
                                </Tooltip>
                            </ListItem>
                        </Grid>
                        {/* <Divider variant="inset" component="li" /> */}
                    </Fragment>
                ))
            ) : (
                <NotificationEmpty
                    title="You're all caught up!"
                    description="Perhaps, be back later?"
                />
            )}
        </List>
    );
};

const NotificationEmpty = ({ title, description }) => {
    return (
        <Empty>
            <Stack>
                <Lottie
                    loop
                    autoPlay
                    animationData={EmptyNotification}
                    style={{ width: "100%", height: "280px" }}
                />
                <Box textAlign="center">
                    <Typography variant="h6" fontWeight="900">
                        {title}
                    </Typography>
                    <Typography variant="body2">{description}</Typography>
                </Box>
            </Stack>
        </Empty>
    );
};

const Empty = styled(Box)(({ theme }) => ({
    display: "flex",
    flexDirection: "column",
    alignItems: "center",
    justifyContent: "center",
    paddingTop: theme.spacing(6),
    paddingBottom: theme.spacing(6),
}));
