import { Head } from "@inertiajs/react";
import { Grid } from "@mui/material";

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

    const createLeadButton =
        role || isAdminOrCSRM
            ? {
                  name: "Create a new Retention",
                  href: route("retention.create"),
              }
            : { name: "", href: "" };

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
                button={createLeadButton.name}
                href={createLeadButton.href}
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
        </AuthenticatedLayout>
    );
};

export default index;
