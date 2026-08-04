import {
    CloseRounded,
    MenuRounded,
    NotificationsRounded,
} from "@mui/icons-material";
import {
    AppBar,
    Badge,
    IconButton,
    Stack,
    Toolbar,
    Typography,
} from "@mui/material";

import { useLayoutStore } from "@/store/layout-store";
import { AvatarMenuWithProfile } from "./avatar-menu-with-profile";
import TimeZoneList from "./time-zone-list";
import { Fragment } from "react";
import NotificationLayout from "./notification-layout";
import { useState } from "react";
import { useNotificationStore } from "@/store/notification-store";
import { useEffect } from "react";
import { useCallback } from "react";

export const MainHeader = ({ user }) => {
    const open = useLayoutStore((state) => state.open);
    const toggle = useLayoutStore((state) => state.toggle);

    const [openNotifications, setOpenNotifications] = useState(false);

    const {
        notifications: notificationData,
        setNotifications: setNotificationData,
        notificationCount,
        setNotificationCount,
    } = useNotificationStore();

    const getNotificationCount = useCallback(async () => {
        if (notificationCount.length > 0) return;
        try {
            fetch(route("notification.getCount"))
                .then((response) => response.json())
                .then((data) => {
                    setNotificationCount(data);
                });
        } catch (error) {
            console.error("Failed to fetch notification count:", error);
        }
    }, [notificationCount.length]);

    useEffect(() => {
        getNotificationCount();
    }, []);

    const handleOpen = () => {
        fetch(route("notification.getNotifications")).then((response) => {
            response.json().then((data) => {
                setNotificationData(data);
            });
        });

        setOpenNotifications(true);
    };

    const handleNotificationToggle = () =>
        setOpenNotifications(!openNotifications);

    return (
        <Fragment>
            <AppBar position="fixed" open={open} id="main-appbar">
                <Toolbar>
                    <IconButton
                        color="inherit"
                        aria-label="open drawer"
                        onClick={toggle}
                        edge="start"
                        sx={{ marginRight: 3 }}
                    >
                        {open ? <CloseRounded /> : <MenuRounded />}
                    </IconButton>

                    <Stack
                        direction="row"
                        spacing={2}
                        alignItems="center"
                        flexGrow={1}
                    >
                        {!open && (
                            <Typography variant="h6" component="div">
                                {import.meta.env.VITE_APP_NAME}
                            </Typography>
                        )}
                    </Stack>
                    <Stack
                        direction="row"
                        spacing={2}
                        alignItems="center"
                        flexGrow={0}
                    >
                        <TimeZoneList />
                        <IconButton
                            size="large"
                            color="inherit"
                            onClick={handleOpen}
                        >
                            <Badge
                                overlap="circular"
                                color="error"
                                badgeContent={notificationCount}
                                max={999999}
                            >
                                <NotificationsRounded />
                            </Badge>
                        </IconButton>
                        <AvatarMenuWithProfile user={user} />
                    </Stack>
                </Toolbar>
            </AppBar>
            <NotificationLayout
                open={openNotifications}
                toggle={handleNotificationToggle}
                notifications={notificationData}
            />
        </Fragment>
    );
};
