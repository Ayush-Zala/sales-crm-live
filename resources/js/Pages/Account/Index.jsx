import { Head, usePage } from "@inertiajs/react";
import { Grid } from "@mui/material";

import SelectPaginatedTable from "@/Components/SelectPaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { useSelectionStore } from "@/store/selection-store";
import { hasPermission, hasRole } from "@/utils/AccessManager";
import { extractUrlParams } from "@/utils/ExtractUrlParams";
import { AccountDataSearch } from "./account-data-search";
import AccountTableCellComponent from "./AccountTableCellComponent";
import AssignComponent from "./AssignComponent";
import DispositionFilterSearch from "./disposition-filter-search";
import FilterComponent from "./FilterComponent";
import IndustryFilterComponent from "./IndustryFilterComponent";
import TimeZoneFilterComponent from "./TimeZoneFilterComponent";
import UserListFilterComponent from "./UserListFilterComponent";

export default function Index({
    auth,
    accountsData,
    users,
    dispositions,
    industries,
}) {
    const page = usePage();
    const params = extractUrlParams(page.url);

    const { selection, setSelection } = useSelectionStore();

    const role = hasRole(auth, [
        "Business Development Manager",
        "Sales Executives",
    ]);

    const isAdminOrBDM = hasRole(auth, [
        "Business Development Manager",
        "Business Development Team Lead",
        "Admin",
    ]);

    const isDEMOrDE = hasRole(auth, ["Data Entry Manager", "Data Entry"]);

    const hasAssignPermission = hasPermission(
        auth,
        "Can Edit Company Assign User"
    );

    const hasDispositionViewPermission = hasPermission(
        auth,
        "Can View Company Dispositions"
    );

    const hasViewAssignFilterPerm = hasPermission(
        auth,
        "Can View Company Assign By"
    );

    const createAccButton = !role
        ? { name: "Create Account", href: route("account.create") }
        : { name: "", href: "" };

    const allColumns = [
        {
            id: "name",
            label: "Name",
            align: "left",
            disableSearch: false,
            search: params.name,
        },
        { id: "website", label: "Website", disableSearch: true, search: "" },
        {
            id: "companyPhones",
            label: "Company Phones",
            disableSearch: false,
            search: params.companyPhones,
        },
        {
            id: "clientPhones",
            label: "Client Phones",
            disableSearch: false,
            search: params.clientPhones,
        },
        {
            id: "companyEmails",
            label: "Company Emails",
            disableSearch: false,
            search: params.companyEmails,
        },
        {
            id: "clientEmails",
            label: "Client Emails",
            disableSearch: false,
            search: params.clientEmails,
        },
        {
            id: "industry",
            label: "Industry",
            disableSearch: false,
            search: params.industryType,
        },
        {
            id: "source",
            label: "Source",
            disableSearch: false,
            search: params.source,
        },
        { id: "fax", label: "Fax", disableSearch: false, search: params.fax },
        {
            id: "assignTo",
            label: "Assign To",
            disableSearch: false,
            search: params.assignTo,
        },
        {
            id: "assignBy",
            label: "Assign By",
            disableSearch: false,
            search: params.assignBy,
        },
        {
            id: "country",
            label: "Country",
            disableSearch: false,
            search: params.country,
        },
        {
            id: "state",
            label: "State",
            disableSearch: false,
            search: params.state,
        },
        {
            id: "timezone",
            label: "Timezone",
            disableSearch: false,
            search: params.timezone,
        },
        {
            id: "dispositionDate",
            label: "Disposition",
            align: "right",
            disableSearch: true,
            search: "",
        },
    ];

    const columns = allColumns.filter((column) => {
        // Exclude `assignBy` for BDM
        if (!hasViewAssignFilterPerm && column.id === "assignBy") return false;

        // Exclude both `assignTo` and `assignBy` for non-Admin/Non-BDM
        if (!isAdminOrBDM && !isDEMOrDE && column.id === "assignTo")
            return false;

        if (!hasDispositionViewPermission && column.id === "dispositionDate")
            return false;

        // Include all other columns
        return true;
    });

    const {
        current_page,
        per_page,
        total,
        last_page,
        data: rows,
    } = accountsData;

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Accounts" />
            <MainContentTemplate
                title="Accounts"
                subtitle="View Accounts details here"
                button={createAccButton.name}
                href={createAccButton.href}
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
                        <FilterComponent
                            canSeeAllFilters={isAdminOrBDM || isDEMOrDE}
                            search={params.filter}
                        />
                    </Grid>
                    <Grid item xs={2}>
                        <IndustryFilterComponent
                            industries={industries}
                            search={params.industry}
                        />
                    </Grid>
                    <Grid item xs={2}>
                        <TimeZoneFilterComponent search={params.timezone} />
                    </Grid>
                    {(isAdminOrBDM || isDEMOrDE) && (
                        <Grid item xs={2}>
                            <UserListFilterComponent
                                users={users}
                                search={params.user}
                            />
                        </Grid>
                    )}
                    {!isDEMOrDE && (
                        <Grid item xs={2}>
                            <DispositionFilterSearch
                                dispositions={dispositions}
                                search={params.disposition}
                            />
                        </Grid>
                    )}
                    {hasAssignPermission && (
                        <Grid item>
                            <AssignComponent
                                data={rows}
                                users={users}
                                auth={auth}
                            />
                        </Grid>
                    )}
                    <Grid item xs={12}>
                        <AccountDataSearch search={params.search} />
                    </Grid>
                </Grid>
                <Grid item xs={12}>
                    <SelectPaginatedTable
                        columns={columns}
                        rows={rows}
                        CellComponent={AccountTableCellComponent}
                        current_page={current_page}
                        per_page={per_page}
                        total={total}
                        last_page={last_page}
                        orderByFilter={params.order}
                        sortFilter={params.sort}
                        selection={selection}
                        setSelection={setSelection}
                        hasAssignPermission={hasAssignPermission}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}
