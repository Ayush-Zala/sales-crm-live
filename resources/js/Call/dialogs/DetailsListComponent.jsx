import { Datatable } from "@/Components/datatable";
import { TabContext, TabList, TabPanel } from "@mui/lab";
import {
    Box,
    Button,
    Card,
    CircularProgress,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    IconButton,
    Stack,
    Tab,
    TextField,
    Typography,
} from "@mui/material";
import { useState, useEffect } from "react";
import DetailsDataCellComponent from "../CellComponent/DetailsDataCellComponent";
import {
    CallMadeRounded,
    CallReceivedRounded,
    DescriptionRounded,
} from "@mui/icons-material";
import { formatDateTime } from "@/utils/date-time-formatters";
import { hasRole } from "@/utils/AccessManager";
import { usePage } from "@inertiajs/react";
import SimpleDataTable from "@/Components/SimpleDataTable";

export const DetailsListComponent = ({
    open,
    handleClose,
    detailTimeLineData,
}) => {
    const [value, setValue] = useState("1");
    const [openCallRemarkDescDialog, setOpenCallRemarkDescDialog] =
        useState(false);
    const [remark, setRemark] = useState("");

    // State to manage which descriptions are expanded
    const [expanded, setExpanded] = useState({});

    const [expandedRemark, setExpandedRemark] = useState(null);

    const { auth } = usePage().props;
    const isAdminOrBDM = hasRole(auth, [
        "Admin",
        "Business Development Manager",
    ]);

    const handleChange = (event, newValue) => setValue(newValue);

    const handleOpenCallRemarkDescDialog = (remark) => {
        setRemark(remark);
        setOpenCallRemarkDescDialog(true);
    };
    const handleCloseCallRemarkDescDialog = () => {
        setRemark("");
        setOpenCallRemarkDescDialog(false);
    };

    const columns = [
        { name: "id", title: "ID" },
        { name: "status", title: "Status" },
        { name: "description", title: "Description" },
        { name: "followup_date", title: "Followup Date" },
        { name: "followup_time", title: "Followup Time" },
        { name: "timezone", title: "Timezone" },
    ];

    const columnsData = [
        { id: "name", label: "Client Name" },
        { id: "mail", label: "Email" },
        { id: "phone", label: "Phone" },
    ];

    // Toggle the description view
    const toggleDescription = (index) => {
        setExpanded((prev) => ({
            ...prev,
            [index]: !prev[index],
        }));
    };

    const toggleRemark = (index) => {
        setExpandedRemark((prev) => (prev === index ? null : index));
    };

    return (
        <>
            <Dialog
                fullWidth
                open={open}
                maxWidth="md"
                onClose={(_, reason) =>
                    reason !== "backdropClick" && handleClose()
                }
            >
                <DialogTitle>Details of Client</DialogTitle>
                <DialogContent dividers sx={{ height: 400, p: 0 }}>
                    <TabContext value={value}>
                        <TabList centered onChange={handleChange}>
                            <Tab label="Company Details" value="1" />
                            <Tab label="Client Details" value="2" />
                            <Tab label="Dispositions" value="3" />
                            <Tab label="Call Comments" value="4" />
                        </TabList>
                        <TabPanel value="1">
                            {detailTimeLineData?.companyDetails?.length > 0 ? (
                                detailTimeLineData.companyDetails.map(
                                    (detail, index) => (
                                        <Card key={index} sx={{ p: 2, mb: 2 }}>
                                            <Grid container spacing={1}>
                                                <Grid item xs={12}>
                                                    <Typography
                                                        variant="subtitle1"
                                                        sx={{
                                                            fontWeight: "bold",
                                                            color: "primary.main",
                                                            display: "inline",
                                                        }}
                                                    >
                                                        Company Name:&nbsp;
                                                    </Typography>
                                                    <Typography
                                                        variant="body2"
                                                        display="inline"
                                                    >
                                                        {detail.name}
                                                    </Typography>
                                                </Grid>
                                                <Grid item xs={12}>
                                                    <Typography
                                                        variant="subtitle1"
                                                        sx={{
                                                            fontWeight: "bold",
                                                            color: "primary.main",
                                                            display: "inline",
                                                        }}
                                                    >
                                                        Email:&nbsp;
                                                    </Typography>
                                                    <Typography
                                                        variant="body2"
                                                        display="inline"
                                                    >
                                                        {detail.email}
                                                    </Typography>
                                                </Grid>
                                                <Grid item xs={12}>
                                                    <Typography
                                                        variant="subtitle1"
                                                        sx={{
                                                            fontWeight: "bold",
                                                            color: "primary.main",
                                                            display: "inline",
                                                        }}
                                                    >
                                                        Description:&nbsp;
                                                    </Typography>
                                                    <Typography
                                                        variant="body2"
                                                        display="inline"
                                                    >
                                                        {detail.description}
                                                    </Typography>
                                                </Grid>
                                            </Grid>
                                        </Card>
                                    )
                                )
                            ) : (
                                <Typography>
                                    No company details available.
                                </Typography>
                            )}
                        </TabPanel>
                        <TabPanel value="2">
                            {detailTimeLineData?.clientDetails?.length > 0 ? (
                                <DetailsDataTable
                                    columns={columnsData}
                                    rows={detailTimeLineData.clientDetails}
                                    cellComponent={DetailsDataCellComponent}
                                />
                            ) : (
                                <Typography>
                                    No client details available.
                                </Typography>
                            )}
                        </TabPanel>
                        <TabPanel value="3">
                            {detailTimeLineData.callHistory?.length > 0 ? (
                                detailTimeLineData.callHistory.map(
                                    (detail, index) => (
                                        <Card key={index} sx={{ p: 2, mb: 2 }}>
                                            <Typography
                                                variant="subtitle1"
                                                sx={{
                                                    fontWeight: "bold",
                                                    color: "primary.main",
                                                }}
                                            >
                                                {isAdminOrBDM && detail.phone
                                                    ? `${detail.phone} - `
                                                    : ""}
                                                {detail.client_name
                                                    ? detail.client_name
                                                    : detail.company_name}
                                            </Typography>
                                            <Typography
                                                variant="caption"
                                                display="inline"
                                                sx={{
                                                    display: "flex",
                                                    alignItems: "center",
                                                }}
                                            >
                                                <CallMadeRounded
                                                    color="success"
                                                    fontSize="small"
                                                    sx={{ mr: 1 }}
                                                />

                                                {`${formatDateTime(
                                                    detail.created_at
                                                )}`}
                                            </Typography>
                                            {/* Description with "Read More" / "Show Less" */}
                                            <Stack
                                                direction="row"
                                                justifyContent="flex-start"
                                                alignItems="center"
                                            >
                                                <Typography
                                                    variant="body2"
                                                    mt={1}
                                                >
                                                    {expanded[index] ? (
                                                        <>
                                                            {detail.description}
                                                            <Typography
                                                                component="span"
                                                                sx={{
                                                                    color: "primary.main", // Highlight color
                                                                    cursor: "pointer", // Indicates it's clickable
                                                                    display:
                                                                        "inline", // Ensures inline text
                                                                }}
                                                                onClick={() =>
                                                                    toggleDescription(
                                                                        index
                                                                    )
                                                                }
                                                            >
                                                                &nbsp;Show Less
                                                            </Typography>
                                                        </>
                                                    ) : (
                                                        <>
                                                            {`${detail.description?.substring(
                                                                0,
                                                                100
                                                            )}...`}
                                                            <Typography
                                                                component="span"
                                                                sx={{
                                                                    color: "primary.main", // Highlight color
                                                                    cursor: "pointer", // Indicates it's clickable
                                                                    display:
                                                                        "inline", // Ensures inline text
                                                                }}
                                                                onClick={() =>
                                                                    toggleDescription(
                                                                        index
                                                                    )
                                                                }
                                                            >
                                                                &nbsp;Read More
                                                            </Typography>
                                                        </>
                                                    )}
                                                </Typography>
                                            </Stack>
                                        </Card>
                                    )
                                )
                            ) : (
                                <Typography>
                                    No disposition available.
                                </Typography>
                            )}
                        </TabPanel>
                        <TabPanel value="4">
                            {/* card like call dialog */}
                            {detailTimeLineData.callRemarks?.length > 0 ? (
                                detailTimeLineData?.callRemarks?.map(
                                    (detail, index) => (
                                        <Card key={index} sx={{ p: 2, mb: 2 }}>
                                            <Typography
                                                variant="subtitle1"
                                                sx={{
                                                    fontWeight: "bold",
                                                    color: "primary.main",
                                                }}
                                            >
                                                {isAdminOrBDM && detail.phone
                                                    ? `${detail.phone} - `
                                                    : ""}
                                                {detail.client_name
                                                    ? detail.client_name
                                                    : detail.company_name}
                                            </Typography>
                                            <Typography
                                                variant="caption"
                                                display="inline"
                                                sx={{
                                                    display: "flex",
                                                    alignItems: "center",
                                                }}
                                            >
                                                {detail.type === "incoming" ? (
                                                    <CallReceivedRounded
                                                        color="success"
                                                        fontSize="small"
                                                        sx={{ mr: 1 }}
                                                    />
                                                ) : (
                                                    <CallMadeRounded
                                                        color="success"
                                                        fontSize="small"
                                                        sx={{ mr: 1 }}
                                                    />
                                                )}
                                                {formatDateTime(
                                                    detail.created_at
                                                )}
                                            </Typography>

                                            {/* Description with "Read More" / "Show Less" */}
                                            <Stack>
                                                <Typography
                                                    variant="body2"
                                                    mt={1}
                                                >
                                                    {expandedRemark ===
                                                    index ? (
                                                        <>
                                                            {detail.remark}
                                                            <Typography
                                                                component="span"
                                                                sx={{
                                                                    color: "primary.main", // Highlight color
                                                                    cursor: "pointer", // Indicates it's clickable
                                                                    display:
                                                                        "inline", // Ensures inline text
                                                                }}
                                                                onClick={() =>
                                                                    toggleRemark(
                                                                        index
                                                                    )
                                                                }
                                                            >
                                                                &nbsp;Show Less
                                                            </Typography>
                                                        </>
                                                    ) : (
                                                        <>
                                                            {`${detail.remark?.substring(
                                                                0,
                                                                100
                                                            )}...`}
                                                            <Typography
                                                                component="span"
                                                                sx={{
                                                                    color: "primary.main", // Highlight color
                                                                    cursor: "pointer", // Indicates it's clickable
                                                                    display:
                                                                        "inline", // Ensures inline text
                                                                }}
                                                                onClick={() =>
                                                                    toggleRemark(
                                                                        index
                                                                    )
                                                                }
                                                            >
                                                                &nbsp;Read More
                                                            </Typography>
                                                        </>
                                                    )}
                                                </Typography>
                                            </Stack>
                                        </Card>
                                    )
                                )
                            ) : (
                                <Typography>No Comments available.</Typography>
                            )}
                        </TabPanel>
                    </TabContext>
                </DialogContent>
                <DialogActions>
                    <Button onClick={handleClose} color="error">
                        Close
                    </Button>
                </DialogActions>
            </Dialog>
            <CallRemarkDescDialog
                title="Comment"
                open={openCallRemarkDescDialog}
                handleClose={handleCloseCallRemarkDescDialog}
                description={remark}
            />
        </>
    );
};

export default DetailsListComponent;

const DetailsDataTable = ({ columns, rows, cellComponent }) => {
    return (
        <SimpleDataTable
            columns={columns}
            rows={rows}
            CellComponent={cellComponent}
            tableMaxHeight={"calc(100vh - 467px)"}
        />
    );
};

const CallRemarkDescDialog = ({ title, open, handleClose, description }) => {
    const [desc, setDesc] = useState(description);

    useEffect(() => {
        setDesc(description);

        return () => {
            setDesc("");
        };
    }, [description]);

    return (
        <Dialog
            open={open}
            fullWidth
            maxWidth="sm"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
        >
            <DialogTitle>{title}</DialogTitle>
            <DialogContent dividers>
                <TextField multiline rows={4} value={desc} fullWidth />
            </DialogContent>
            <DialogActions>
                <Button onClick={handleClose} color="error">
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};
