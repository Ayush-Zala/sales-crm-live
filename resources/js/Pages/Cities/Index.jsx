import PaginatedTable from "@/Components/PaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";
import { Grid } from "@mui/material";
import CitiesTableCellComponent from "./CitiesTableCellComponent";

const Index = ({ auth, cities }) => {
    const columns = [
        { name: "id", title: "ID" },
        { name: "name", title: "Name" },
        { name: "state_code", title: "State Code" },
        { name: "country_code", title: "Country Code" },
        { name: "longitude", title: "Longitude" },
        { name: "latitude", title: "Latitude" },
        { name: "flag", title: "Flag" },
        { name: "wikiDataId", title: "Wikidata id" },
        { name: "created_at", title: "Created At" },
        { name: "updated_at", title: "Updated At" },
        { name: "actions", title: "Actions" },
    ];

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Cities" />
            <MainContentTemplate
                title={`Cities`}
                subtitle="View cities details here"
                button="Go to States"
                onClick={() => window.history.go(-2)}
            >
                <Grid item xs={12}>
                    <PaginatedTable
                        columns={columns}
                        row={cities.data}
                        currentPage={cities.current_page}
                        perPage={cities.per_page}
                        total={cities.total}
                        url={cities.path}
                        tableCellComponent={CitiesTableCellComponent}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default Index;
