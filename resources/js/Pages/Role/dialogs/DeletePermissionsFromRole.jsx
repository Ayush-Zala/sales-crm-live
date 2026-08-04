import React from "react";
import {
    Dialog,
    Paper,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    DialogActions,
    DialogTitle,
    DialogContent,
    Button,
    Typography,
    DialogContentText,
} from "@mui/material";
import { CheckboxElement, FormContainer, useForm } from "react-hook-form-mui";
import { LoadingButton } from "@mui/lab";
import { yupResolver } from "@hookform/resolvers/yup";
import * as Yup from "yup";
import toast from "react-hot-toast";

export const DeletePermissionsFromRole = ({
    permissions,
    role,
    open,
    handleClose,
}) => {
    const defaultValues = {
        permissions:
            permissions?.reduce((acc, permission) => {
                acc[permission.permission_id] = permission.allowed || false;
                return acc;
            }, {}) || {},
    };

    const schema = Yup.object().shape({
        permissions: Yup.object().required("Permissions are required"), // Ensure permissions are selected
    });

    const { control, handleSubmit } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    // Dynamically assign `group_name` if it does not exist
    const updatedPermissions = permissions.map((permission) => ({
        ...permission,
        group_name: permission.group_name || "General", // Add a default group name if missing
    }));

    const groupedData = Object.values(
        updatedPermissions.reduce((acc, permission) => {
            if (!acc[permission.group_name]) {
                acc[permission.group_name] = {
                    group_name: permission.group_name,
                    permissions: [],
                };
            }
            acc[permission.group_name].permissions.push(permission);
            return acc;
        }, {})
    );

    const submit = (data) => {
        // get the permissions that are selected
        const permissionsToDelete = Object.entries(data.permissions)
            .filter(([, allowed]) => allowed)
            .map(([permission_id]) => permission_id);

        if (permissionsToDelete.length === 0) {
            toast.error("Please select permissions to delete");
            return;
        }

        // delete permissions from role using fetch
        fetch(route("role.deletePermissionsFromRole"), {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                role_id: role.id,
                permissions: permissionsToDelete,
            }),
        })
            .then((response) => response.json())
            .then((res) => {
                toast.success(res.message);
                handleClose();
            })
            .catch((error) => {
                console.error(error);
                toast.error("Error deleting permissions");
            });
    };

    return (
        <Dialog
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
            maxWidth="lg"
        >
            <FormContainer
                defaultValues={defaultValues}
                onSuccess={handleSubmit(submit)}
            >
                <DialogTitle>Delete Permissions</DialogTitle>
                <DialogContent dividers sx={{ maxHeight: 500 }}>
                    <DialogContentText mb={2} mx={2}>
                        <strong>*</strong>Select permissions below to remove
                        permissions from the <strong>{role.name}</strong> role
                    </DialogContentText>
                    {groupedData.map((group, index) => (
                        <TableContainer component={Paper} key={index}>
                            <Table aria-label="permissions table" stickyHeader>
                                <TableHead>
                                    <TableRow>
                                        <TableCell>Permission Name</TableCell>
                                        <TableCell>Allow</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {group.permissions.map((perm) => (
                                        <TableRow key={perm.id}>
                                            <TableCell>{perm.name}</TableCell>
                                            <TableCell>
                                                <CheckboxElement
                                                    control={control}
                                                    name={`permissions.${perm.id}`}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    ))}
                </DialogContent>
                <DialogActions>
                    <Button color="error" onClick={handleClose}>
                        Cancel
                    </Button>
                    <LoadingButton type="submit">Delete</LoadingButton>
                </DialogActions>
            </FormContainer>
        </Dialog>
    );
};
