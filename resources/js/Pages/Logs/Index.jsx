import { Grid } from "@mui/material";

import PaginatedTable from "@/Components/PaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import LogsTableCellComponent from "./LogsTableCellComponent";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";

export default function Index({ auth, activities }) {
    const columns = [
        { name: "id", title: "ID" },
        { name: "name", title: "Name" },
        { name: "event", title: "Event" },
        { name: "description", title: "Description" },
        { name: "properties", title: "Old Data" },
        { name: "properties", title: "New Data" },
        { name: "created_at", title: "Created At" },
        { name: "updated_at", title: "Updated At" },
    ];

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Logs" />
            <MainContentTemplate title="Logs" subtitle="View logs details here">
                <Grid item xs={12}>
                    <PaginatedTable
                        row={activities.data}
                        columns={columns}
                        perPage={activities.per_page}
                        currentPage={activities.current_page}
                        total={activities.total}
                        url={"logs"}
                        tableCellComponent={LogsTableCellComponent}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}
