import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";
import { Grid } from "@mui/material";
import { useState } from "react";
import RolesTableCellComponent from "./RolesTableCellComponent";
import { Fragment } from "react";
import PaginatedTable from "@/Components/SimplePaginatedTable";

const Index = ({ auth, roles }) => {
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
                <Head title="Roles" />
                <MainContentTemplate
                    title="Roles"
                    subtitle="View roles here"
                    button="Create new role"
                    href={route("role.createView")}
                >
                    <Grid item xs={12}>
                        <PaginatedTable
                            columns={columns}
                            rows={roles.data}
                            current_page={roles.current_page}
                            per_page={roles.per_page}
                            total={roles.total}
                            url={roles.path}
                            CellComponent={RolesTableCellComponent}
                        />
                    </Grid>
                </MainContentTemplate>
            </AuthenticatedLayout>
        </Fragment>
    );
};

export default Index;
