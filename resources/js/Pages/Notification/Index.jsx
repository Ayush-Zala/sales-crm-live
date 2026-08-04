import PaginatedTable from "@/Components/PaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import NotificationTableCellComponent from "./NotificationTableCellComponent";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Grid } from "@mui/material";
import { Head } from "@inertiajs/react";

export default function Index({ auth, notifications }) {
    const columns = [
        { name: "id", title: "ID" },
        { name: "name", title: "Company Name" },
        { name: "title", title: "Status" },
        { name: "description", title: "Description" },
    ];

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Notifications" />
            <MainContentTemplate
                title="Notifications"
                subtitle="View notification details here"
            >
                <Grid item xs={12}>
                    <PaginatedTable
                        row={notifications.data}
                        columns={columns}
                        perPage={notifications.per_page}
                        currentPage={notifications.current_page}
                        total={notifications.total}
                        url="notification"
                        tableCellComponent={NotificationTableCellComponent}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}
