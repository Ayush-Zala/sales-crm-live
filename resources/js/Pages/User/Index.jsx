import SimplePaginatedTable from "@/Components/SimplePaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { extractUrlParams } from "@/utils/ExtractUrlParams";
import { Head, usePage } from "@inertiajs/react";
import { Grid } from "@mui/material";
import FilterUserComponent from "./FilterUserComponent";
import { UserDataSearch } from "./user-data-search";
import UserTableCellComponent from "./UserTableCellComponent";

export default function Index({ auth, users }) {
    const page = usePage();
    const params = extractUrlParams(page.url);

    const { current_page, per_page, total, path, data: rows } = users;

    const columns = [
        { id: "id", label: "ID", align: "left", width: "2%" },
        { id: "name", label: "Name" },
        { id: "role", label: "Role", textAlign: "left" },
        { id: "reporting_authority_name", label: "Reporting Authority" },
        { id: "assigned_accounts", label: "Assigned" },
        { id: "is_active", label: "Status" },
        { id: "created_at", label: "Created At" },
        { id: "updated_at", label: "Updated At" },
        {
            id: "actions",
            label: "Actions",
            align: "right",
            width: "5%",
        },
    ];

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Users" />

            <MainContentTemplate
                title="Users"
                subtitle="View user details here"
                button="Add new user"
                href={route("user.create")}
            >
                <Grid
                    container
                    item
                    xs={12}
                    columns={12}
                    spacing={1}
                    alignItems="center"
                >
                    <Grid item xs={12} lg={5}>
                        <FilterUserComponent search={params.filter} />
                    </Grid>
                    <Grid item xs={12} lg={7}>
                        <UserDataSearch search={params.search} />
                    </Grid>
                </Grid>
                <Grid item xs={12}>
                    <SimplePaginatedTable
                        columns={columns}
                        rows={rows}
                        current_page={current_page}
                        per_page={per_page}
                        total={total}
                        url={path}
                        CellComponent={UserTableCellComponent}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}
