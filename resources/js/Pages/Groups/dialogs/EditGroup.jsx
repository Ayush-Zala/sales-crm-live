import { EditRounded } from "@mui/icons-material";
import { IconButton } from "@mui/material";
import { Fragment, useState } from "react";

import { yupResolver } from "@hookform/resolvers/yup";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
} from "@mui/material";
import { FormContainer, TextFieldElement, useForm } from "react-hook-form-mui";
import * as Yup from "yup";
import toast from "react-hot-toast";

const EditGroup = ({ group }) => {
    const [open, setOpen] = useState(false);

    const handleOpen = () => {
        setOpen(true);
    };

    const handleClose = () => {
        setOpen(false);
    };

    return (
        <Fragment>
            <IconButton size="small" onClick={handleOpen}>
                <EditRounded />
            </IconButton>
            <EditGroupDialog
                group={group}
                open={open}
                handleClose={handleClose}
            />
        </Fragment>
    );
};

export default EditGroup;

const EditGroupDialog = ({ group, open, handleClose }) => {
    const defaultValues = {
        name: group.name,
        description: group.description,
    };

    const schema = Yup.object().shape({
        name: Yup.string().required("Name is required"),
        description: Yup.string().required("Description is required"),
    });

    const { control, handleSubmit, reset } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    const submit = (data) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("group.update"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ ...data, id: group.id }),
        })
            .then((response) => response.json())
            .then((res) => {
                toast.success(res.message);
                handleClose();
                reset();
            })
            .catch((error) => {
                console.error(error);
                toast.error("Error updating group");
            });
    };

    return (
        <Dialog
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
        >
            <FormContainer
                defaultValues={defaultValues}
                onSuccess={handleSubmit(submit)}
            >
                <DialogTitle id="alert-dialog-title">Create Group</DialogTitle>

                <DialogContent dividers>
                    <Grid item container xs={12} spacing={2}>
                        <Grid item xs={12}>
                            <TextFieldElement
                                name="name"
                                label="Name"
                                control={control}
                            />
                        </Grid>
                        <Grid item xs={12}>
                            <TextFieldElement
                                name="description"
                                label="Desccription"
                                control={control}
                            />
                        </Grid>
                    </Grid>
                </DialogContent>
                <DialogActions>
                    <Button onClick={handleClose} color="error">
                        Disagree
                    </Button>
                    <Button type="submit" control={control}>
                        Agree
                    </Button>
                </DialogActions>
            </FormContainer>
        </Dialog>
    );
};
