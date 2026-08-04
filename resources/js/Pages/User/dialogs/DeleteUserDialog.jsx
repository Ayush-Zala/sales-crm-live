import { DialogTransition } from "@/Components/common/dialog-transition";
import { LoadingButton } from "@mui/lab";
import {
    Dialog,
    DialogActions,
    DialogContent,
    DialogContentText,
    DialogTitle,
    useMediaQuery,
    useTheme,
} from "@mui/material";
import React from "react";

const DeleteUserDialog = ({ user, open, handleClose }) => {
    const theme = useTheme();
    const fullScreen = useMediaQuery(theme.breakpoints.down("md"));

    const handleDelete = () => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("user.deleteuser"), {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ id: user.id }),
        }).then((response) => {
            if (response.ok) {
                window.location.reload();
            }
        });
    };

    return (
        <Dialog
            fullWidth
            open={open}
            maxWidth="sm"
            scroll="paper"
            keepMounted={false}
            fullScreen={fullScreen}
            TransitionComponent={DialogTransition}
            PaperProps={{ elevation: 0, variant: "outlined" }}
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
        >
            <DialogTitle>Delete User</DialogTitle>
            <DialogContent dividers>
                <DialogContentText>
                    Are you sure you want to delete {user.name}?
                </DialogContentText>
            </DialogContent>
            <DialogActions>
                <LoadingButton onClick={handleClose} variant="outlined">
                    Cancel
                </LoadingButton>
                <LoadingButton
                    onClick={handleDelete}
                    variant="contained"
                    color="error"
                >
                    Delete
                </LoadingButton>
            </DialogActions>
        </Dialog>
    );
};

export default DeleteUserDialog;
