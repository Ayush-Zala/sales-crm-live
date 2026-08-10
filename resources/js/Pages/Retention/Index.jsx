import { Head } from "@inertiajs/react";
import React, { useState } from "react";
import axios from "axios";
import { Grid, TextField, IconButton, CircularProgress, Tooltip, Snackbar, Alert } from "@mui/material";
import SyncIcon from "@mui/icons-material/Sync";

import SelectPaginatedTable from "@/Components/SelectPaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { useLeadsSelectionStore } from "@/store/leads-selection-store";
import { hasPermission, hasRole } from "@/utils/AccessManager";
import { LeadDataSearch } from "./lead-data-search";
import LeadDispositionFilterSearch from "./lead-disposition-filter-search";
import LeadAssignComponent from "./LeadAssignComponent";
import LeadFilterComponent from "./LeadFilterComponent";
import LeadTableCellComponent from "./LeadTableCellComponent";
import LeadUserListFilterComponent from "./LeadUserListFilterComponent";

const index = ({ auth, leadsData, users, dispositions }) => {
    const { current_page, per_page, total, last_page, data: rows } = leadsData;
    
    const [syncMonths, setSyncMonths] = useState(6);
    const [isSyncing, setIsSyncing] = useState(false);
    const [snackbarMsg, setSnackbarMsg] = useState({ open: false, message: "", severity: "success" });

    const handleSnackbarClose = () => setSnackbarMsg({ ...snackbarMsg, open: false });

    const runRetentionImport = async () => {
        setIsSyncing(true);
        try {
            const response = await axios.post(`/api/retention-import?months=${syncMonths}`);
            
            const { id } = response.data;

            const poll = setInterval(async () => {
                try {
                    const res = await axios.get(`/api/retention-import/${id}`);
                    const log = res.data;
                    
                    if (log.status === "completed") {
                        clearInterval(poll);
                        setIsSyncing(false);
                        setSnackbarMsg({ 
                            open: true, 
                            message: `Sync completed! Updated ${log.summary.companies_updated} companies.`, 
                            severity: "success" 
                        });
                        // Could trigger a reload here if wanted: window.location.reload();
                    } else if (log.status === "failed") {
                        clearInterval(poll);
                        setIsSyncing(false);
                        setSnackbarMsg({ open: true, message: "Import failed: " + log.error, severity: "error" });
                    }
                } catch (pollErr) {
                    clearInterval(poll);
                    setIsSyncing(false);
                    setSnackbarMsg({ open: true, message: "Error checking status.", severity: "error" });
                }
            }, 2000);
        } catch (error) {
            console.error(error);
            if (error.response && error.response.status === 409) {
                setSnackbarMsg({ open: true, message: "An import is already running.", severity: "warning" });
            } else {
                setSnackbarMsg({ open: true, message: "Error triggering sync.", severity: "error" });
            }
            setIsSyncing(false);
        }
    };

    const { selection, setSelection } = useLeadsSelectionStore();

    const role = hasRole(auth, [
        "Customer Service Representative Manager",
        "Customer Service Representative Team Lead",
        "Customer Service Representative",
    ]);

    const isAdminOrCSRM = hasRole(auth, [
        "Customer Service Representative Manager",
        "Admin",
    ]);

    const hasAssignPermission = hasPermission(
        auth,
        "Can Edit Retention Assign User"
    );

    const hasSyncPermission = hasPermission(
        auth,
        "Can Import Retention"
    );


    const allColumns = [
        { id: "name", label: "Name", align: "left", disableSearch: false },
        {
            id: "lastOrderDate",
            label: "Last US Order Date",
            disableSearch: true,
        },
        { id: "industry", label: "Industry", disableSearch: false },
        { id: "clientName", label: "Client Name", disableSearch: false },
        { id: "clientPhones", label: "Client Phones", disableSearch: false },
        { id: "clientEmails", label: "Client Emails", disableSearch: false },
        {
            id: "retentionPhones",
            label: "Company Phones",
            disableSearch: false,
        },
        {
            id: "retentionEmails",
            label: "Company Emails",
            disableSearch: false,
        },
        {
            id: "assignTo",
            label: "Assign To",
            disableSearch: false,
            search: "",
        },
        {
            id: "assignBy",
            label: "Assign By",
            disableSearch: false,
            search: "",
        },
        { id: "leadProvideBy", label: "Retention By", disableSearch: false },
        {
            id: "dispositionType",
            label: "Disposition",
            align: "right",
            disableSearch: true,
        },
    ];

    const columns = allColumns.filter((column) => {
        // Exclude both `assignTo` and `assignBy` for non-Admin/Non-BDM
        if (!hasAssignPermission && ["assignBy"].includes(column.id))
            return false;

        // Include all other columns
        return true;
    });

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Retention" />
            <MainContentTemplate
                title="Retention"
                subtitle="View Retention list here"
                buttonComponent={
                    hasSyncPermission && (
                        <Grid item display="flex" alignItems="center" gap={1}>
                            <TextField
                                type="number"
                                size="small"
                                label="Months"
                                variant="outlined"
                                value={syncMonths}
                                onChange={(e) => setSyncMonths(e.target.value)}
                                disabled={isSyncing}
                                inputProps={{ min: 1 }}
                                sx={{ width: '80px' }}
                            />
                            <Tooltip title="Sync Retention Data">
                                <span>
                                    <IconButton 
                                        color="primary" 
                                        onClick={runRetentionImport} 
                                        disabled={isSyncing}
                                    >
                                        {isSyncing ? <CircularProgress size={24} /> : <SyncIcon />}
                                    </IconButton>
                                </span>
                            </Tooltip>
                        </Grid>
                    )
                }
            >
                <Grid
                    container
                    item
                    xs={12}
                    sx={{
                        display: "flex",
                        justifyContent: "flex-end",
                        alignItems: "center",
                        gap: 2,
                    }}
                >
                    <Grid item>
                        <LeadFilterComponent />
                    </Grid>
                    {/* <Grid item xs={2}>
                        <LeadTimeZoneFilterComponent />
                    </Grid> */}
                    {isAdminOrCSRM && (
                        <Grid item xs={2}>
                            <LeadUserListFilterComponent users={users} />
                        </Grid>
                    )}
                    <Grid item xs={2}>
                        <LeadDispositionFilterSearch
                            dispositions={dispositions}
                        />
                    </Grid>
                    {hasAssignPermission && (
                        <Grid item>
                            <LeadAssignComponent data={rows} users={users} />
                        </Grid>
                    )}
                    <Grid item xs={12}>
                        <LeadDataSearch />
                    </Grid>
                </Grid>
                <Grid container item xs={12}>
                    <SelectPaginatedTable
                        columns={columns}
                        rows={rows}
                        CellComponent={LeadTableCellComponent}
                        current_page={current_page}
                        per_page={per_page}
                        last_page={last_page}
                        total={total}
                        url="/retention"
                        selection={selection}
                        setSelection={setSelection}
                        hasAssignPermission={hasAssignPermission}
                    />
                </Grid>
            </MainContentTemplate>

            <Snackbar 
                open={snackbarMsg.open} 
                autoHideDuration={6000} 
                onClose={handleSnackbarClose}
                anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
            >
                <Alert onClose={handleSnackbarClose} severity={snackbarMsg.severity} sx={{ width: '100%' }}>
                    {snackbarMsg.message}
                </Alert>
            </Snackbar>
        </AuthenticatedLayout>
    );
};

export default index;
