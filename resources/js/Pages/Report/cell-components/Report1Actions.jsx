import SimpleDataTable from "@/Components/SimpleDataTable";
import { hasPermission } from "@/utils/AccessManager";
import { formatDateTime } from "@/utils/date-time-formatters";
import { usePage } from "@inertiajs/react";
import { DvrRounded } from "@mui/icons-material";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    IconButton,
    Tooltip,
} from "@mui/material";
import { useState } from "react";

const Report1Actions = ({ companyId }) => {
    return <ViewDispositions companyId={companyId} />;
};

export default Report1Actions;

const ViewDispositions = ({ companyId }) => {
    const [open, setOpen] = useState(false);
    const handleClose = () => setOpen(false);

    const [data, setData] = useState(null);

    const handleOpen = () => {
        fetch(`/report/disposition-list-by-company-id/${companyId}`)
            .then((response) => response.json())
            .then((res) => {
                setData(res);
                setOpen(true);
            });
    };

    const columns = [
        { id: "name", label: "Company", textAlign: "left" },
        { id: "status", label: "Disposition", textAlign: "left" },
        { id: "phone", label: "Phone", textAlign: "left" },
        { id: "user", label: "User", textAlign: "left" },
        { id: "created_at", label: "Created At", textAlign: "left" },
        { id: "updated_at", label: "Updated At", textAlign: "left" },
    ];

    return (
        <>
            <Tooltip
                title="View Dispositions"
                placement="left"
                arrow
                sx={{ ":hover": { color: "warning.main" } }}
            >
                <IconButton size="small" onClick={handleOpen}>
                    <DvrRounded fontSize="small" />
                </IconButton>
            </Tooltip>

            <Dialog open={open} onClose={handleClose} maxWidth="md" fullWidth>
                <DialogTitle>View Dispositions</DialogTitle>
                <DialogContent dividers>
                    <SimpleDataTable
                        columns={columns}
                        rows={data}
                        CellComponent={DispositionCellComponent}
                        tableMaxHeight={500}
                    />
                </DialogContent>
                <DialogActions>
                    <Button
                        onClick={handleClose}
                        variant="outlined"
                        color="error"
                    >
                        Cancel
                    </Button>
                </DialogActions>
            </Dialog>
        </>
    );
};

const DispositionCellComponent = ({ row, column }) => {
    const { auth } = usePage().props;
    const hasViewPhonePerm = hasPermission(auth, "Can View Company Phone");

    switch (column.id) {
        case "name":
            return row.company.name;
        case "status":
            return row.status;
        case "phone":
            return hasViewPhonePerm
                ? row.phone
                : row.client.name
                ? row.client.name
                : row.company.name;
        case "user":
            return row.user.name;
        case "created_at":
            return row.created_at && formatDateTime(row.created_at);
        case "updated_at":
            return row.updated_at && formatDateTime(row.updated_at);
        default:
            return null;
    }
};
