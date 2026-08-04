import PaginatedTable from "@/Components/PaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";
import { Grid } from "@mui/material";
import CountriesTableCellComponent from "./CountriesTableCellComponent";

const Index = ({ auth, countries }) => {
    const columns = [
        { name: "id", title: "ID" },
        { name: "name", title: "Name" },
        { name: "capital", title: "Capital" },
        { name: "nationality", title: "Nationality" },
        { name: "native", title: "Native" },
        { name: "region", title: "Region" },
        { name: "subregion", title: "Subregion" },
        { name: "currency", title: "Currency" },
        { name: "currency_symbol", title: "Currency symbol" },
        { name: "currency_name", title: "Currency name" },
        { name: "numeric_code", title: "Numeric code" },
        { name: "phonecode", title: "Phone code" },
        { name: "longitude", title: "Longitude" },
        { name: "latitude", title: "Latitude" },
        { name: "created_at", title: "Created At" },
        { name: "updated_at", title: "Updated At" },
        { name: "actions", title: "Actions" },
    ];

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Countries" />
            <MainContentTemplate
                title="Countries"
                subtitle="View countries details here"
            >
                <Grid item xs={12}>
                    <PaginatedTable
                        columns={columns}
                        row={countries.data}
                        currentPage={countries.current_page}
                        perPage={countries.per_page}
                        total={countries.total}
                        url={countries.path}
                        tableCellComponent={CountriesTableCellComponent}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default Index;
