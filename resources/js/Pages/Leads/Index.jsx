import { Head } from "@inertiajs/react";
import { Grid } from "@mui/material";

import SelectPaginatedTable from "@/Components/SelectPaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { hasPermission, hasRole } from "@/utils/AccessManager";
import LeadFilterComponent from "./LeadFilterComponent";
import LeadTableCellComponent from "./LeadTableCellComponent";
import LeadTimeZoneFilterComponent from "./LeadTimeZoneFilterComponent";
import LeadUserListFilterComponent from "./LeadUserListFilterComponent";
import { LeadDataSearch } from "./lead-data-search";
import LeadDispositionFilterSearch from "./lead-disposition-filter-search";
import LeadSourceFilterSearch from "./lead-source-filter-search";
import LeadAssignComponent from "./LeadAssignComponent";
import { useLeadsSelectionStore } from "@/store/leads-selection-store";

const index = ({ auth, leadsData, users, dispositions, leadSources }) => {
    const { current_page, per_page, total, last_page, data: rows } = leadsData;

    const { selection, setSelection } = useLeadsSelectionStore();

    const role = hasRole(auth, [
        "Business Development Manager",
        "Business Development Team Lead",
        "Sales Executives",
    ]);

    const isAdminOrBDM = hasRole(auth, [
        "Business Development Manager",
        "Admin",
    ]);

    const isBDM = hasRole(auth, ["Business Development Manager"]);

    const isDEM = hasRole(auth, ["Data Entry Manager"]);

    const hasAssignPermission = hasPermission(
        auth,
        "Can Edit Lead Assign User"
    );

    const createLeadButton =
        role || isAdminOrBDM || isDEM || hasPermission(auth, "Can Create Lead")
            ? { name: "Create a new Lead", href: route("lead.create") }
            : { name: "", href: "" };

    const allColumns = [
        { id: "name", label: "Name", align: "left", disableSearch: false },
        { id: "website", label: "Website", disableSearch: true },
        { id: "industry", label: "Industry", disableSearch: false },
        { id: "fax", label: "Fax", disableSearch: false },
        { id: "leadPhones", label: "Lead Phones", disableSearch: false },
        { id: "leadEmails", label: "Lead Emails", disableSearch: false },
        { id: "clientPhones", label: "Client Phones", disableSearch: false },
        { id: "clientEmails", label: "Client Emails", disableSearch: false },
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
        { id: "leadBy", label: "Lead By", disableSearch: false },
        { id: "lead_source", label: "Lead Source", disableSearch: false },
        { id: "country", label: "Country", disableSearch: true },
        { id: "state", label: "State", disableSearch: true },
        { id: "timezoneFilter", label: "Timezone", disableSearch: false },
        {
            id: "dispositionType",
            label: "Disposition",
            align: "right",
            disableSearch: true,
        },
    ];

    const columns = allColumns.filter((column) => {
        // Exclude both `assignTo` and `assignBy` for non-Admin/Non-BDM
        if (
            // !isAdminOrBDM &&
            // !isBDM &&
            !hasAssignPermission &&
            ["assignTo", "assignBy"].includes(column.id)
        )
            return false;

        if (isDEM && column.id === "disposition") return false;

        // Include all other columns
        return true;
    });

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Leads" />
            <MainContentTemplate
                title="Leads"
                subtitle="View Leads details here"
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
                    <Grid item xs={2}>
                        <LeadTimeZoneFilterComponent />
                    </Grid>
                    {isAdminOrBDM && (
                        <Grid item xs={2}>
                            <LeadUserListFilterComponent users={users} />
                        </Grid>
                    )}
                    <Grid item xs={2}>
                        <LeadDispositionFilterSearch
                            dispositions={dispositions}
                        />
                    </Grid>
                    <Grid item xs={2}>
                        <LeadSourceFilterSearch leadSources={leadSources} />
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
                        url="/lead"
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
