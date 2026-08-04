import PaginatedTable from "@/Components/PaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";
import { Grid } from "@mui/material";
import StatesTableCellComponent from "./StatesTableCellComponent";

const Index = ({ auth, states }) => {
    const columns = [
        { name: "id", title: "ID" },
        { name: "name", title: "Name" },
        { name: "fips_code", title: "Fips Code" },
        { name: "iso2", title: "Iso 2" },
        { name: "longitude", title: "Longitude" },
        { name: "latitude", title: "Latitude" },
        { name: "type", title: "Type" },
        { name: "flag", title: "Flag" },
        { name: "wikiDataId", title: "Wikidata id" },
        { name: "created_at", title: "Created At" },
        { name: "updated_at", title: "Updated At" },
        { name: "actions", title: "Actions" },
    ];

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="States" />
            <MainContentTemplate
                title={`States`}
                subtitle="View states details here"
                button="Go to Countries"
                href={route("country.index")}
            >
                <Grid item xs={12}>
                    <PaginatedTable
                        columns={columns}
                        row={states.data}
                        currentPage={states.current_page}
                        perPage={states.per_page}
                        total={states.total}
                        url={states.path}
                        tableCellComponent={StatesTableCellComponent}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default Index;
