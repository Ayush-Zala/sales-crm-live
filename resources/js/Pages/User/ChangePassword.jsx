import { useForm } from "@inertiajs/react";
import { LoadingButton } from "@mui/lab";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    TextField,
} from "@mui/material";
import toast from "react-hot-toast";

export const ChangePassword = ({ open, handleClose, email }) => {
    const defaultValues = {
        email: email,
        password: "",
        password_confirmation: "",
    };

    const {
        data,
        patch,
        reset,
        setData,
        processing,
        wasSuccessful,
        recentlySuccessful,
    } = useForm(defaultValues);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (data.password !== data.password_confirmation) {
            toast.error("Passwords do not match");
            return;
        }

        if (data.password.length < 7 || data.password_confirmation.length < 7) {
            toast.error("Password must be at least 8 characters long");
            return;
        } else {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");

            fetch(route("user.updateuserpassword"), {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(data),
            })
                .then((response) => response.json())
                .then((res) => {
                    toast.success(res.message);
                    handleClose();
                    reset();
                })
                .catch((error) => {
                    console.error("Error:", error);
                });
        }
    };

    return (
        <Dialog
            aria-labelledby="customized-dialog-title"
            open={open}
            onClose={handleClose}
            maxWidth="md"
        >
            <DialogTitle id="customized-dialog-title">
                Change Password
            </DialogTitle>
            <DialogContent dividers>
                <Grid item container xs={12} spacing={2}>
                    <Grid item xs={12}>
                        <TextField
                            label="New Password"
                            type="password"
                            fullWidth
                            variant="outlined"
                            name="password"
                            onChange={(e) =>
                                setData("password", e.target.value)
                            }
                            value={data.password}
                        />
                    </Grid>
                    <Grid item xs={12}>
                        <TextField
                            label="Confirm New Password"
                            type="password"
                            fullWidth
                            variant="outlined"
                            name="password_confirmation"
                            onChange={(e) =>
                                setData("password_confirmation", e.target.value)
                            }
                            value={data.password_confirmation}
                        />
                    </Grid>
                </Grid>
            </DialogContent>
            <DialogActions>
                <LoadingButton
                    onClick={handleSubmit}
                    variant="contained"
                    color="primary"
                    loading={processing}
                >
                    Submit
                </LoadingButton>
                <Button onClick={handleClose} variant="outlined">
                    Cancel
                </Button>
            </DialogActions>
        </Dialog>
    );
};
