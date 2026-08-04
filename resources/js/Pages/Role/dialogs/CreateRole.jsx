import { useForm } from "@inertiajs/react";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    TextField,
} from "@mui/material";
import toast from "react-hot-toast";

const CreateRole = ({ open, handleClose }) => {
    const defaultValues = {
        role_name: "",
    };

    const { data, post, setData, reset } = useForm(defaultValues);

    const submit = () => {
        post(route("role.create"), {
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
            <DialogTitle id="alert-dialog-title">Create new role</DialogTitle>
            <DialogContent dividers>
                <TextField
                    label="Role Name"
                    fullWidth
                    value={data.role_name}
                    onChange={(e) => setData("role_name", e.target.value)}
                />
            </DialogContent>
            <DialogActions>
                <Button onClick={handleClose} color="error">
                    Cancel
                </Button>
                <Button type="submit" onClick={submit}>
                    Create
                </Button>
            </DialogActions>
        </Dialog>
    );
};

export default CreateRole;
