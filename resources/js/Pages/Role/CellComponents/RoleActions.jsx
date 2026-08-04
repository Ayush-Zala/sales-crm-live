import { matisse } from "@/theme/colors";
import { Link } from "@inertiajs/react";
import {
    AddModeratorRounded,
    DeleteRounded,
    EditRounded,
} from "@mui/icons-material";
import { IconButton, Tooltip } from "@mui/material";
import { Fragment, useState } from "react";
import { AddPermissionToRole } from "../dialogs/AddPermissionToRole";
import { DeletePermissionsFromRole } from "../dialogs/DeletePermissionsFromRole";

const RoleActions = ({ role }) => {
    return (
        <Fragment>
            <EditAction role={role} />
            <AddPermission role={role} />
            <DeletePermissions role={role} />
        </Fragment>
    );
};

export default RoleActions;

const EditAction = ({ role }) => {
    return (
        <Tooltip title={`Edit ${role.name} role`} placement="left">
            <IconButton
                aria-label="edit"
                size="small"
                sx={{ ":hover": { color: matisse[600] } }}
                LinkComponent={Link}
                href={route("role.edit", { id: role.id })}
            >
                <EditRounded />
            </IconButton>
        </Tooltip>
    );
};

const AddPermission = ({ role }) => {
    const [open, setOpen] = useState(false);
    const [permissions, setPermissions] = useState([]);

    const handleOpen = () => {
        fetch(route("permission.getPermissionsByRole", { role_id: role.id }))
            .then((response) => response.json())
            .then((res) => {
                // Add `allowed: true` or `false` based on the ID comparison
                const updatedPermissions = res.permissions.map((permission) => {
                    const isAllowed = res.permissionsByRole.some(
                        (rolePermission) => rolePermission.id === permission.id
                    );
                    return { ...permission, allowed: isAllowed };
                });

                // Sort permissions to have `allowed: true` first
                const sortedPermissions = updatedPermissions.sort(
                    (a, b) => b.allowed - a.allowed
                );

                setPermissions(sortedPermissions);
                setOpen(true);
            });
    };

    const handleClose = () => {
        setOpen(false);
    };

    return (
        <>
            <Tooltip title={`Add permissions to ${role.name}`} placement="left">
                <IconButton
                    aria-label="edit"
                    size="small"
                    sx={{ ":hover": { color: matisse[600] } }}
                    onClick={handleOpen}
                >
                    <AddModeratorRounded />
                </IconButton>
            </Tooltip>

            {/* Move AddPermissionToRole outside of the IconButton */}
            <AddPermissionToRole
                open={open}
                handleClose={handleClose}
                permissions={permissions}
                role={role}
            />
        </>
    );
};

const DeletePermissions = ({ role }) => {
    const [open, setOpen] = useState(false);
    const [permissions, setPermissions] = useState([]);

    const handleOpen = () => {
        fetch(route("permission.getPermissionsByRole", { role_id: role.id }))
            .then((response) => response.json())
            .then((res) => {
                setPermissions(res.permissionsByRole);
                setOpen(true);
            });
    };

    const handleClose = () => {
        setOpen(false);
    };

    return (
        <>
            <Tooltip title={`Add permissions to ${role.name}`} placement="left">
                <IconButton
                    aria-label="edit"
                    size="small"
                    sx={{ ":hover": { color: "red" } }}
                    onClick={handleOpen}
                    aria-hidden="false"
                >
                    <DeleteRounded />
                </IconButton>
            </Tooltip>
            <DeletePermissionsFromRole
                open={open}
                handleClose={handleClose}
                permissions={permissions}
                role={role}
            />
        </>
    );
};
