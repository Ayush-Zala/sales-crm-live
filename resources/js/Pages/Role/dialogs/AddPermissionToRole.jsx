import { yupResolver } from "@hookform/resolvers/yup";
import { LoadingButton } from "@mui/lab";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Paper,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
} from "@mui/material";
import { CheckboxElement, FormContainer, useForm } from "react-hook-form-mui";
import toast from "react-hot-toast";
import * as Yup from "yup";

export const AddPermissionToRole = ({
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
        // Filter out permissions that are not allowed
        const allowedPermissions = Object.entries(data.permissions)
            .filter(([, allowed]) => allowed)
            .map(([permission_id]) => permission_id);

        // Add permissions to role
        fetch(route("role.addPermissionsToRole"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                role_id: role.id,
                permissions: allowedPermissions,
            }),
        })
            .then((response) => response.json())
            .then((res) => {
                toast.success(res.message);
                handleClose();
            })
            .catch((error) => {
                console.error(error);
                toast.error("Error adding permissions to role");
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
                <DialogTitle>Permissions</DialogTitle>
                <DialogContent dividers>
                    {groupedData.map((group, index) => (
                        <TableContainer
                            key={index}
                            component={Paper}
                            sx={{ maxHeight: "500px" }}
                        >
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
                                                    checked={perm.allowed}
                                                    disabled={perm.allowed}
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
                    <LoadingButton type="submit">Add</LoadingButton>
                </DialogActions>
            </FormContainer>
        </Dialog>
    );
};
