import { useForm } from "@inertiajs/react";
import { LoadingButton } from "@mui/lab";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    MenuItem,
    Select,
    TextField,
} from "@mui/material";
import FormControl from "@mui/material/FormControl";
import InputLabel from "@mui/material/InputLabel";
import { useState } from "react";
import toast from "react-hot-toast";

const CreatePermission = () => {
    const [open, setOpen] = useState(false);
    const [groups, setGroups] = useState(null);

    const defaultValues = {
        permission_name: null,
        group_id: null,
    };

    const { data, post, setData, reset, processing } = useForm(defaultValues);

    const handleOpen = () => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("permission.create"), {
            method: "GET",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
            },
        })
            .then((response) => response.json())
            .then((data) => {
                const groupsList = data.groups.map((group) => {
                    return { value: group.id, label: group.name };
                });
                setGroups(groupsList);
                setOpen(true);
            });
    };

    const handleClose = () => {
        setOpen(false);
        reset();
    };

    const submit = () => {
        post(route("permission.store"), {
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
        <>
            <Button onClick={handleOpen} variant="contained">
                Create Permission
            </Button>
            <Dialog
                open={open}
                onClose={(_, reason) =>
                    reason !== "backdropClick" && handleClose()
                }
                fullWidth
            >
                <DialogTitle>Create Permission</DialogTitle>
                <DialogContent dividers>
                    <Grid container spacing={2}>
                        <Grid item xs={12}>
                            <FormControl fullWidth size="small">
                                <InputLabel id="group-select">
                                    Permission Group
                                </InputLabel>
                                <Select
                                    value={data.group_id}
                                    labelId="group-select"
                                    id="group-select-element"
                                    label="Permission Group"
                                    onChange={(e) =>
                                        setData("group_id", e.target.value)
                                    }
                                >
                                    {groups &&
                                        groups.map((group) => (
                                            <MenuItem
                                                key={group.value}
                                                value={group.value}
                                            >
                                                {group.label}
                                            </MenuItem>
                                        ))}
                                </Select>
                            </FormControl>
                        </Grid>
                        <Grid item xs={12}>
                            <TextField
                                label="Permission Name"
                                fullWidth
                                value={data.permission_name}
                                onChange={(e) =>
                                    setData("permission_name", e.target.value)
                                }
                                required
                            />
                        </Grid>
                    </Grid>
                </DialogContent>
                <DialogActions>
                    <Button onClick={handleClose} color="error">
                        Cancel
                    </Button>
                    <LoadingButton
                        type="submit"
                        onClick={submit}
                        loading={processing}
                    >
                        Create
                    </LoadingButton>
                </DialogActions>
            </Dialog>
        </>
    );
};

export default CreatePermission;
