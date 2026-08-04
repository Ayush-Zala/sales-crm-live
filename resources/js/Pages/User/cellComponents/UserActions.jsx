import { Link, router, usePage } from "@inertiajs/react";
import {
    DomainDisabledRounded,
    EditRounded,
    VideoCameraFrontRounded,
    VideocamRounded,
} from "@mui/icons-material";
import { IconButton, Stack, Tooltip } from "@mui/material";
import { useState } from "react";
import { Fragment } from "react";
import { ChangePassword } from "../ChangePassword";
import HttpsIcon from "@mui/icons-material/Https";
import { hasRole } from "@/utils/AccessManager";
import { EditZoomCredentialsDialog } from "../dialogs/EditZoomCredentailsDialog";
import { confirm } from "material-ui-confirm";
import toast from "react-hot-toast";

const UserActions = ({ user }) => {
    const [open, setOpen] = useState(false);
    const [openZoomCredentials, setOpenZoomCredentials] = useState(false);
    const [zoomCredentialsData, setZoomCredentialsData] = useState([]);

    const auth = usePage().props.auth;
    const isAdmin = hasRole(auth, ["Admin"]);

    const handleOpen = () => setOpen(true);
    const handleClose = () => setOpen(false);

    const handleOpenZoomCredentials = () => {
        // fetch zoom credentials here
        fetch(route("zoom.getusercredentials", { user_id: user.id }))
            .then((res) => res.json())
            .then((data) => {
                setZoomCredentialsData(data);
                setOpenZoomCredentials(true);
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    };
    const handleCloseZoomCredentials = () => setOpenZoomCredentials(false);

    return (
        <>
            <Stack
                direction="row"
                alignItems="center"
                justifyContent="flex-start"
            >
                {isAdmin && <EditUser user={user} />}

                {isAdmin && (
                    <EditZoomCredentials
                        user={user}
                        handleOpen={handleOpenZoomCredentials}
                    />
                )}

                <UnassignAllAccounts user={user} />

                <Tooltip
                    title="Change Password"
                    sx={{ ":hover": { color: "warning.main" } }}
                >
                    <IconButton size="small" onClick={handleOpen}>
                        <HttpsIcon fontSize="small" />
                    </IconButton>
                </Tooltip>

                <ChangePassword
                    open={open}
                    handleClose={handleClose}
                    email={user.email}
                />

                <EditZoomCredentialsDialog
                    open={openZoomCredentials}
                    handleClose={handleCloseZoomCredentials}
                    zoomData={zoomCredentialsData}
                    userId={user.id}
                    name={user.name}
                />
            </Stack>
        </>
    );
};

export default UserActions;

const EditUser = ({ user }) => {
    return (
        <Fragment>
            <Tooltip
                title={`Edit ${user.name.toUpperCase()}'s details`}
                placement="left"
                sx={{ ":hover": { color: "primary.main" } }}
                LinkComponent={Link}
                href={route("user.edit", { id: user.id })}
            >
                <IconButton size="small">
                    <EditRounded fontSize="small" />
                </IconButton>
            </Tooltip>
        </Fragment>
    );
};

const EditZoomCredentials = ({ user, handleOpen }) => {
    return (
        <Tooltip
            title={`Edit ${user.name.toUpperCase()}'s Zoom credentials`}
            sx={{ ":hover": { color: "primary.main" } }}
        >
            <IconButton size="small" onClick={handleOpen}>
                <VideoCameraFrontRounded fontSize="small" />
            </IconButton>
        </Tooltip>
    );
};

const UnassignAllAccounts = ({ user }) => {
    const handleClick = () => {
        confirm({
            title: `Unassign all Accounts from ${user.name.toUpperCase()}`,
            description: "Are you sure you want to Unassign all accounts?",
            confirmationText: "Yes",
            cancellationText: "No",
            confirmationButtonProps: { color: "error" },
        }).then(() => {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");

            const url = router.page.url;

            fetch(route("user.unassignallcompaniesofuser"), {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    user_id: user.id,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.error) {
                        toast.error(data.error);
                        return;
                    }
                    toast.success(data.message);
                    router.get(
                        url,
                        {},
                        { preserveScroll: true, preserveState: true }
                    );
                })
                .catch((error) => {
                    console.error(error);
                    // toast.error(error.message);
                });
        });
    };

    return (
        <>
            <Tooltip
                title={`Unassign all accounts from ${user.name.toUpperCase()}`}
                sx={{ ":hover": { color: "error.main" } }}
            >
                <IconButton size="small" onClick={handleClick}>
                    <DomainDisabledRounded fontSize="small" />
                </IconButton>
            </Tooltip>
        </>
    );
};
