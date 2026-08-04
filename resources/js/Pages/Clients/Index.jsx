import { Grid } from "@mui/material";

import PaginatedTable from "@/Components/PaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { hasRole } from "@/utils/AccessManager";
import { Head } from "@inertiajs/react";
import ClientTableCellComponent from "./ClientTableCellComponent";

export default function Index({ auth, clients }) {
    const role = hasRole(auth, ["Admin", "Business Development Manager"]);

    const createClientButton = role
        ? { name: "create client", href: route("client.create") }
        : { name: "", href: "" };

    const columns = [
        { name: "id", title: "ID" },
        { name: "fullname", title: "Name" },
        { name: "designation", title: "Designation" },
        { name: "linkdinurl", title: "Linkdin" },
        { name: "cname", title: "Company" },
        { name: "clientPhone", title: "Phone" },
        { name: "ClientEmail", title: "Email" },
        { name: "actions", title: "Actions" },
    ];

    // const [columnsExtension] = useState([
    //     { columnName: "id", width: 80, align: "center" },
    //     { columnName: "fullname", width: 200 },
    //     { columnName: "designation", width: 200 },
    //     { columnName: "linkdinurl", width: 200 },
    //     { columnName: "cname", width: 200 },
    //     { columnName: "clientPhone", width: 200 },
    //     { columnName: "ClientEmail", width: 200 },
    //     { columnName: "action", width: 120, align: "right" },
    // ]);

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Clients" />
            <MainContentTemplate
                title="Clients"
                subtitle="View Client details here"
                button={createClientButton.name}
                href={createClientButton.href}
            >
                <Grid item xs={12}>
                    <PaginatedTable
                        row={clients.data}
                        columns={columns}
                        currentPage={clients.current_page}
                        perPage={clients.per_page}
                        total={clients.total}
                        url={clients.path}
                        tableCellComponent={ClientTableCellComponent}
                        // columnsExtension={
                        //     columnsExtension ? columnsExtension : []
                        // }
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}
