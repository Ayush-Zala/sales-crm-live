import { useForm } from "@inertiajs/react";
import { EditRounded } from "@mui/icons-material";
import { LoadingButton } from "@mui/lab";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    Grid,
    IconButton,
    InputLabel,
    MenuItem,
    Select,
    TextField,
} from "@mui/material";
import { Fragment, useState } from "react";
import toast from "react-hot-toast";

const EditPermission = ({ permission }) => {
    const [open, setOpen] = useState(false);
    const [details, setDetails] = useState(null);

    const handleOpen = () => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("permission.edit"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ permission_id: permission.id }),
        })
            .then((response) => response.json())
            .then((data) => {
                setDetails(data);
                setOpen(true);
            })
            .catch((error) => {
                console.error(error);
            });
    };
    const handleClose = () => setOpen(false);

    return (
        <Fragment>
            <IconButton onClick={handleOpen}>
                <EditRounded />
            </IconButton>
            <EditPermissionDialog
                permission={permission}
                details={details}
                open={open}
                handleClose={handleClose}
            />
        </Fragment>
    );
};

export default EditPermission;

const EditPermissionDialog = ({ permission, open, handleClose, details }) => {
    const defaultValues = {
        permission_name: permission.name,
        permission_id: permission.id,
        group_id:
            details?.groups?.find(
                (group) => group.id === details.permission?.group_id
            ) || "",
    };

    const { data, setData, post, reset, processing } = useForm(defaultValues);

    const submit = () => {
        post(route("permission.update"), {
            onSuccess: (res) => {
                toast.success(res.props.flash.success);
                handleClose();
            },
            onError: (error) => {
                console.log(error);
                toast.error("An error occurred. Please try again.");
            },
        });
    };

    return (
        <Dialog
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
            fullWidth
        >
            <DialogTitle id="alert-dialog-title">Edit Permission</DialogTitle>
            <DialogContent dividers>
                <Grid container spacing={2}>
                    <Grid item xs={12}>
                        <TextField
                            label="Permission Name"
                            fullWidth
                            value={data.permission_name}
                            onChange={(e) =>
                                setData("permission_name", e.target.value)
                            }
                        />
                    </Grid>
                    <Grid item xs={12}>
                        <FormControl fullWidth size="small">
                            <InputLabel id="group-select">
                                Permission Group
                            </InputLabel>
                            <Select
                                value={data.group_id}
                                labelId="group-select"
                                id="grouped-select-element"
                                label="Permission Group"
                                onChange={(e) =>
                                    setData("group_id", e.target.value)
                                }
                            >
                                {details?.groups &&
                                    details.groups.map((group) => (
                                        <MenuItem
                                            key={group.id}
                                            value={group.id}
                                        >
                                            {group.name}
                                        </MenuItem>
                                    ))}
                            </Select>
                        </FormControl>
                    </Grid>
                </Grid>
            </DialogContent>
            <DialogActions>
                <Button
                    onClick={() => {
                        handleClose();
                        reset();
                    }}
                    color="error"
                >
                    Cancel
                </Button>
                <LoadingButton
                    type="submit"
                    autoFocus
                    onClick={submit}
                    loading={processing}
                >
                    Save
                </LoadingButton>
            </DialogActions>
        </Dialog>
    );
};
