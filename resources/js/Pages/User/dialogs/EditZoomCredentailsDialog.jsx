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
import { useEffect } from "react";
import toast from "react-hot-toast";

export const EditZoomCredentialsDialog = ({
    open,
    handleClose,
    zoomData,
    userId,
    name,
}) => {
    const defaultValues = {
        userId: userId || "",
        zoomAccountId: zoomData?.account_id || "",
        zoomClientKey: zoomData?.client_key || "",
        zoomClientSecret: zoomData?.client_secret || "",
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

    useEffect(() => {
        setData("userId", userId);
        setData("zoomAccountId", zoomData?.account_id);
        setData("zoomClientKey", zoomData?.client_key);
        setData("zoomClientSecret", zoomData?.client_secret);
    }, [zoomData]);

    const handleSubmit = (e) => {
        e.preventDefault();

        patch(route("zoom.updateusercredentials"), {
            onSuccess: (res) => {
                toast.success(res.props.flash.success);
                handleClose();
                reset();
            },
            onError: (error) => {
                console.log(error);
                toast.error("An error occurred. Please try again.");
            },
        });

        // if (data.password !== data.password_confirmation) {
        //     toast.error("Passwords do not match");
        //     return;
        // }

        // if (data.password.length < 7 || data.password_confirmation.length < 7) {
        //     toast.error("Password must be at least 8 characters long");
        //     return;
        // } else {
        //     const csrfToken = document
        //         .querySelector('meta[name="csrf-token"]')
        //         .getAttribute("content");

        //     fetch(route("zoom.editusercredentials"), {
        //         method: "PATCH",
        //         headers: {
        //             "Content-Type": "application/json",
        //             "X-CSRF-TOKEN": csrfToken,
        //         },
        //         body: JSON.stringify(data),
        //     })
        //         .then((response) => response.json())
        //         .then((res) => {
        //             toast.success(res.message);
        //             handleClose();
        //             reset();
        //         })
        //         .catch((error) => {
        //             console.error("Error:", error);
        //         });
        // }
    };

    return (
        <Dialog
            aria-labelledby="customized-dialog-title"
            open={open}
            onClose={handleClose}
            maxWidth="sm"
            fullWidth
        >
            <DialogTitle id="customized-dialog-title">
                Edit Zoom Credentials of {name}
            </DialogTitle>
            <DialogContent dividers>
                <Grid container spacing={2}>
                    <Grid item xs={12}>
                        <TextField
                            fullWidth
                            label="Zoom Account ID"
                            name="zoomAccountId"
                            value={data.zoomAccountId}
                            onChange={(e) =>
                                setData("zoomAccountId", e.target.value)
                            }
                        />
                    </Grid>
                    <Grid item xs={12}>
                        <TextField
                            fullWidth
                            label="Zoom Client Key"
                            name="zoomClientKey"
                            value={data.zoomClientKey}
                            onChange={(e) =>
                                setData("zoomClientKey", e.target.value)
                            }
                        />
                    </Grid>
                    <Grid item xs={12}>
                        <TextField
                            fullWidth
                            label="Zoom Client Secret"
                            name="zoomClientSecret"
                            value={data.zoomClientSecret}
                            onChange={(e) =>
                                setData("zoomClientSecret", e.target.value)
                            }
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
