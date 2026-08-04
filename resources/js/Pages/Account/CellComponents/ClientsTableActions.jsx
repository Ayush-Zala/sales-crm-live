import { matisse } from "@/theme/colors";
import { hasPermission } from "@/utils/AccessManager";

import { Link, router, usePage } from "@inertiajs/react";
import { DeleteRounded, EditRounded } from "@mui/icons-material";
import { IconButton, Stack, Tooltip } from "@mui/material";
import { confirm } from "material-ui-confirm";
import toast from "react-hot-toast";

const ClientsTableActions = ({ row }) => {
    const { auth } = usePage().props;
    const hasClientDeletePermission = hasPermission(auth, "Can Delete Client");

    const handleDelete = async () => {
        await confirm({
            title: `Delete ${row.name}!`,
            description: `Are you sure you want to delete "${row.name}"?`,
            confirmationText: "Yes",
            cancellationText: "No",
            confirmationButtonProps: { color: "error" },
        }).then(() => {
            router.delete(
                route("client.delete", { id: row.id }),
                {},
                {
                    onSuccess: (response) => {
                        if (response.props.flash.error) {
                            toast.error(response.props.flash.error);
                            return;
                        }

                        toast.success(response.props.flash.success);
                    },
                    onError: (error) => {
                        console.error(error);
                        toast.error("Something went wrong! Please try again.");
                    },
                }
            );
        });
    };

    return (
        <Stack direction="row" justifyContent="flex-end" spacing={1}>
            <Tooltip title={`Edit ${row.name}`} placement="left">
                <IconButton
                    aria-label="edit"
                    size="small"
                    sx={{ ":hover": { color: matisse[600] } }}
                    LinkComponent={Link}
                    href={route("client.edit", {
                        id: row.id,
                    })}
                >
                    <EditRounded />
                </IconButton>
            </Tooltip>

            {hasClientDeletePermission && (
                <Tooltip title={`Delete ${row.name}`} placement="left">
                    <IconButton
                        aria-label="edit"
                        size="small"
                        sx={{ ":hover": { color: "error.main" } }}
                        onClick={handleDelete}
                    >
                        <DeleteRounded />
                    </IconButton>
                </Tooltip>
            )}
        </Stack>
    );
};

export default ClientsTableActions;
