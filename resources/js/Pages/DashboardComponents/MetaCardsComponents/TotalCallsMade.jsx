import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Typography,
} from "@mui/material";
import { useState } from "react";
import MuiLink from "@mui/material/Link";
import { Chip as MuiChip, styled } from "@mui/material";

import SimpleDataTable from "@/Components/SimpleDataTable";
import MetaDataCard from "../MetaDataCard";
import { formatDateTime } from "@/utils/date-time-formatters";
import { green, pink } from "@mui/material/colors";

const TotalCallsMade = ({ count }) => {
    const [callsDetailsDialog, setCallsDetailsDialog] = useState(false);
    const [callsDetails, setCallsDetails] = useState([]);

    const handleCallsDetailsDialogOpen = () => setCallsDetailsDialog(true);
    const handleCallsDetailsDialogClose = () => setCallsDetailsDialog(false);

    const handleGetCallsDetails = () => {
        try {
            fetch(
                route("dashboard.gettotalcallsdetails", { filter: "life_time" })
            )
                .then((response) => response.json())
                .then((data) => {
                    setCallsDetails([data.calls]);
                    handleCallsDetailsDialogOpen();
                });
        } catch (error) {
            console.error("Error fetching calls details", error);
            toast.error("Error fetching calls details");
        }
    };

    return (
        <>
            <MetaDataCard
                format
                title="Total Calls Made"
                count={count || 0}
                isLinkOnCount={true}
                handleClickOnCount={handleGetCallsDetails}
            />

            <CallsDetailsDialogBox
                open={callsDetailsDialog}
                onClose={handleCallsDetailsDialogClose}
                calls={callsDetails}
            />
        </>
    );
};

export default TotalCallsMade;

const CallsDetailsDialogBox = ({ open, onClose, calls }) => {
    return (
        <Dialog
            open={open}
            onClose={(_, reason) => reason !== "backdropClick" && onClose()}
            maxWidth={calls.length > 0 ? "md" : "sm"}
            fullWidth
        >
            <DialogTitle>Calls Details</DialogTitle>
            <DialogContent dividers>
                {calls.length > 0 ? (
                    <CallsDetailsTable calls={calls} />
                ) : (
                    <Typography
                        variant="body1"
                        color="error.main"
                        textAlign="center"
                        sx={{ py: 2 }}
                    >
                        No calls data found.
                    </Typography>
                )}
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose} color="error">
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};

const CallsDetailsTable = ({ calls }) => {
    const [open, setOpen] = useState(false);
    const [dispositionData, setDispositionData] = useState(null);

    const handleOpen = () => setOpen(true);
    const handleClose = () => setOpen(false);

    const columns = [
        { id: "disposition", label: "Disposition" },
        { id: "total_count", label: "Total Count" },
    ];

    // get only company name, user name and updated at from the data
    const rows = Object.entries(calls[0]).map(([key, value]) => ({
        disposition: key, // Use the key as the disposition
        total_count: value, // Use the value as the total_count
    }));

    const handleRowClick = (disposition) => {
        fetch(
            route("dashboard.getDispositionCallDetailsForSalesExec", {
                dispositionName: disposition,
                filter: "life_time",
            })
        )
            .then((response) => response.json())
            .then((data) => {
                setDispositionData(data.dispositionDetails);
                handleOpen();
            })
            .catch((error) => {
                console.error("Error fetching data", error);
            });
    };

    return (
        <>
            <SimpleDataTable
                columns={columns}
                rows={rows}
                CellComponent={CallDetailsCellComponent}
                clickableRow={true}
                handleClickRow={handleRowClick}
                tableMaxHeight={"calc(100vh - 270px)"}
            />

            {/* Display the disposition data details */}
            <DispositionDataDetails
                data={dispositionData}
                open={open}
                onClose={handleClose}
            />
        </>
    );
};

const CallDetailsCellComponent = ({ column, row }) => {
    switch (column.id) {
        default:
            return row[column.id];
    }
};

const DispositionDataDetails = ({ data, open, onClose }) => {
    const columns = [
        { id: "company_name", label: "Company Name" },
        { id: "user_name", label: "User Name" },
        { id: "updated_at", label: "Date" },
    ];

    const rows =
        data &&
        data.map((item) => ({
            company_name: item.company_name,
            user_name: item.user_name,
            updated_at: item.updated_at,
            company_id: item.company_id,
        }));

    return (
        <Dialog
            open={open}
            onClose={(_, reason) => reason !== "backdropClick" && onClose()}
            maxWidth="md"
            fullWidth
        >
            <DialogTitle>Disposition Details</DialogTitle>
            <DialogContent dividers>
                <SimpleDataTable
                    columns={columns}
                    rows={rows}
                    tableMaxHeight={"calc(100vh - 370px)"}
                    CellComponent={DispositionCallDataCellComponent}
                />
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose} color="error">
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};

const DispositionCallDataCellComponent = ({ column, row }) => {
    switch (column.id) {
        case "company_name":
            return (
                <MuiLink
                    underline="none"
                    href={route("account.view", { id: row.company_id })}
                    target="_blank"
                >
                    {row.company_name}
                </MuiLink>
            );
        case "user_name":
            return (
                <Chip
                    clickable
                    size="small"
                    label={row.user_name}
                    sx={{ borderRadius: 1 }}
                    bgColor={pink[100]}
                    textColor={pink[800]}
                    bgHoverColor={pink[800]}
                    bgTextColor={pink[100]}
                />
            );
        case "updated_at":
            return (
                row.updated_at && (
                    <Chip
                        clickable
                        size="small"
                        label={formatDateTime(row.updated_at)}
                        sx={{ borderRadius: 1 }}
                    />
                )
            );
        default:
            return null;
    }
};

const Chip = styled(MuiChip)(
    ({
        bgColor = green[100],
        textColor = green[800],
        bgHoverColor = green[800],
        bgTextColor = green[100],
    }) => ({
        fontWeight: 600,
        backgroundColor: bgColor,
        color: textColor,
        "&:hover": {
            backgroundColor: bgHoverColor,
            color: bgTextColor,
        },
    })
);
