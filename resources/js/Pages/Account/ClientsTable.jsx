import SimpleDataTable from "@/Components/SimpleDataTable";
import { hasPermission } from "@/utils/AccessManager";
import CreateClient from "../Clients/CreateClient";
import ClientsTableCell from "./ClientsTableCell";

import { usePage } from "@inertiajs/react";
import { Button, Dialog, DialogTitle, Stack, Typography } from "@mui/material";
import { useState } from "react";

const ClientsTable = ({ clients, companyId, companyName }) => {
    const { auth } = usePage().props;
    const hasBlacklistClientPermission = hasPermission(
        auth,
        "Can Blacklist Client"
    );

    const columns = [
        { id: "clientname", label: "Name" },
        { id: "phones", label: "Phone" },
        { id: "emails", label: "Email" },
        { id: "designation", label: "Designation" },
        { id: "linkedinurl", label: "Linkedin URL" },
        { id: "actions", label: "Actions", align: "right" },
    ];

    // add blacklist column after email column if user has permission
    if (hasBlacklistClientPermission) {
        columns.splice(3, 0, {
            id: "blacklisted",
            label: "Blacklisted",
            align: "center",
        });
    }

    return (
        <>
            <Stack
                my={2}
                direction="row"
                justifyContent="space-between"
                alignItems="center"
            >
                <Typography variant="h6" component="h6">
                    Clients
                </Typography>
                <AddClientDialog
                    companyId={companyId}
                    companyName={companyName}
                />
            </Stack>

            <SimpleDataTable
                columns={columns}
                rows={clients}
                CellComponent={ClientsTableCell}
                tableMaxHeight={"calc(100vh - 370px)"}
            />
        </>
    );
};

export default ClientsTable;

const AddClientDialog = ({ companyId, companyName }) => {
    const { auth } = usePage().props;

    const hasCreateClientPermission = hasPermission(auth, "Can Create Client");

    const [open, setOpen] = useState(false);
    const handleOpen = () => setOpen(true);
    const handleClose = () => setOpen(false);

    return (
        <>
            {hasCreateClientPermission && (
                <Button variant="outlined" color="primary" onClick={handleOpen}>
                    Add Client
                </Button>
            )}

            <Dialog open={open} onClose={handleClose}>
                <DialogTitle>{`Add Client (${companyName})`}</DialogTitle>
                <CreateClient
                    auth={auth}
                    companyId={companyId}
                    handleClose={handleClose}
                />
            </Dialog>
        </>
    );
};
