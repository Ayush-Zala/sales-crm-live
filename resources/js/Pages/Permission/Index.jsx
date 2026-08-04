import PaginatedTable from "@/Components/SimplePaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";
import { Grid } from "@mui/material";
import { Fragment } from "react";
import PermissionsTableCellComponent from "./PermissionsTableCellComponent";
import CreatePermission from "./dialogs/CreatePermission";

const Index = ({ auth, permissions }) => {
    const columns = [
        { id: "id", label: "ID", align: "left" },
        { id: "name", label: "Name", textAlign: "left" },
        { id: "guard_name", label: "Guard Name", textAlign: "left" },
        { id: "created_at", label: "Create At", textAlign: "left" },
        { id: "updated_at", label: "Updated At", textAlign: "left" },
        { id: "actions", label: "Actions", align: "right" },
    ];

    return (
        <Fragment>
            <AuthenticatedLayout user={auth.user}>
                <Head title="Permissions" />
                <MainContentTemplate
                    title="Permissions"
                    subtitle="View permissions here"
                    tempButton={<CreatePermission />}
                >
                    <Grid item xs={12}>
                        <PaginatedTable
                            columns={columns}
                            rows={permissions.data}
                            current_page={permissions.current_page}
                            per_page={permissions.per_page}
                            total={permissions.total}
                            url={permissions.path}
                            CellComponent={PermissionsTableCellComponent}
                        />
                    </Grid>
                </MainContentTemplate>
            </AuthenticatedLayout>
        </Fragment>
    );
};

export default Index;
